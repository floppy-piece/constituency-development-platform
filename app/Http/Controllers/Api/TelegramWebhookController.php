<?php

namespace App\Http\Controllers\Api;

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

class TelegramWebhookController extends Controller
{
    protected Gemma4Service $gemmaService;

    public function __construct(Gemma4Service $gemmaService)
    {
        $this->gemmaService = $gemmaService;
    }

    public function handleWebhook(Request $request)
    {
        // Secret Token Validation
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($secret !== config('services.telegram.secret')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = $request->input('message');
        if (! $message) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $chatId = $message['chat']['id'] ?? null;
        if (! $chatId) {
            return response()->json(['status' => 'no_chat_id'], 200);
        }

        try {
            $telegramUserId = (string) $message['from']['id'];

            // Link or create user by telegram ID
            $user = User::firstOrCreate(
                ['phone_number' => $telegramUserId],
                ['whatsapp_linked_at' => now()]
            );

            $userId = $user->getKey();

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

            // Extract native Telegram coordinates if user attached location
            $latitude = $message['location']['latitude'] ?? null;
            $longitude = $message['location']['longitude'] ?? null;

            // Resolve correct MP dynamically based on user/location context
            $assignedMp = $this->resolveMpForUser($user, $latitude, $longitude);
            $mpId = $assignedMp->mp_id ?? $assignedMp->id ?? 1;

            // Extract content types (Text, Image, Audio, Video)
            $filePath = null;
            $fileType = 'text';
            $rawText = $message['text'] ?? ($message['caption'] ?? '');

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
            } elseif (isset($message['video_note'])) { // Telegram video notes (round videos)
                $fileType = 'video';
                $fileId = $message['video_note']['file_id'];
                $filePath = $this->downloadTelegramFile($fileId, 'mp4');
            }

            // Retrieve existing requests for LLM deduplication comparison
            $recentRequests = ConstituencyRequest::where('mp_id', $mpId)
                ->where('created_at', '>=', now()->subDays(3))
                ->latest()
                ->take(30)
                ->get(['request_id', 'raw_message', 'content', 'created_at']);

            // Send payload to Gemma 4 (supports text, image, audio, video)
            $analysis = $this->gemmaService->processRequestData(
                $rawText,
                $filePath,
                $fileType,
                $user->is_within_constituency ?? true,
                $recentRequests
            );

            $urgency = $analysis['urgency'] ?? 'low';
            $matchedRequestId = $analysis['matched_request_id'] ?? null;

            // Handle duplicate request scenario
            if ($matchedRequestId) {
                $similarRequest = ConstituencyRequest::where('request_id', $matchedRequestId)
                    ->where('mp_id', $mpId)
                    ->first();

                if ($similarRequest) {
                    $similarRequest->touch();
                }
            } else {
                // Save new record with resolved MP ID & Coordinates
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

            // Send user confirmation with assigned MP context
            $mpName = $assignedMp->mp_name ?? 'your MP';
            $constituencyName = $assignedMp->constituency_name ?? $assignedMp->constituency ?? '';
            
            $confirmationMessage = "Your request has been forwarded to {$mpName}";
            if (!empty($constituencyName)) {
                $confirmationMessage .= " ({$constituencyName})";
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
                '⚠️ Sorry, your request failed to process due to a system error. Please try again after a few hours, thank you 🙂.'
            );

            return response()->json(['status' => 'error', 'message' => 'Processed with error'], 200);
        }
    }

    /**
     * Dynamically match MP based on constituency, location, or default fallback.
     */
    private function resolveMpForUser(User $user, ?float $latitude, ?float $longitude): Mp
    {
        if (!empty($user->constituency_name)) {
            $mp = Mp::where('constituency_name', $user->constituency_name)->first();
            if ($mp) return $mp;
        }

        return Mp::first() ?? new Mp(['mp_id' => 1, 'mp_name' => 'Default MP']);
    }

    private function sendTelegramMessage($chatId, $text)
    {
        $token = config('services.telegram.bot_token');
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
    }

    private function downloadTelegramFile($fileId, $extension): ?string
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