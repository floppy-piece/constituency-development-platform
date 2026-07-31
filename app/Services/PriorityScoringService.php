<?php

namespace App\Services;

use App\Models\Constituency;
use App\Models\ConstituencyRequest;
use App\Models\Mp;
use Illuminate\Support\Facades\Schema;

class PriorityScoringService
{
    public function __construct(
        private readonly EquityCheckService $equityCheck,
    ) {}

    /**
     * Transparent AI recommendation score (0–100).
     * Confidence is intentionally excluded — it only gates human review / equity flag.
     *
     * score = 0.35*urgency + 0.25*cluster_size + 0.20*equity + 0.20*population_proxy
     *        + optional fairness boost (Sprint F)
     */
    public function score(ConstituencyRequest $request): ConstituencyRequest
    {
        $request->loadMissing(['cluster', 'mp', 'ward.constituency']);

        // Re-evaluate channel/language bias before scoring so boost is current.
        $this->equityCheck->evaluate($request);
        $request->refresh();

        $urgencyRaw = $request->urgency_score;
        if ($urgencyRaw === null) {
            $urgencyRaw = match (strtolower((string) $request->urgency)) {
                'high' => 8,
                'medium' => 5,
                default => 2,
            };
        }
        $urgencyComponent = $this->clamp((float) $urgencyRaw * 10);

        $reportCount = (int) ($request->cluster?->report_count ?? $request->similar_count ?? 1);
        $clusterComponent = $this->clamp(($reportCount / 20) * 100);

        $povertyRate = $this->resolvePovertyRate($request);
        $equityComponent = $this->clamp($povertyRate);

        $populationComponent = $this->clamp($this->resolvePopulationProxy($request, $reportCount));

        $baseScore = round(
            (0.35 * $urgencyComponent)
            + (0.25 * $clusterComponent)
            + (0.20 * $equityComponent)
            + (0.20 * $populationComponent),
            2
        );

        $score = $this->equityCheck->applyBoost($baseScore, $request);
        $boost = (int) ($request->equity_boost ?? 0);

        $reason = sprintf(
            'Ranked by urgency %d/10, %d related reports, equity (poverty %.0f%%), and local demand proxy %.0f.',
            (int) $urgencyRaw,
            $reportCount,
            $povertyRate,
            $populationComponent
        );

        if ($boost > 0) {
            $reason .= sprintf(' Fairness boost +%d applied (confidence excluded from score).', $boost);
        }

        $request->priority_score = $score;
        $request->priority_factors = [
            'urgency' => (int) $urgencyRaw,
            'urgency_weight' => 0.35,
            'urgency_component' => round($urgencyComponent, 2),
            'reports' => $reportCount,
            'cluster_weight' => 0.25,
            'cluster_component' => round($clusterComponent, 2),
            'poverty_rate_percentage' => round($povertyRate, 2),
            'equity_weight' => 0.20,
            'equity_component' => round($equityComponent, 2),
            'population_proxy' => round($populationComponent, 2),
            'population_weight' => 0.20,
            'confidence_excluded' => true,
            'confidence' => $request->confidence,
            'equity_flag' => (bool) $request->equity_flag,
            'equity_boost' => $boost,
            'equity_reasons' => $request->equity_reasons,
            'detected_language' => $request->detected_language,
            'base_score' => $baseScore,
            'reason' => $reason,
            'formula' => '0.35*urgency + 0.25*cluster + 0.20*equity + 0.20*population (+ fairness boost if flagged)',
        ];
        $request->save();

        return $request->fresh();
    }

    public function rescoreOpenForMp(int $mpId): int
    {
        $count = 0;

        ConstituencyRequest::with(['cluster', 'mp', 'ward.constituency'])
            ->where('mp_id', $mpId)
            ->where('status', '!=', ConstituencyRequest::STATUS_RESOLVED)
            ->orderBy('request_id')
            ->each(function (ConstituencyRequest $request) use (&$count) {
                $this->score($request);
                $count++;
            });

        return $count;
    }

    public function prioritiesAreLocked(Mp $mp): bool
    {
        return $mp->priorities_locked_at !== null;
    }

