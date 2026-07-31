<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMessagingService
{
    /**
     * Automated WhatsApp Outbound Message Dispatcher with Error Logging.
     */
    public function sendMessage(string $to, string $text): void
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
}