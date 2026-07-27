<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppFileService
{
    /**
     * Download WhatsApp Media via 2-Step Meta Graph API Flow.
     */
    public function downloadMedia(string $mediaId, string $extension): ?string 
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