    /**
     * Effective display rank: MP override first, else order by AI priority_score.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rankedListForMp(int $mpId): array
    {
        $items = ConstituencyRequest::with(['cluster.ward:ward_id,name', 'user:user_id,phone_number'])
            ->where('mp_id', $mpId)
            ->where('status', '!=', ConstituencyRequest::STATUS_RESOLVED)
            ->get()
            ->sort(function (ConstituencyRequest $a, ConstituencyRequest $b) {
                $aRank = $a->mp_priority_rank;
                $bRank = $b->mp_priority_rank;

                if ($aRank !== null && $bRank !== null) {
                    return $aRank <=> $bRank;
                }
                if ($aRank !== null) {
                    return -1;
                }
                if ($bRank !== null) {
                    return 1;
                }

                return ((float) $b->priority_score) <=> ((float) $a->priority_score)
                    ?: ($b->request_id <=> $a->request_id);
            })
            ->values();

        $ranked = [];
        foreach ($items as $index => $req) {
            $displayRank = $index + 1;
            $cluster = $req->cluster;

            $ranked[] = [
                'request_id' => $req->request_id,
                'display_rank' => $displayRank,
                'ai_rank_source' => $req->mp_priority_rank !== null ? 'mp_override' : 'ai_score',
                'mp_priority_rank' => $req->mp_priority_rank,
                'priority_score' => (float) ($req->priority_score ?? 0),
                'priority_factors' => $req->priority_factors,
                'estimated_cost_kes' => $req->estimated_cost_kes ? (int) $req->estimated_cost_kes : null,
                'cost_source' => $req->cost_source,
                'cost_rationale' => $req->cost_rationale,
                'override_reason' => $req->override_reason,
                'overridden_at' => $req->overridden_at?->toIso8601String(),
                'urgency' => $req->urgency,
                'urgency_score' => $req->urgency_score,
                'category' => $req->category ?? 'General',
                'content' => $req->content ?? $req->raw_message,
                'status' => $req->status,
                'confidence' => $req->confidence,
                'detected_language' => $req->detected_language,
                'equity_flag' => (bool) $req->equity_flag,
                'equity_reasons' => $req->equity_reasons,
                'equity_boost' => (int) ($req->equity_boost ?? 0),
                'file_type' => $req->file_type,
                'source_channel' => $req->source_channel,
                'similar_count' => $cluster?->report_count ?? ($req->similar_count ?? 1),
                'cluster_summary' => $cluster?->summaryLine($cluster->ward?->name),
                'evaluation_thoughts' => $req->evaluation_thoughts,
                'suggested_fix' => $req->suggested_fix,
                'user' => [
                    'phone_number' => $req->user?->phone_number ?? 'N/A',
                ],
            ];
        }

        return $ranked;
    }

    private function resolvePovertyRate(ConstituencyRequest $request): float
    {
        $constituency = $request->ward?->constituency;

        if (! $constituency && $request->mp?->constituency_name) {
            $constituency = Constituency::query()
                ->where('name', $request->mp->constituency_name)
                ->first();
        }

        if ($constituency && Schema::hasColumn('constituencies', 'poverty_rate_percentage')) {
            $rate = (float) ($constituency->getAttribute('poverty_rate_percentage') ?? 0);
            if ($rate > 0) {
                return $rate;
            }
        }

        return 50.0;
    }

    private function resolvePopulationProxy(ConstituencyRequest $request, int $reportCount): float
    {
        $constituency = $request->ward?->constituency;

        if (! $constituency && $request->mp?->constituency_name) {
            $constituency = Constituency::query()
                ->where('name', $request->mp->constituency_name)
                ->first();
        }

        if ($constituency && Schema::hasColumn('constituencies', 'total_population')) {
            $population = (float) ($constituency->getAttribute('total_population') ?? 0);
            if ($population > 0) {
                return min(100, log10(max(10, $population)) * 25);
            }
        }

        return min(100, $reportCount * 8);
    }

    private function clamp(float $value): float
    {
        return max(0.0, min(100.0, $value));
    }
}
