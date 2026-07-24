<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Mp;
use App\Models\ConstituencyRequest;
use App\Services\Gemma4Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected Gemma4Service $gemmaService;

    public function __construct(Gemma4Service $gemmaService)
    {
        $this->gemmaService = $gemmaService;
    }

    /**
     * 1. META WEBHOOK VERIFICATION HANDSHAKE (GET Request)
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
     * 2. INCOMING MESSAGE HANDLER (POST Request)
     */
    public function handleWebhook(Request $request)
    {
        // Extract WhatsApp ID and Message Object
        $phone = $request->input('entry.0.changes.0.value.contacts.0.wa_id');
        $messageData = $request->input('entry.0.changes.0.value.messages.0');

        if (!$phone || !$messageData) {
            return response()->json(['status' => 'ignored'], 200);
        }

        // Retrieve or create User (Citizen)
        $user = User::firstOrCreate(['phone_number' => $phone]);

        // 🛑 Rate Limit Check (1 request every 2 hours)
        $lastRequest = ConstituencyRequest::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(2))
            ->latest()
            ->first();

        if ($lastRequest) {
            $nextAllowed = $lastRequest->created_at->addHours(2)->diffForHumans();
            $this->sendWhatsAppMessage($phone, "You can only send one request every 2 hours. Next request available: {$nextAllowed}.");
            return response()->json(['status' => 'rate_limited'], 200);
        }

        // Process File / Text Payload
        $filePath = null;
        $fileType = 'text';
        $rawText = $messageData['text']['body'] ?? '';

        if (isset($messageData['image'])) {
            $fileType = 'image';
            $filePath = $this->downloadWhatsAppMedia($messageData['image']['id'], 'jpg');
        } elseif (isset($messageData['audio'])) {
            $fileType = 'audio';
            $filePath = $this->downloadWhatsAppMedia($messageData['audio']['id'], 'ogg');
        }

        // Fetch assigned MP for the constituency (defaults to active MP)
        $assignedMp = Mp::first(); 

        // 🤖 Gemma 4 Processing Engine
        $analysis = $this->gemmaService->processRequestData(
            $rawText, 
            $filePath, 
            $fileType, 
            $user->is_within_constituency ?? true
        );

        // Deduplication & Similarity Counter
        $similarRequest = ConstituencyRequest::where('category', $analysis['category'] ?? 'General')
            ->where('urgency', $analysis['urgency'] ?? 'low')
            ->where('mp_id', $assignedMp->id ?? 1)
            ->where('created_at', '>=', now()->subDays(3))
            ->first();

        if ($similarRequest) {
            $similarRequest->increment('similar_count');
        } else {
            ConstituencyRequest::create([
                'user_id'          => $user->id, // Correct foreign key reference
                'mp_id'            => $assignedMp->id ?? 1,
                'raw_message'      => $rawText,
                'content'          => $analysis['translated_summary'] ?? $rawText,
                'upload_file_path' => $filePath,
                'file_type'        => $fileType,
                'urgency'          => $analysis['urgency'] ?? 'low',
                'category'         => $analysis['category'] ?? 'General',
                'similar_count'    => 1
            ]);
        }

        // Update user timestamp
        $user->touch(); // updates updated_at

        // Send Success Reply to Citizen
        $this->sendWhatsAppMessage(
            $phone, 
            "Your request has been received successfully and is being processed, you can send another request after the next two hours."
        );

        return response()->json(['status' => 'success'], 200);
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