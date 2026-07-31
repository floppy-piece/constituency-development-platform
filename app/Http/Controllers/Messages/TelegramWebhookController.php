<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\ConstituencyRequest;
use App\Models\User;
use App\Services\Gemma4Service;
<<<<<<< HEAD
=======
use App\Services\IssueClusterService;
use App\Services\PriorityScoringService;
use App\Services\BudgetOptimizerService;
use App\Services\ResolutionVerificationService;
>>>>>>> origin/feature/communities-clustering
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
<<<<<<< HEAD
=======
    protected IssueClusterService $clusterService;
    protected PriorityScoringService $priorityService;
    protected BudgetOptimizerService $budgetService;
    protected ResolutionVerificationService $verificationService;
>>>>>>> origin/feature/communities-clustering
    protected TelegramLocationService $locationService;
    protected TelegramFileService $fileService;
    protected TelegramMessagingService $messagingService;

    public function __construct(
<<<<<<< HEAD
        Gemma4Service $gemmaService, 
=======
        Gemma4Service $gemmaService,
        IssueClusterService $clusterService,
        PriorityScoringService $priorityService,
        BudgetOptimizerService $budgetService,
        ResolutionVerificationService $verificationService,
>>>>>>> origin/feature/communities-clustering
        TelegramLocationService $locationService,
        TelegramFileService $fileService,
        TelegramMessagingService $messagingService
    ) {
        $this->gemmaService = $gemmaService;
<<<<<<< HEAD
=======
        $this->clusterService = $clusterService;
        $this->priorityService = $priorityService;
        $this->budgetService = $budgetService;
        $this->verificationService = $verificationService;
>>>>>>> origin/feature/communities-clustering
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

        $message = $request->input('message') ?? $request->input('edited_message');
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

<<<<<<< HEAD
=======
            // Sprint E: intercept citizen YES/NO/photo replies for pending resolution verification
            // before /start, location gate, or rate limit turn this into a new request.
            $pendingVerification = $this->verificationService->findPendingForUser((int) $userId);
            if ($pendingVerification && ! str_starts_with($rawText, '/start')) {
                $fileData = $this->fileService->downloadTelegramFile($request->all());
                $filePath = $fileData['path'] ?? null;
                $fileType = $fileData['type'] ?? 'text';
                // Prefer public path for dashboard viewing when available
                if (! empty($fileData['public_path'])) {
                    $filePath = $fileData['public_path'];
                }

                $result = $this->verificationService->handleCitizenReply(
                    $user,
                    $rawText,
                    $filePath,
                    $fileType,
                    ConstituencyRequest::CHANNEL_TELEGRAM
                );

                if ($result['handled']) {
                    return response()->json([
                        'status' => 'verification_'.$result['outcome'],
                    ], 200);
                }
            }

>>>>>>> origin/feature/communities-clustering
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

            // Extract content types and file info via File Service using downloadTelegramFile()
            $fileData = $this->fileService->downloadTelegramFile($request->all());
            $filePath = $fileData['path'] ?? null;
            $fileType = $fileData['type'] ?? 'text';

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

            // Clean up temporary local media file
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }

            $urgency = $analysis['urgency'] ?? 'low';
            $matchedRequestId = $analysis['matched_request_id'] ?? null;
            $confidence = (float) ($analysis['confidence'] ?? 0.4);
            $status = ConstituencyRequest::statusFromConfidence($confidence);
            $category = $analysis['category'] ?? 'General';
