<?php

namespace App\Services;

use App\Models\ConstituencyRequest;

/**
 * Sprint F — equity / channel-bias check.
 *
 * Voice notes and vernacular (Sheng, Kiswahili, etc.) can produce lower Gemma
 * confidence than clean English text. Confidence must never reduce priority;
 * instead we flag risk, route to human review when needed, and apply a small
 * transparent fairness boost so uncertain multimodal submissions are not
 * silently deprioritized.
 */
class EquityCheckService
{
    public const CONFIDENCE_RISK = 0.70;

    public const FAIRNESS_BOOST_MAX = 5;

    /**
     * Evaluate and persist equity fields on a request.
     *
     * @param  array<string, mixed>  $analysis  Optional Gemma analysis payload
     */
    public function evaluate(ConstituencyRequest $request, array $analysis = []): ConstituencyRequest
    {
        if (! empty($analysis['detected_language']) && empty($request->detected_language)) {
            $request->detected_language = $this->normalizeLanguage((string) $analysis['detected_language']);
        }

        if (empty($request->detected_language) && ! empty($request->raw_message)) {
            $request->detected_language = $this->heuristicLanguage((string) $request->raw_message);
        }

        $reasons = [];

        $fileType = strtolower((string) ($request->file_type ?? 'text'));
        if (in_array($fileType, ['audio', 'voice', 'video'], true)) {
            $reasons[] = sprintf(
                '%s submission — transcription/understanding confidence can under-score vernacular speech relative to typed English.',
                ucfirst($fileType)
            );
        }

        $confidence = $request->confidence;
        if ($confidence !== null && (float) $confidence < self::CONFIDENCE_RISK) {
            $reasons[] = sprintf(
                'Low AI confidence (%.0f%%). Confidence is excluded from priority score and gated to human review instead of silent deprioritization.',
                (float) $confidence * 100
            );
        }

        $lang = strtolower((string) ($request->detected_language ?? 'en'));
        if ($lang !== '' && ! in_array($lang, ['en', 'english', 'unknown'], true)) {
            $reasons[] = sprintf(
                'Detected language/dialect: %s — multilingual submissions must not lose rank because of translation uncertainty.',
                $request->detected_language
            );
        }

        $flagged = $reasons !== [];
        $boost = 0;

        if ($flagged) {
            // Transparent fairness compensation when confidence is uncertain and/or channel is multimodal.
            $needsBoost = ($confidence !== null && (float) $confidence < self::CONFIDENCE_RISK)
                || in_array($fileType, ['audio', 'voice', 'video'], true)
                || (! in_array($lang, ['en', 'english', 'unknown', ''], true));

            if ($needsBoost) {
                $boost = self::FAIRNESS_BOOST_MAX;
                if ($confidence !== null) {
                    // Scale boost: lower confidence → closer to full boost
                    $boost = (int) max(2, round(self::FAIRNESS_BOOST_MAX * (1 - (float) $confidence)));
                    $boost = min(self::FAIRNESS_BOOST_MAX, $boost);
                }
                $reasons[] = sprintf(
                    'Applied fairness boost +%d to priority so channel/language uncertainty does not silently deprioritize this request.',
                    $boost
                );
            }

            // Prefer human review when confidence is shaky on equity-risk channels.
            if ($confidence !== null
                && (float) $confidence < self::CONFIDENCE_RISK
                && $request->status === ConstituencyRequest::STATUS_PENDING) {
                $request->status = ConstituencyRequest::STATUS_PENDING_REVIEW;
            }
        }

        $request->equity_flag = $flagged;
        $request->equity_reasons = $reasons;
        $request->equity_boost = $boost;
        $request->save();

        return $request->fresh();
    }

    /**
     * Apply stored equity_boost on top of a base priority score (0–100).
     */
    public function applyBoost(float $baseScore, ConstituencyRequest $request): float
    {
        $boost = (int) ($request->equity_boost ?? 0);
        if ($boost <= 0) {
            return max(0.0, min(100.0, $baseScore));
        }

        return max(0.0, min(100.0, round($baseScore + $boost, 2)));
    }

    public function summaryLine(ConstituencyRequest $request): ?string
    {
        if (! $request->equity_flag) {
            return null;
        }

        $reasons = $request->equity_reasons;
        if (is_array($reasons) && $reasons !== []) {
            return $reasons[0];
        }

        return 'Equity check flagged — review channel/language bias risk.';
    }

    private function normalizeLanguage(string $raw): string
    {
        $v = strtolower(trim($raw));

        return match (true) {
            str_contains($v, 'sheng') => 'sheng',
            str_contains($v, 'swahili') || $v === 'sw' || $v === 'kiswahili' => 'sw',
            str_contains($v, 'kikuyu') || str_contains($v, 'gikuyu') => 'kikuyu',
            str_contains($v, 'luo') || str_contains($v, 'dholuo') => 'luo',
            str_contains($v, 'luhya') => 'luhya',
            str_contains($v, 'kalenjin') => 'kalenjin',
            str_contains($v, 'kamba') || str_contains($v, 'kikamba') => 'kamba',
            str_contains($v, 'english') || $v === 'en' => 'en',
            str_contains($v, 'mix') => 'mixed',
            default => $v !== '' ? $v : 'unknown',
        };
    }

    private function heuristicLanguage(string $text): string
    {
        $t = mb_strtolower($text);

        $shengHints = ['msee', 'manze', 'poa', 'noma', 'maze', 'bro', 'dame', 'doo', 'kaa'];
        $swHints = ['maji', 'barabara', 'shule', 'hospitalsi', 'tafadhali', 'sana', 'hakuna', 'nina', 'tunaomba', 'barabara', 'taarifa'];

        $shengHits = 0;
        foreach ($shengHints as $w) {
            if (preg_match('/\b'.preg_quote($w, '/').'\b/u', $t)) {
                $shengHits++;
            }
        }
        if ($shengHits >= 2) {
            return 'sheng';
        }

        $swHits = 0;
        foreach ($swHints as $w) {
            if (preg_match('/\b'.preg_quote($w, '/').'\b/u', $t)) {
                $swHits++;
            }
        }
        if ($swHits >= 2) {
            return 'sw';
        }

        return 'unknown';
    }
}
