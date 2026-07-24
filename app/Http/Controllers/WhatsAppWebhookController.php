<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ConstituencyRequest;
use App\Models\Mp;
use App\Models\User;
use App\Services\Gemma4Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WhatsAppWebhookController extends Controller
{
    protected Gemma4Service $gemmaService;

    public function __construct(Gemma4Service $gemmaService)
    {
        $this->gemmaService = $gemmaService;
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
        // Extract WhatsApp ID and Message Object
        $phone = $request->input('entry.0.changes.0.value.contacts.0.wa_id');
        $messageData = $request->input('entry.0.changes.0.value.messages.0');

        if (!$phone || !$messageData) {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            // Retrieve or create User (Citizen)
            $user = User::firstOrCreate(
                ['phone_number' => $phone],
                ['whatsapp_linked_at' => now()]
            );

            $userId = $user->getKey();

            // 🛑 Rate Limit Check (1 request every 2 hours)
            $lastRequest = ConstituencyRequest::where('user_id', $userId)
                ->where('created_at', '>=', now()->subHours(2))
                ->latest()
                ->first();

            if ($lastRequest) {
                $nextAllowed = $lastRequest->created_at->addHours(2)->diffForHumans();
                $this->sendWhatsAppMessage($phone, "You can only send one request every 2 hours. Next request available: {$nextAllowed}.");
                return response()->json(['status' => 'rate_limited'], 200);
            }

            // Location Coordinates from WhatsApp if provided
            $latitude = $messageData['location']['latitude'] ?? null;
            $longitude = $messageData['location']['longitude'] ?? null;

            // Resolve MP dynamically based on user/location context
            $assignedMp = $this->resolveMpForUser($user, $latitude, $longitude);
            $mpId = $assignedMp->mp_id ?? $assignedMp->id ?? 1;

            // Process File / Text Payload
            $filePath = null;
            $fileType = 'text';
            $rawText = $messageData['text']['body'] ?? ($messageData['caption'] ?? '');

            if (isset($messageData['image'])) {
                $fileType = 'image';
                $filePath = $this->downloadWhatsAppMedia($messageData['image']['id'], 'jpg');
            } elseif (isset($messageData['audio'])) {
                $fileType = 'audio';
                $filePath = $this->downloadWhatsAppMedia($messageData['audio']['id'], 'ogg');
            } elseif (isset($messageData['video'])) {
                $fileType = 'video';
                $filePath = $this->downloadWhatsAppMedia($messageData['video']['id'], 'mp4');
            }

            // Retrieve recent requests for LLM deduplication comparison
            $recentRequests = ConstituencyRequest::where('mp_id', $mpId)
                ->where('created_at', '>=', now()->subDays(3))
                ->latest()
                ->take(30)
                ->get(['request_id', 'raw_message', 'content', 'created_at']);

            // 🤖 Gemma 4 Processing Engine
            $analysis = $this->gemmaService->processRequestData(
                $rawText,
                $filePath,
                $fileType,
                $user->is_within_constituency ?? true,
                $recentRequests
            );

            $urgency = $analysis['urgency'] ?? 'low';
            $matchedRequestId = $analysis['matched_request_id'] ?? null;

            // Deduplication Check
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

            // Send Confirmation Message back to Citizen
            $mpName = $assignedMp->mp_name ?? 'your MP';
            $constituencyName = $assignedMp->constituency_name ?? $assignedMp->constituency ?? '';
            
            $confirmationMessage = "Your request has been forwarded to {$mpName}";
            if (!empty($constituencyName)) {
                $confirmationMessage .= " ({$constituencyName})";
            }
            $confirmationMessage .= ". You can send another request after two hours.";

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
     * Dynamically match MP based on constituency or fallback.
     */
    private function resolveMpForUser(User $user, ?float $latitude, ?float $longitude): Mp
    {
        if (!empty($user->constituency_name)) {
            $mp = Mp::where('constituency_name', $user->constituency_name)->first();
            if ($mp) return $mp;
        }

        return Mp::first() ?? new Mp(['mp_id' => 1, 'mp_name' => 'Default MP']);
    }

    /**
     * Helper to send WhatsApp messages back to citizen
     */
    private function sendWhatsAppMessage($to, $text)
    {
        $token = config('services.whatsapp.access_token');
        $phoneId = config('services.whatsapp.phone_number_id');

        Http::withToken($token)
            ->post("https://graph.facebook.com/v21.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $text]
            ]);
    }

    /**
     * Download WhatsApp Media (2-Step Meta Graph API Flow)
     */
    private function downloadWhatsAppMedia(string $mediaId, string $extension): ?string 
    {
        try {
            $token = config('services.whatsapp.access_token');

            // Step 1: Retrieve Media URL from Meta
            $mediaResponse = Http::withToken($token)
                ->get("https://graph.facebook.com/v21.0/{$mediaId}");

            if (!$mediaResponse->successful()) {
                Log::error("Failed to fetch media metadata for ID: {$mediaId}");
                return null;
            }

            $downloadUrl = $mediaResponse->json('url');

            // Step 2: Download Media Binary Content
            $fileResponse = Http::withToken($token)->get($downloadUrl);

            if ($fileResponse->successful()) {
                $fileName = "uploads/" . $mediaId . "." . $extension;
                Storage::disk('public')->put($fileName, $fileResponse->body());
                return "storage/" . $fileName;
            }
        } catch (\Exception $e) {
            Log::error("WhatsApp Media Download Error: " . $e->getMessage());
        }

        return null;
    }
}