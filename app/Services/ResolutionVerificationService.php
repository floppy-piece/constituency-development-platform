<?php

namespace App\Services;

use App\Models\ConstituencyRequest;
use App\Models\User;
use App\Services\Telegram\TelegramMessagingService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Support\Facades\Log;

class ResolutionVerificationService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_EXPIRED = 'expired';

    public const EXPIRY_DAYS = 7;

    public function __construct(
        private readonly TelegramMessagingService $telegram,
        private readonly WhatsAppMessagingService $whatsapp,
    ) {}

    /**
     * After MP marks resolved: ask the original citizen to confirm via their channel.
     */
    public function requestCitizenVerification(ConstituencyRequest $request): bool
    {
        $request->loadMissing('user');

        $user = $request->user;
        if (! $user || empty($user->phone_number)) {
            Log::warning("Verification skipped: request {$request->request_id} has no citizen contact.");

            return false;
        }

        $channel = $this->resolveChannel($request, $user);
        $summary = $this->shortSummary($request);

        $message = "✅ Your MP has marked this issue as *resolved*:\n"
            . "\"{$summary}\"\n\n"
            . "Please confirm the outcome:\n"
            . "• Reply *YES* if it is fixed\n"
            . "• Reply *NO* if the problem remains\n"
            . "• Or send a *photo* of the current situation\n\n"
            . "Your reply helps keep constituency accountability honest.";

        // Plain text for WhatsApp (no Markdown emphasis required)
        $whatsappMessage = str_replace(['*', '_'], '', $message);

        $sent = false;
        if ($channel === 'telegram') {
            $sent = $this->telegram->sendMessage($user->phone_number, $message);
        } else {
            $this->whatsapp->sendMessage($user->phone_number, $whatsappMessage);
            $sent = true;
        }

        $request->source_channel = $request->source_channel ?: $channel;
        $request->verification_status = self::STATUS_PENDING;
        $request->verification_requested_at = now();
        $request->verified_at = null;
        $request->verification_note = null;
        $request->save();

        Log::info("Verification requested for request {$request->request_id} via {$channel}. sent=".($sent ? '1' : '0'));

        return $sent;
    }

    /**
     * Latest open verification pending for this citizen (not expired).
     */
    public function findPendingForUser(int $userId): ?ConstituencyRequest
    {
        $this->expireStaleForUser($userId);

        return ConstituencyRequest::query()
            ->where('user_id', $userId)
            ->where('verification_status', self::STATUS_PENDING)
            ->where('status', ConstituencyRequest::STATUS_RESOLVED)
            ->orderByDesc('verification_requested_at')
            ->first();
    }

    /**
     * Handle citizen YES / NO / photo reply for a pending verification.
     *
     * @return array{handled:bool,outcome:?string,request:?ConstituencyRequest}
     */
    public function handleCitizenReply(
        User $user,
        string $rawText,
        ?string $filePath = null,
        ?string $fileType = null,
        ?string $channel = null,
    ): array {
        $pending = $this->findPendingForUser((int) $user->getKey());

        if (! $pending) {
            return ['handled' => false, 'outcome' => null, 'request' => null];
        }

        $text = trim(mb_strtolower($rawText));
        $hasPhoto = $filePath && in_array($fileType, ['image', 'photo'], true);

        $outcome = null;
        if ($this->isAffirmative($text) || ($hasPhoto && $text === '')) {
            $outcome = self::STATUS_CONFIRMED;
        } elseif ($this->isNegative($text)) {
            $outcome = self::STATUS_DISPUTED;
        } elseif ($hasPhoto) {
            // Photo with optional caption that isn't clearly NO → treat as confirmed evidence
            $outcome = $this->isNegative($text) ? self::STATUS_DISPUTED : self::STATUS_CONFIRMED;
        }

        if ($outcome === null) {
            $this->nudgeClarify($pending, $user, $channel);

            return ['handled' => true, 'outcome' => 'clarification', 'request' => $pending];
        }

        $pending->verification_status = $outcome;
        $pending->verified_at = now();
        $pending->verification_note = $rawText !== '' ? $rawText : ($hasPhoto ? 'Citizen sent photo evidence.' : null);
        if ($hasPhoto) {
            $pending->verification_file_path = $filePath;
        }

        if ($outcome === self::STATUS_DISPUTED) {
            // Re-open for MP follow-up — accountability loop
            $pending->status = ConstituencyRequest::STATUS_IN_PROGRESS;
            $pending->resolved_at = null;
        }

        $pending->save();

        $this->sendOutcomeAck($pending, $user, $outcome, $channel);

        return ['handled' => true, 'outcome' => $outcome, 'request' => $pending->fresh()];
    }

    public function expireStaleForUser(int $userId): int
    {
        return ConstituencyRequest::query()
            ->where('user_id', $userId)
            ->where('verification_status', self::STATUS_PENDING)
            ->where('verification_requested_at', '<', now()->subDays(self::EXPIRY_DAYS))
            ->update(['verification_status' => self::STATUS_EXPIRED]);
    }

    private function resolveChannel(ConstituencyRequest $request, User $user): string
    {
        if (in_array($request->source_channel, ['telegram', 'whatsapp'], true)) {
            return $request->source_channel;
        }

        // Heuristic: Telegram chat ids are typically shorter numeric strings without leading country code length.
        $contact = (string) $user->phone_number;
        if (preg_match('/^\d{6,12}$/', $contact) && ! str_starts_with($contact, '254')) {
            return 'telegram';
        }

        return 'whatsapp';
    }

    private function shortSummary(ConstituencyRequest $request): string
    {
        $text = (string) ($request->content ?? $request->raw_message ?? 'Your reported issue');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return mb_strlen($text) > 160 ? mb_substr($text, 0, 157).'...' : $text;
    }

    private function isAffirmative(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        $patterns = [
            '/^(yes|y|yeah|yep|ok|okay|ndiyo|sawa|fixed|confirmed|done|resolved|imewekwa|imetengenezwa)\b/u',
            '/\b(it is fixed|all good|problem solved|issue resolved)\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    private function isNegative(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        $patterns = [
            '/^(no|n|nope|hapana|bado)\b/u',
            '/\b(not fixed|still broken|still bad|not resolved|problem remains|haijarekebishwa)\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    private function nudgeClarify(ConstituencyRequest $request, User $user, ?string $channel): void
    {
        $channel = $channel ?: $this->resolveChannel($request, $user);
        $msg = "Please reply YES (fixed), NO (still a problem), or send a photo so we can verify resolution of request #{$request->request_id}.";

        if ($channel === 'telegram') {
            $this->telegram->sendMessage($user->phone_number, $msg);
        } else {
            $this->whatsapp->sendMessage($user->phone_number, $msg);
        }
    }

    private function sendOutcomeAck(ConstituencyRequest $request, User $user, string $outcome, ?string $channel): void
    {
        $channel = $channel ?: $this->resolveChannel($request, $user);

        if ($outcome === self::STATUS_CONFIRMED) {
            $msg = "Thank you — resolution of request #{$request->request_id} is confirmed. Your feedback strengthens accountability.";
        } else {
            $msg = "Noted — request #{$request->request_id} has been reopened for your MP because you reported it is not fixed. Thank you for verifying.";
        }

        if ($channel === 'telegram') {
            $this->telegram->sendMessage($user->phone_number, $msg);
        } else {
            $this->whatsapp->sendMessage($user->phone_number, $msg);
        }
    }
}
