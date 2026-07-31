<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramMessagingService
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Send a text message back to a Telegram chat user.
     */
    public function sendMessage(string|int $chatId, string $message, ?string $parseMode = 'Markdown'): bool
    {
        if (empty($this->botToken)) {
            Log::error('Telegram Bot Token is missing from configuration.');
            return false;
        }

        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("Failed to send Telegram message to chat {$chatId}: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("Telegram Messaging Exception: " . $e->getMessage());
            return false;
        }
    }
}