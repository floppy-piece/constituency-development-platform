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
use App\Services\WhatsApp\WhatsAppLocationService;
use App\Services\WhatsApp\WhatsAppFileService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppWebhookController extends Controller
{
    protected Gemma4Service $gemmaService;
<<<<<<< HEAD
=======
    protected IssueClusterService $clusterService;
    protected PriorityScoringService $priorityService;
    protected BudgetOptimizerService $budgetService;
    protected ResolutionVerificationService $verificationService;
>>>>>>> origin/feature/communities-clustering
    protected WhatsAppLocationService $locationService;
    protected WhatsAppFileService $fileService;
    protected WhatsAppMessagingService $messagingService;

    public function __construct(
        Gemma4Service $gemmaService,
<<<<<<< HEAD
=======
        IssueClusterService $clusterService,
        PriorityScoringService $priorityService,
        BudgetOptimizerService $budgetService,
        ResolutionVerificationService $verificationService,
>>>>>>> origin/feature/communities-clustering
        WhatsAppLocationService $locationService,
        WhatsAppFileService $fileService,
        WhatsAppMessagingService $messagingService
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

    /**
     * Meta Webhook Verification Handshake (GET Request)
     */
    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }

    /**
     * Incoming Message Handler (POST Request)
     */
    public function handleWebhook(Request $request)
    {
        $phone = $request->input('entry.0.changes.0.value.contacts.0.wa_id');
        $messageData = $request->input('entry.0.changes.0.value.messages.0');

        if (!$phone || !$messageData) {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            $user = User::firstOrCreate(
                ['phone_number' => $phone],
                ['whatsapp_linked_at' => now()]
            );

            $userId = $user->getKey();
            $rawText = $messageData['text']['body'] ?? ($messageData['caption'] ?? '');

<<<<<<< HEAD
=======
            // Sprint E: citizen resolution verification replies (YES / NO / photo)
            $pendingVerification = $this->verificationService->findPendingForUser((int) $userId);
            if ($pendingVerification) {
                [$filePath, $fileType] = $this->downloadWhatsAppMediaPayload($messageData);
                $result = $this->verificationService->handleCitizenReply(
                    $user,
                    $rawText,
                    $filePath,
                    $fileType,
                    ConstituencyRequest::CHANNEL_WHATSAPP
                );

                if ($result['handled']) {
                    return response()->json([
                        'status' => 'verification_'.$result['outcome'],
                    ], 200);
                }
            }

>>>>>>> origin/feature/communities-clustering
            // Location Coordinates from WhatsApp native location pin if sent directly
            $latitude = $messageData['location']['latitude'] ?? null;
            $longitude = $messageData['location']['longitude'] ?? null;

            // Parse system coordinate tags
            if ((!$latitude || !$longitude) && preg_match('/\[SYS_LOC:\s*([-\d.]+),\s*([-\d.]+)\]/i', $rawText, $matches)) {
                $latitude = (float) $matches[1];
                $longitude = (float) $matches[2];
                $rawText = trim(preg_replace('/\[SYS_LOC:.*?\]/i', '', $rawText));
            }

            $latitude = $latitude ?? $user->last_latitude;
            $longitude = $longitude ?? $user->last_longitude;

            if ($latitude && $longitude) {
                $user->forceFill([
                    'last_latitude' => $latitude,
                    'last_longitude' => $longitude,
                ])->save();

                Log::info("WhatsApp: Updated coordinates for User ID: {$userId} -> Lat: {$latitude}, Lng: {$longitude}");
            }

            // Rate Limit Check (1 request every 2 hours)
            $lastRequest = ConstituencyRequest::where('user_id', $userId)
                ->where('created_at', '>=', now()->subHours(2))
                ->latest()
                ->first();

            if ($lastRequest) {
                $nextAllowed = $lastRequest->created_at->addHours(2)->diffForHumans();
                $this->sendWhatsAppMessage($phone, "⏳ You can only send one request every 2 hours. Next request available: {$nextAllowed}.");
                return response()->json(['status' => 'rate_limited'], 200);
            }

            $assignedMp = $this->resolveMpForUser($user, $latitude, $longitude);
            $mpId = $assignedMp->mp_id ?? $assignedMp->id ?? 1;
            $ward = $this->resolveWardForCoordinates($latitude, $longitude);
            $wardId = $ward?->ward_id;

            // Process File Payloads via File Service
            [$filePath, $fileType] = $this->downloadWhatsAppMediaPayload($messageData);

            $recentRequests = ConstituencyRequest::where('mp_id', $mpId)
                ->where('created_at', '>=', now()->subDays(3))
                ->latest()
                ->take(30)
                ->get(['request_id', 'raw_message', 'content', 'created_at']);

            $analysis = $this->gemmaService->processRequestData(
                $rawText,
                $filePath,
                $fileType,
                $user->is_within_constituency ?? true,
                $recentRequests
            );

            if (empty($analysis['translated_summary'])) {
                $analysis['translated_summary'] = $rawText ?: 'Media upload submission';
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
                    if (! is_array($clusterWardIds)) {
                        $clusterWardIds = [];
                    }
                    if ($wardId) {
                        $clusterWardIds = array_values(array_unique(array_merge($clusterWardIds, [$wardId])));
                    }

                    $similarRequest->increment('similar_count');
                    $similarRequest->cluster_ward_ids = $clusterWardIds;
                    $similarRequest->save();
                } else {
                    ConstituencyRequest::create([
                        'user_id'          => $userId,
                        'mp_id'            => $mpId,
                        'ward_id'          => $wardId,
                        'raw_message'      => $rawText ?: 'Media upload submission',
                        'content'          => $analysis['translated_summary'] ?? ($rawText ?: 'Media upload submission'),
                        'upload_file_path' => $filePath,
                        'file_type'        => $fileType,
                        'urgency'          => $urgency,
                        'category'         => $category,
                        'confidence'       => $confidence,
                        'status'           => $status,
                        'similar_count'    => 1,
                        'cluster_ward_ids' => $wardId ? [$wardId] : [],
                        'latitude'         => $latitude ?? 0.0000,
                        'longitude'        => $longitude ?? 0.0000,
                    ]);
                }
            } else {
                ConstituencyRequest::create([
                    'user_id'          => $userId,
                    'mp_id'            => $mpId,
                    'ward_id'          => $wardId,
                    'raw_message'      => $rawText ?: 'Media upload submission',
                    'content'          => $analysis['translated_summary'] ?? ($rawText ?: 'Media upload submission'),
                    'upload_file_path' => $filePath,
                    'file_type'        => $fileType,
                    'urgency'          => $urgency,
                    'category'         => $category,
                    'confidence'       => $confidence,
                    'status'           => $status,
                    'similar_count'    => 1,
                    'cluster_ward_ids' => $wardId ? [$wardId] : [],
                    'latitude'         => $latitude ?? 0.0000,
                    'longitude'        => $longitude ?? 0.0000,
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

            $createdRequest = ConstituencyRequest::create(array_merge([
                'user_id'          => $userId,
                'mp_id'            => $mpId,
                'ward_id'          => $wardId,
                'raw_message'      => $rawText ?: 'Media upload submission',
                'content'          => $analysis['translated_summary'] ?? ($rawText ?: 'Media upload submission'),
                'upload_file_path' => $filePath,
                'file_type'        => $fileType,
                'source_channel'   => ConstituencyRequest::CHANNEL_WHATSAPP,
                'urgency'          => $urgency,
                'category'         => $category,
                'confidence'       => $confidence,
                'status'           => $status,
                'similar_count'    => 1,
                'cluster_ward_ids' => $wardId ? [$wardId] : [],
                'latitude'         => $latitude ?? 0.0000,
                'longitude'        => $longitude ?? 0.0000,
            ], $explainability));

            $this->clusterService->attachRequest($createdRequest, $analysis, $matchedRequest);
            $scored = $this->priorityService->score($createdRequest->fresh(['cluster', 'mp', 'ward.constituency']));
            $this->budgetService->ensureCost($scored, false);

>>>>>>> origin/feature/communities-clustering
            $mpName = $assignedMp->mp_name ?? 'your MP';
            $constituencyName = $assignedMp->constituency_name ?? $assignedMp->constituency ?? '';
            
            $confirmationMessage = "✅ Your request has been successfully recorded and forwarded to {$mpName}";
            if (!empty($constituencyName)) {
                $confirmationMessage .= " ({$constituencyName})";
            }
            $confirmationMessage .= ".\n\nThank you for participating in your constituency development. You can send another request after two hours.";

            $this->sendWhatsAppMessage($phone, $confirmationMessage);

            return response()->json(['status' => 'success'], 200);

        } catch (Throwable $e) {
            Log::error("WhatsApp Webhook Error for phone {$phone}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $messageData,
            ]);

            $this->sendWhatsAppMessage(
                $phone,
                '⚠️ Sorry, your request failed to process due to a system error. Please try again after a few hours, thank you 🙂.'
            );

            return response()->json(['status' => 'error', 'message' => 'Processed with error'], 200);
        }
    }

    /**
     * Resolve the most likely ward based on incoming coordinates.
     */
    private function resolveWardForCoordinates(?float $latitude, ?float $longitude): ?object
    {
        return $this->locationService->resolveWard($latitude, $longitude);
    }

    /**
     * Dynamically match MP based on live coordinates or fallback attributes.
     */
    private function resolveMpForUser(User $user, ?float $latitude, ?float $longitude): object
    {
        return $this->locationService->resolveMp($user, $latitude, $longitude);
    }

    /**
     * Automated WhatsApp Outbound Message Dispatcher
     */
    private function sendWhatsAppMessage(string $to, string $text): void
    {
        $this->messagingService->sendMessage($to, $text);
    }

    /**
     * Download WhatsApp Media wrapper
     */
    private function downloadWhatsAppMedia(string $mediaId, string $extension): ?string 
    {
        return $this->fileService->downloadMedia($mediaId, $extension);
    }

    /**
     * Internal helper to extract file type payload from message array
     */
    private function downloadWhatsAppMediaPayload(array $messageData): array
    {
        $filePath = null;
        $fileType = 'text';

        if (isset($messageData['image']['id'])) {
            $fileType = 'image';
            $filePath = $this->downloadWhatsAppMedia($messageData['image']['id'], 'jpg');
        } elseif (isset($messageData['audio']['id'])) {
            $fileType = 'audio';
            $filePath = $this->downloadWhatsAppMedia($messageData['audio']['id'], 'ogg');
        } elseif (isset($messageData['video']['id'])) {
            $fileType = 'video';
            $filePath = $this->downloadWhatsAppMedia($messageData['video']['id'], 'mp4');
        } elseif (isset($messageData['document']['id'])) {
            $fileType = 'document';
            $filePath = $this->downloadWhatsAppMedia($messageData['document']['id'], 'pdf');
        }

        // Ensure we explicitly return null if no file was actually downloaded
        if ($filePath && !file_exists($filePath)) {
            $filePath = null;
            $fileType = 'text';
        }

        return [$filePath, $fileType];
    }
}