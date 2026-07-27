<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Models\ConstituencyRequest;
use App\Models\Mp;
use App\Models\User;
use App\Models\Ward;
use App\Services\GeocodingService;
use App\Services\Gemma4Service;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TelegramWebhookController extends Controller
{
    protected Gemma4Service $gemmaService;
    protected GeocodingService $geocodingService;
    protected TelegramNotifier $telegramNotifier;

    public function __construct(
        Gemma4Service $gemmaService,
        GeocodingService $geocodingService,
        TelegramNotifier $telegramNotifier
    ) {
        $this->gemmaService = $gemmaService;
        $this->geocodingService = $geocodingService;
        $this->telegramNotifier = $telegramNotifier;
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

                    // Explicitly update and save database columns
                    $user->forceFill([
                        'last_latitude' => $cachedLocation['latitude'],
                        'last_longitude' => $cachedLocation['longitude'],
                    ])->save();

                    Log::info("Telegram Webhook: Successfully updated database coordinates for user ID {$userId}.");

                    Cache::forget($cacheKey);

                    $this->telegramNotifier->send(
                        $chatId, 
                        "✅ Location successfully verified and updated! You can now send your constituency request."
                    );
                } else {
                    Log::warning("Telegram Webhook: Cache key [{$cacheKey}] was empty, expired, or invalid.");
                    $this->telegramNotifier->send(
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
                $this->telegramNotifier->send(
                    $chatId, 
                    "⚠️ Location Missing: Please visit our web platform first, share your physical location, and click the Telegram submission link."
                );
                return response()->json(['status' => 'location_missing'], 200);
            }

            // 3. Resolve Constituency, MP, and Ward dynamically using coordinates and GeocodingService
            $resolution = $this->resolveLocationContext($latitude, $longitude, $request->ip());
            
            if (!$resolution['constituency'] || !$resolution['ward']) {
                $this->telegramNotifier->send(
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
                $this->telegramNotifier->send($chatId, "You can only send one request every 2 hours. Next request available: {$nextAllowed}.");

                return response()->json(['status' => 'rate_limited'], 200);
            }

            // Extract content types (Text, Image, Audio, Video)
            $filePath = null;
            $fileType = 'text';

            if (isset($message['photo'])) {
                $fileType = 'image';
                $photo = end($message['photo']);
                $filePath = $this->downloadTelegramFile($photo['file_id'], 'jpg');
            } elseif (isset($message['voice']) || isset($message['audio'])) {
                $fileType = 'audio';
                $fileId = $message['voice']['file_id'] ?? $message['audio']['file_id'];
                $filePath = $this->downloadTelegramFile($fileId, 'ogg');
            } elseif (isset($message['video'])) {
                $fileType = 'video';
                $fileId = $message['video']['file_id'];
                $filePath = $this->downloadTelegramFile($fileId, 'mp4');
            } elseif (isset($message['video_note'])) {
                $fileType = 'video';
                $fileId = $message['video_note']['file_id'];
                $filePath = $this->downloadTelegramFile($fileId, 'mp4');
            }

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
                true, // verified within boundaries via site coordinates
                $recentRequests
            );

            $urgency = $analysis['urgency'] ?? 'low';
            $matchedRequestId = $analysis['matched_request_id'] ?? null;
            $confidence = (float) ($analysis['confidence'] ?? 0.4);
            $status = ConstituencyRequest::statusFromConfidence($confidence);
            $category = $analysis['category'] ?? 'General';

            $mpName = $mp->mp_name ?? 'your MP';

            if ($matchedRequestId) {
                $similarRequest = ConstituencyRequest::where('request_id', $matchedRequestId)
                    ->where('mp_id', $mpId)
                    ->first();

                if ($similarRequest) {
                    $incomingWardId = $ward->ward_id ?? null;
                    $clusterWardIds = $similarRequest->cluster_ward_ids ?? [];
                    if (! is_array($clusterWardIds)) {
                        $clusterWardIds = [];
                    }
                    if ($incomingWardId) {
                        $clusterWardIds = array_values(array_unique(array_merge($clusterWardIds, [$incomingWardId])));
                    }

                    $similarRequest->increment('similar_count');
                    $similarRequest->cluster_ward_ids = $clusterWardIds;
                    $similarRequest->save();

                    $count = $similarRequest->similar_count;
                    $this->telegramNotifier->send(
                        $chatId,
                        "We received your report. It matches an existing issue (#{$similarRequest->request_id}) that {$count} citizens have now flagged. Forwarded to {$mpName} ({$constituency->name}, {$ward->name} Ward)."
                    );
                } else {
                    $requestItem = ConstituencyRequest::create([
                        'user_id'          => $userId,
                        'mp_id'            => $mpId,
                        'ward_id'          => $ward->ward_id,
                        'raw_message'      => $rawText,
                        'content'          => $analysis['translated_summary'] ?? $rawText,
                        'upload_file_path' => $filePath,
                        'file_type'        => $fileType,
                        'urgency'          => $urgency,
                        'category'         => $category,
                        'confidence'       => $confidence,
                        'status'           => $status,
                        'similar_count'    => 1,
                        'cluster_ward_ids'=> $ward->ward_id ? [$ward->ward_id] : [],
                        'latitude'         => $latitude,
                        'longitude'        => $longitude,
                    ]);

                    $this->notifyCitizenOnIngest($chatId, $requestItem, $status, $mpName, $constituency->name, $ward->name);
                }
            } else {
                $requestItem = ConstituencyRequest::create([
                    'user_id'          => $userId,
                    'mp_id'            => $mpId,
                    'ward_id'          => $ward->ward_id,
                    'raw_message'      => $rawText,
                    'content'          => $analysis['translated_summary'] ?? $rawText,
                    'upload_file_path' => $filePath,
                    'file_type'        => $fileType,
                    'urgency'          => $urgency,
                    'category'         => $category,
                    'confidence'       => $confidence,
                    'status'           => $status,
                    'similar_count'    => 1,
                    'cluster_ward_ids'=> $ward->ward_id ? [$ward->ward_id] : [],
                    'latitude'         => $latitude,
                    'longitude'        => $longitude,
                ]);

                $this->notifyCitizenOnIngest($chatId, $requestItem, $status, $mpName, $constituency->name, $ward->name);
            }

            return response()->json(['status' => 'success'], 200);

        } catch (Throwable $e) {
            Log::error("Telegram Webhook Error for chat {$chatId}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $message,
            ]);

            $this->telegramNotifier->send(
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
        $rawCandidates = $this->geocodingService->resolveLocationCandidates($latitude, $longitude, $clientIp);
        
        $cleanCandidates = collect($rawCandidates)
            ->flatMap(fn($item) => explode(',', $item))
            ->map(fn($item) => trim($item))
            ->filter(fn($item) => !in_array(strtolower($item), ['kenya', 'africa', 'africa/nairobi']))
            ->unique()
            ->values()
            ->all();

        $constituency = null;

        foreach ($cleanCandidates as $areaName) {
            $constituency = Constituency::where('name', 'LIKE', '%' . $areaName . '%')
                ->orWhereRaw('? LIKE CONCAT("%", name, "%")', [$areaName])
                ->first();

            if ($constituency) break;
        }

        if (!$constituency) {
            $constituency = Constituency::select('*')
                ->selectRaw('( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                ->orderBy('distance')
                ->first();
        }

        if (!$constituency) {
            return ['constituency' => null, 'ward' => null, 'mp' => null];
        }

        // Find the matching ward safely using current method parameters ($latitude, $longitude)
        $ward = DB::table('wards')
            ->select('*')
            ->selectRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance', [$latitude, $longitude, $latitude])
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= SQRT(approximate_size / PI())', [$latitude, $longitude, $latitude])
            ->orderBy('distance', 'asc')
            ->first();

        if (!$ward) {
            $ward = Ward::where('constituency_id', $constituency->constituency_id ?? $constituency->id)
                ->select('*')
                ->selectRaw('( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                ->orderBy('distance')
                ->first();
        }

        $mp = Mp::where('constituency_name', 'LIKE', '%' . $constituency->name . '%')->first() ?? Mp::first();

        return [
            'constituency' => $constituency,
            'ward' => $ward,
            'mp' => $mp,
        ];
    }

    /**
     * Confirm receipt and optional auto-categorization to the citizen.
     */
    private function notifyCitizenOnIngest(
        string|int $chatId,
        ConstituencyRequest $requestItem,
        string $status,
        string $mpName,
        string $constituencyName,
        string $wardName
    ): void {
        $id = $requestItem->request_id;
        $category = $requestItem->category ?? 'General';
        $urgency = $requestItem->urgency ?? 'low';

        if ($status === ConstituencyRequest::STATUS_PENDING_REVIEW) {
            $this->telegramNotifier->send(
                $chatId,
                "We received your report (#{$id}). It needs a quick human review before routing to {$mpName} ({$constituencyName}, {$wardName} Ward). We will update you shortly."
            );

            return;
        }

        $this->telegramNotifier->send(
            $chatId,
            "We received your report (#{$id}). Categorized as {$category} ({$urgency} urgency) and forwarded to {$mpName} ({$constituencyName}, {$wardName} Ward). You can send another request after two hours."
        );
    }

    private function downloadTelegramFile(string $fileId, string $extension): ?string
    {
        try {
            $token = config('services.telegram.bot_token');
            $response = Http::get("https://api.telegram.org/bot{$token}/getFile", ['file_id' => $fileId]);

            if ($response->successful()) {
                $telegramFilePath = $response->json('result.file_path');
                $fileContent = Http::get("https://api.telegram.org/file/bot{$token}/{$telegramFilePath}")->body();

                $fileName = 'uploads/'.$fileId.'.'.$extension;
                Storage::disk('public')->put($fileName, $fileContent);

                return 'storage/'.$fileName;
            }
        } catch (\Exception $e) {
            Log::error('Telegram File Download Error: '.$e->getMessage());
        }

        return null;
    }
}