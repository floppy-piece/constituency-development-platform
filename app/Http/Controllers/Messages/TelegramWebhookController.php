<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\ConstituencyRequest;
use App\Models\User;
use App\Services\Gemma4Service;
use App\Services\Telegram\TelegramLocationService;
use App\Services\Telegram\TelegramFileService;
use App\Services\Telegram\TelegramMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TelegramWebhookController extends Controller
{
    protected Gemma4Service $gemmaService;
    protected TelegramLocationService $locationService;
    protected TelegramFileService $fileService;
    protected TelegramMessagingService $messagingService;

    public function __construct(
        Gemma4Service $gemmaService, 
        TelegramLocationService $locationService,
        TelegramFileService $fileService,
        TelegramMessagingService $messagingService
    ) {
        $this->gemmaService = $gemmaService;
        $this->locationService = $locationService;
        $this->fileService = $fileService;
        $this->messagingService = $messagingService;
    }

    public function handleWebhook(Request $request)
    {
        // Secret Token Validation
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($secret !== config('services.telegram.secret')) {
            Log::warning('Telegram Webhook: Unauthorized secret token attempt.');
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = $request->input('message');
        if (! $message) {
            Log::info('Telegram Webhook: Ignored request (no message payload).');
            return response()->json(['status' => 'ignored'], 200);
        }

        $chatId = $message['chat']['id'] ?? null;
        if (! $chatId) {
            Log::info('Telegram Webhook: Ignored request (no chat id).');
            return response()->json(['status' => 'no_chat_id'], 200);
        }

        try {
            $telegramUserId = (string) $message['from']['id'];
            $rawText = $message['text'] ?? ($message['caption'] ?? '');

            Log::info("Telegram Webhook Incoming: User ID [{$telegramUserId}], Text: [{$rawText}]");

            // Track or create user by telegram ID
            $user = User::firstOrCreate(
                ['phone_number' => $telegramUserId],
                ['whatsapp_linked_at' => now()]
            );

            $userId = $user->getKey();

            // 1. Handle Telegram /start deep-link payload containing site coordinates token
            if (str_starts_with($rawText, '/start')) {
                $token = trim(Str::after($rawText, '/start'));
                Log::info("Telegram Webhook: Parsed start token string: [{$token}]");

                $cacheKey = 'telegram_loc_' . $token;
                $cachedLocation = Cache::get($cacheKey);

                if (!empty($token) && $cachedLocation) {
                    Log::info("Telegram Webhook: Found cached location for token.", $cachedLocation);

                    $user->forceFill([
                        'last_latitude' => $cachedLocation['latitude'],
                        'last_longitude' => $cachedLocation['longitude'],
                    ])->save();

                    Log::info("Telegram Webhook: Successfully updated database coordinates for user ID {$userId}.");

                    Cache::forget($cacheKey);

                    $this->sendTelegramMessage(
                        $chatId, 
                        "✅ Location successfully verified and updated! You can now send your constituency request."
                    );
                } else {
                    Log::warning("Telegram Webhook: Cache key [{$cacheKey}] was empty, expired, or invalid.");
                    $this->sendTelegramMessage(
                        $chatId, 
                        "⚠️ Your location session link has expired or is invalid. Please revisit the web platform to re-share your location."
                    );
                }
                return response()->json(['status' => 'start_handled'], 200);
            }

            // 2. Retrieve site-assigned coordinates from the user profile
            $latitude = $user->last_latitude ?? null;
            $longitude = $user->last_longitude ?? null;

            Log::info("Telegram Webhook: Current database coordinates for user ID {$userId} - Lat: " . ($latitude ?? 'NULL') . ", Lng: " . ($longitude ?? 'NULL'));

            if (!$latitude || !$longitude) {
                Log::warning("Telegram Webhook: Request blocked because coordinates are missing/null for user ID {$userId}.");
                $this->sendTelegramMessage(
                    $chatId, 
                    "⚠️ Location Missing: Please visit our web platform first, share your physical location, and click the Telegram submission link."
                );
                return response()->json(['status' => 'location_missing'], 200);
            }

            // 3. Resolve Constituency, MP, and Ward dynamically using coordinates
            $resolution = $this->resolveLocationContext($latitude, $longitude, $request->ip());
            
            if (!$resolution['constituency'] || !$resolution['ward']) {
                $this->sendTelegramMessage(
                    $chatId, 
                    "⚠️ Boundary Error: Your assigned coordinates do not fall within any recognized ward or constituency bounds in our system."
                );
                return response()->json(['status' => 'out_of_bounds'], 200);
            }

            $constituency = $resolution['constituency'];
            $ward = $resolution['ward'];
            $mp = $resolution['mp'];
            $mpId = $mp?->mp_id ?? $mp?->id ?? 1;

            // Rate Limit Check (1 request per 2 hours)
            $lastRequest = ConstituencyRequest::where('user_id', $userId)
                ->where('created_at', '>=', now()->subHours(2))
                ->latest()
                ->first();

            if ($lastRequest) {
                $nextAllowed = $lastRequest->created_at->addHours(2)->diffForHumans();
                $this->sendTelegramMessage($chatId, "You can only send one request every 2 hours. Next request available: {$nextAllowed}.");

                return response()->json(['status' => 'rate_limited'], 200);
            }

            // Extract content types via File Service
            [$filePath, $fileType] = $this->fileService->extractAndDownloadFile($message);

            // Retrieve recent requests for LLM deduplication comparison
            $recentRequests = ConstituencyRequest::where('mp_id', $mpId)
                ->where('created_at', '>=', now()->subDays(3))
                ->latest()
                ->take(30)
                ->get(['request_id', 'raw_message', 'content', 'created_at']);

            // Send payload to Gemma 4
            $analysis = $this->gemmaService->processRequestData(
                $rawText,
                $filePath,
                $fileType,
                true,
                $recentRequests
            );

            $urgency = $analysis['urgency'] ?? 'low';
            $matchedRequestId = $analysis['matched_request_id'] ?? null;

            if ($matchedRequestId) {
                $similarRequest = ConstituencyRequest::where('request_id', $matchedRequestId)
                    ->where('mp_id', $mpId)
                    ->first();

                if ($similarRequest) {
                    $similarRequest->touch();
                }
            } else {
                ConstituencyRequest::create([
                    'user_id'          => $userId,
                    'mp_id'            => $mpId,
                    'ward_id'          => $ward->ward_id,
                    'raw_message'      => $rawText,
                    'content'          => $analysis['translated_summary'] ?? $rawText,
                    'upload_file_path' => $filePath,
                    'file_type'        => $fileType,
                    'urgency'          => $urgency,
                    'category'         => $analysis['category'] ?? 'General',
                    'latitude'         => $latitude,
                    'longitude'        => $longitude,
                ]);
            }

            $mpName = $mp->mp_name ?? 'your MP';
            $confirmationMessage = "Your request has been forwarded to {$mpName} ({$constituency->name}, {$ward->name} Ward). You can send another request after two hours.";

            $this->sendTelegramMessage($chatId, $confirmationMessage);

            return response()->json(['status' => 'success'], 200);

        } catch (Throwable $e) {
            Log::error("Telegram Webhook Error for chat {$chatId}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $message,
            ]);

            $this->sendTelegramMessage(
                $chatId,
                '⚠️ Sorry, your request failed to process due to a system error. Please try again later.'
            );

            return response()->json(['status' => 'error', 'message' => 'Processed with error'], 200);
        }
    }

    /**
     * Resolve Constituency, Ward, and MP using GeocodingService and Haversine radius calculations.
     */
    private function resolveLocationContext(float $latitude, float $longitude, ?string $clientIp): array
    {
        return $this->locationService->resolve($latitude, $longitude, $clientIp);
    }

    private function sendTelegramMessage($chatId, $text)
    {
        $this->messagingService->sendMessage($chatId, $text);
    }

    private function downloadTelegramFile($fileId, $extension): ?string
    {
        return $this->fileService->downloadTelegramFile($fileId, $extension);
    }
}