<?php

namespace App\Services;

use App\Models\ConstituencyRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function send(string|int $chatId, string $text): bool
    {
        $token = config('services.telegram.bot_token');

        if (empty($token) || empty($chatId)) {
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => $text,
            ]);

            if (! $response->successful()) {
                Log::warning('Telegram sendMessage failed', [
                    'chat_id' => $chatId,
                    'body'    => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('TelegramNotifier exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Notify the citizen who submitted a request about a status transition.
     * Telegram chat id is stored in users.phone_number for Telegram-origin users.
     */
    public function notifyRequestStatus(ConstituencyRequest $request, string $status): void
    {
        $user = $request->relationLoaded('user')
            ? $request->user
            : User::find($request->user_id);

        if (! $user || empty($user->phone_number)) {
            return;
        }

        $chatId = (string) $user->phone_number;

        // Skip obvious international phone strings (WhatsApp). Telegram chat ids are numeric user ids.
        if (str_starts_with($chatId, '+') || preg_match('/^254\d{9}$/', $chatId)) {
            return;
        }

        $message = $this->messageForStatus($request, $status);

        if ($message) {
            $this->send($chatId, $message);
        }
    }

    public function messageForStatus(ConstituencyRequest $request, string $status): ?string
    {
        $id = $request->request_id;
        $category = $request->category ?? 'General';
        $urgency = $request->urgency ?? 'low';

        return match ($status) {
            'received' => "We received your report (#{$id}). Our system is processing it.",
            ConstituencyRequest::STATUS_PENDING,
            'categorized' => "Your report (#{$id}) has been categorized as {$category} with {$urgency} urgency. It is now with your MP's office.",
            ConstituencyRequest::STATUS_IN_PROGRESS => "Update on report #{$id}: your MP's office is now working on this issue.",
            ConstituencyRequest::STATUS_RESOLVED => "Good news — report #{$id} has been marked resolved. Thank you for helping improve your constituency.",
            ConstituencyRequest::STATUS_PENDING_REVIEW => "We received your report (#{$id}). It needs a quick human review before routing — we will update you shortly.",
            default => null,
        };
    }
}
