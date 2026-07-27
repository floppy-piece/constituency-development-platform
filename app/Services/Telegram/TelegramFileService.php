<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramFileService
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Extract file ID from incoming Telegram payload and download it locally.
     */
    public function downloadTelegramFile(array $payload): ?array
    {
        $message = $payload['message'] ?? $payload['edited_message'] ?? [];
        
        $fileId = null;
        $fileType = 'unknown';

        if (isset($message['photo'])) {
            // Telegram sends an array of photo sizes; grab the highest resolution (last element)
            $photo = end($message['photo']);
            $fileId = $photo['file_id'] ?? null;
            $fileType = 'image';
        } elseif (isset($message['document'])) {
            $fileId = $message['document']['file_id'] ?? null;
            $fileType = 'document';
        } elseif (isset($message['audio'])) {
            $fileId = $message['audio']['file_id'] ?? null;
            $fileType = 'audio';
        } elseif (isset($message['video'])) {
            $fileId = $message['video']['file_id'] ?? null;
            $fileType = 'video';
        } elseif (isset($message['voice'])) {
            $fileId = $message['voice']['file_id'] ?? null;
            $fileType = 'voice';
        }

        if (!$fileId) {
            return null;
        }

        $localPath = $this->downloadFile($fileId, $fileType);

        if (!$localPath) {
            return null;
        }

        return [
            'path' => $localPath,
            'type' => $fileType,
        ];
    }

    /**
     * Call Telegram API to retrieve file path and download binary contents to local storage.
     */
    public function downloadFile(string $fileId, string $fileType): ?string
    {
        if (empty($this->botToken)) {
            Log::error('Telegram Bot Token is missing for file download.');
            return null;
        }

        try {
            // 1. Get file path from Telegram
            $response = Http::get("{$this->apiUrl}/getFile", [
                'file_id' => $fileId,
            ]);

            if (!$response->successful() || !$response->json('ok')) {
                Log::error("Failed to retrieve Telegram file path for ID {$fileId}: " . $response->body());
                return null;
            }

            $filePath = $response->json('result.file_path');
            $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";

            // 2. Download binary content
            $fileBinary = Http::get($fileUrl);

            if (!$fileBinary->successful()) {
                Log::error("Failed to download binary file from URL: {$fileUrl}");
                return null;
            }

            // 3. Save to local storage (storage/app/public/telegram_attachments)
            $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'bin';
            $fileName = 'tg_' . uniqid() . '.' . $extension;
            $storagePath = 'telegram_attachments/' . $fileName;

            Storage::disk('public')->put($storagePath, $fileBinary->body());

            return Storage::disk('public')->path($storagePath);

        } catch (\Throwable $e) {
            Log::error("Telegram File Service Exception: " . $e->getMessage());
            return null;
        }
    }
}