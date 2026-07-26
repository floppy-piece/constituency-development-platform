<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Models\ConstituencyRequest;
use App\Models\Mp;
use App\Models\User;
use App\Services\Gemma4Service;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WhatsAppWebhookController extends Controller
{
    protected Gemma4Service $gemmaService;
    protected GeocodingService $geocodingService;

    public function __construct(Gemma4Service $gemmaService, GeocodingService $geocodingService)
    {
        $this->gemmaService = $gemmaService;
        $this->geocodingService = $geocodingService;
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
            // Retrieve or create User (Citizen) based on phone number
            $user = User::firstOrCreate(
                ['phone_number' => $phone],
                ['whatsapp_linked_at' => now()]
            );

            $userId = $user->getKey();

            // Extract raw text or caption payload
            $rawText = $messageData['text']['body'] ?? ($messageData['caption'] ?? '');

            // Location Coordinates from WhatsApp native location pin if sent directly
            $latitude = $messageData['location']['latitude'] ?? null;
            $longitude = $messageData['location']['longitude'] ?? null;

            // 🔍 PARSE SYSTEM LAT/LNG PREFIX FROM TEXT (Keeps it cleanly away from Gemma 4)
            if ((!$latitude || !$longitude) && preg_match('/\[SYS_LOC:\s*([-\d.]+),\s*([-\d.]+)\]/i', $rawText, $matches)) {
                $latitude = (float) $matches[1];
                $longitude = (float) $matches[2];
                
                // Strip the system coordinate tag out so Gemma 4 never reads coordinate data
                $rawText = trim(preg_replace('/\[SYS_LOC:.*?\]/i', '', $rawText));
            }

            // Fallback to user's previously stored coordinates if still null
            $latitude = $latitude ?? $user->last_latitude;
            $longitude = $longitude ?? $user->last_longitude;

            // Update database coordinates if successfully found
            if ($latitude && $longitude) {
                $user->forceFill([
                    'last_latitude' => $latitude,
                    'last_longitude' => $longitude,
                ])->save();

                Log::info("WhatsApp: Updated coordinates for User ID: {$userId} -> Lat: {$latitude}, Lng: {$longitude}");
            }

            // 🛑 Rate Limit Check (1 request every 2 hours)
            $lastRequest = ConstituencyRequest::where('user_id', $userId)
                ->where('created_at', '>=', now()->subHours(2))
                ->latest()
                ->first();

            if ($lastRequest) {
                $nextAllowed = $lastRequest->created_at->addHours(2)->diffForHumans();
                $this->sendWhatsAppMessage($phone, "⏳ You can only send one request every 2 hours. Next request available: {$nextAllowed}.");
                return response()->json(['status' => 'rate_limited'], 200);
            }

            // Resolve MP dynamically based on coordinates
            $assignedMp = $this->resolveMpForUser($user, $latitude, $longitude);
            $mpId = $assignedMp->mp_id ?? $assignedMp->id ?? 1;

            // Process File Payloads
            $filePath = null;
            $fileType = 'text';

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

            // 🤖 Gemma 4 Processing Engine (Receives clean text, free of coordinate strings)
            $analysis = $this->gemmaService->processRequestData(
                $rawText,
                $filePath,
                $fileType,
                $user->is_within_constituency ?? true,
                $recentRequests
            );

            $urgency = $analysis['urgency'] ?? 'low';
            $matchedRequestId = $analysis['matched_request_id'] ?? null;

            // Deduplication & Creation Check with safe database defaults
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
                    'raw_message'      => $rawText ?: 'Media upload submission',
                    'content'          => $analysis['translated_summary'] ?? ($rawText ?: 'Media upload submission'),
                    'upload_file_path' => $filePath,
                    'file_type'        => $fileType,
                    'urgency'          => $urgency,
                    'category'         => $analysis['category'] ?? 'General',
                    'latitude'         => $latitude ?? 0.0000,
                    'longitude'        => $longitude ?? 0.0000,
                ]);
            }

            // Automated Success Response back to Citizen
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
     * Dynamically match MP based on live coordinates or fallback database attributes.
     */
    private function resolveMpForUser(User $user, ?float $latitude, ?float $longitude): Mp
    {
        $lat = $latitude ?? $user->last_latitude;
        $lng = $longitude ?? $user->last_longitude;

        if ($lat && $lng) {
            $rawCandidates = $this->geocodingService->resolveLocationCandidates($lat, $lng, null);
            
            $cleanCandidates = collect($rawCandidates)
                ->flatMap(fn($item) => explode(',', $item))
                ->map(fn($item) => trim($item))
                ->filter(fn($item) => !in_array(strtolower($item), ['kenya', 'africa', 'africa/nairobi']))
                ->unique()
                ->values()
                ->all();

            foreach ($cleanCandidates as $areaName) {
                $constituency = Constituency::where('name', 'LIKE', '%' . $areaName . '%')
                    ->orWhereRaw('? LIKE CONCAT("%", name, "%")', [$areaName])
                    ->first();

                if ($constituency) {
                    $mp = Mp::where('constituency_name', 'LIKE', '%' . $constituency->name . '%')->first();
                    if ($mp) return $mp;
                }
            }
        }

        if (!empty($user->constituency_name)) {
            $mp = Mp::where('constituency_name', $user->constituency_name)->first();
            if ($mp) return $mp;
        }

        return Mp::first() ?? new Mp(['mp_id' => 1, 'mp_name' => 'Default MP']);
    }

    /**
     * Automated WhatsApp Outbound Message Dispatcher with Error Logging
     */
    private function sendWhatsAppMessage($to, $text)
    {
        $token = config('services.whatsapp.access_token');
        $phoneId = config('services.whatsapp.phone_number_id');
        $version = config('services.whatsapp.version', 'v21.0');

        try {
            $response = Http::withToken($token)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['body' => $text]
                ]);

            if (!$response->successful()) {
                Log::error("Meta WhatsApp Outbound Error Response for {$to}: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Failed to send outbound WhatsApp message to {$to}: " . $e->getMessage());
        }
    }

    /**
     * Download WhatsApp Media (2-Step Meta Graph API Flow)
     */
    private function downloadWhatsAppMedia(string $mediaId, string $extension): ?string 
    {
        try {
            $token = config('services.whatsapp.access_token');
            $version = config('services.whatsapp.version', 'v21.0');

            $mediaResponse = Http::withToken($token)
                ->get("https://graph.facebook.com/{$version}/{$mediaId}");

            if (!$mediaResponse->successful()) {
                Log::error("Failed to fetch media metadata for ID: {$mediaId}");
                return null;
            }

            $downloadUrl = $mediaResponse->json('url');
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