<<<<<<< HEAD

            if ($matchedRequestId) {
                $similarRequest = ConstituencyRequest::where('request_id', $matchedRequestId)
                    ->where('mp_id', $mpId)
                    ->first();

                if ($similarRequest) {
                    $clusterWardIds = $similarRequest->cluster_ward_ids ?? [];
                    if (!is_array($clusterWardIds)) {
                        $clusterWardIds = [];
                    }
                    if ($ward->ward_id ?? null) {
                        $clusterWardIds = array_values(array_unique(array_merge($clusterWardIds, [$ward->ward_id])));
                    }

                    $similarRequest->increment('similar_count');
                    $similarRequest->cluster_ward_ids = $clusterWardIds;
                    $similarRequest->save();
                } else {
                    ConstituencyRequest::create([
                        'user_id'          => $userId,
                        'mp_id'            => $mpId,
                        'ward_id'          => $ward->ward_id ?? null,
                        'raw_message'      => $rawText ?: 'Media upload submission',
                        'content'          => $analysis['translated_summary'] ?? ($rawText ?: 'Media upload submission'),
                        'upload_file_path' => $filePath,
                        'file_type'        => $fileType,
                        'urgency'          => $urgency,
                        'category'         => $category,
                        'confidence'       => $confidence,
                        'status'           => $status,
                        'similar_count'    => 1,
                        'cluster_ward_ids' => isset($ward->ward_id) ? [$ward->ward_id] : [],
                        'latitude'         => $latitude,
                        'longitude'        => $longitude,
                    ]);
                }
            } else {
                ConstituencyRequest::create([
                    'user_id'          => $userId,
                    'mp_id'            => $mpId,
                    'ward_id'          => $ward->ward_id ?? null,
                    'raw_message'      => $rawText ?: 'Media upload submission',
                    'content'          => $analysis['translated_summary'] ?? ($rawText ?: 'Media upload submission'),
                    'upload_file_path' => $filePath,
                    'file_type'        => $fileType,
                    'urgency'          => $urgency,
                    'category'         => $category,
                    'confidence'       => $confidence,
                    'status'           => $status,
                    'similar_count'    => 1,
                    'cluster_ward_ids' => isset($ward->ward_id) ? [$ward->ward_id] : [],
                    'latitude'         => $latitude,
                    'longitude'        => $longitude,
                ]);
            }

=======
            $explainability = [
                'urgency_score'       => isset($analysis['urgency_score']) ? (int) $analysis['urgency_score'] : null,
                'evaluation_thoughts' => $analysis['evaluation_thoughts'] ?? null,
                'suggested_fix'       => $analysis['suggested_fix'] ?? null,
                'detected_language'   => $analysis['detected_language'] ?? null,
            ];

            $matchedRequest = null;
            if ($matchedRequestId) {
                $matchedRequest = ConstituencyRequest::where('request_id', $matchedRequestId)
                    ->where('mp_id', $mpId)
                    ->first();
            }

            // Always keep the citizen's row — matches are theme evidence, not discards.
            $createdRequest = ConstituencyRequest::create(array_merge([
                'user_id'          => $userId,
                'mp_id'            => $mpId,
                'ward_id'          => $ward->ward_id ?? null,
                'raw_message'      => $rawText ?: 'Media upload submission',
                'content'          => $analysis['translated_summary'] ?? ($rawText ?: 'Media upload submission'),
                'upload_file_path' => $filePath,
                'file_type'        => $fileType,
                'source_channel'   => ConstituencyRequest::CHANNEL_TELEGRAM,
                'urgency'          => $urgency,
                'category'         => $category,
                'confidence'       => $confidence,
                'status'           => $status,
                'similar_count'    => 1,
                'cluster_ward_ids' => isset($ward->ward_id) ? [$ward->ward_id] : [],
                'latitude'         => $latitude,
                'longitude'        => $longitude,
            ], $explainability));

            $this->clusterService->attachRequest($createdRequest, $analysis, $matchedRequest);
            $scored = $this->priorityService->score($createdRequest->fresh(['cluster', 'mp', 'ward.constituency']));
            $this->budgetService->ensureCost($scored, false);

>>>>>>> origin/feature/communities-clustering
            $mpName = $mp->mp_name ?? 'your MP';
            $constituencyName = $constituency->name ?? '';
            $wardName = $ward->name ?? '';

            $confirmationMessage = "Your request has been forwarded to {$mpName}";
            if (!empty($constituencyName)) {
                $confirmationMessage .= " ({$constituencyName}";
                if (!empty($wardName)) {
                    $confirmationMessage .= ", {$wardName} Ward";
                }
                $confirmationMessage .= ")";
            }
            $confirmationMessage .= ". You can send another request after two hours.";

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
}