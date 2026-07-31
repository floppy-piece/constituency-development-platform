<?php

namespace App\Services;

use App\Models\ConstituencyRequest;
use App\Models\Mp;
use Illuminate\Support\Collection;

class BudgetOptimizerService
{
    public const MAX_GEMMA_COST_CALLS = 8;

    public function __construct(
        private readonly Gemma4Service $gemma,
    ) {}

    /**
     * Persist a cost estimate for one request.
     */
    public function ensureCost(ConstituencyRequest $request, bool $preferGemma = false): ConstituencyRequest
    {
        if ($request->estimated_cost_kes && ! $preferGemma) {
            return $request;
        }

        $request->loadMissing(['cluster']);

        if ($preferGemma) {
            $estimate = $this->gemma->estimateProjectCost([
                'content' => $request->content ?? $request->raw_message,
                'category' => $request->category ?? 'General',
                'urgency' => $request->urgency,
                'urgency_score' => $request->urgency_score,
                'reports' => (int) ($request->cluster?->report_count ?? $request->similar_count ?? 1),
                'suggested_fix' => $request->suggested_fix,
            ]);
            $source = (($estimate['confidence'] ?? 0) >= 0.5) ? 'gemma' : 'heuristic';
        } else {
            $estimate = $this->heuristicOnly($request);
            $source = 'heuristic';
        }

        $request->estimated_cost_kes = (int) $estimate['estimated_cost_kes'];
        $request->cost_source = $source;
        $request->cost_rationale = (string) ($estimate['cost_rationale'] ?? '');
        $request->save();

        return $request->fresh();
    }

    /**
     * Fill missing costs for open requests. Uses Gemma for up to MAX_GEMMA_COST_CALLS, then heuristic.
     *
     * @return int Number of requests that received a (new/updated) cost
     */
    public function ensureCostsForMp(int $mpId, bool $refreshAll = false): int
    {
        $updated = 0;
        $gemmaCalls = 0;

        ConstituencyRequest::with(['cluster'])
            ->where('mp_id', $mpId)
            ->where('status', '!=', ConstituencyRequest::STATUS_RESOLVED)
            ->when(! $refreshAll, fn ($q) => $q->whereNull('estimated_cost_kes'))
            ->orderByDesc('priority_score')
            ->orderBy('request_id')
            ->each(function (ConstituencyRequest $request) use (&$updated, &$gemmaCalls, $refreshAll) {
                $useGemma = $refreshAll || $request->estimated_cost_kes === null;

                if ($useGemma && $gemmaCalls < self::MAX_GEMMA_COST_CALLS) {
                    $context = [
                        'content' => $request->content ?? $request->raw_message,
                        'category' => $request->category ?? 'General',
                        'urgency' => $request->urgency,
                        'urgency_score' => $request->urgency_score,
                        'reports' => (int) ($request->cluster?->report_count ?? $request->similar_count ?? 1),
                        'suggested_fix' => $request->suggested_fix,
                    ];
                    $estimate = $this->gemma->estimateProjectCost($context);
                    $gemmaCalls++;
                    $source = (($estimate['confidence'] ?? 0) >= 0.5) ? 'gemma' : 'heuristic';
                } else {
                    $estimate = $this->heuristicOnly($request);
                    $source = 'heuristic';
                }

                $request->estimated_cost_kes = (int) $estimate['estimated_cost_kes'];
                $request->cost_source = $source;
                $request->cost_rationale = (string) ($estimate['cost_rationale'] ?? '');
                $request->save();
                $updated++;
            });

        return $updated;
    }

    /**
     * 0/1 knapsack: maximize total priority_score under available_budget_kes.
     *
     * @return array<string, mixed>
     */
    public function proposeBundle(int $mpId, int $budgetKes, bool $refreshCosts = false): array
    {
        if ($budgetKes <= 0) {
            return [
                'available_budget_kes' => $budgetKes,
                'selected' => [],
                'deferred' => [],
                'total_cost_kes' => 0,
                'total_impact_score' => 0,
                'remaining_budget_kes' => 0,
                'summary' => 'Set an available budget greater than zero to propose a fundable bundle.',
                'method' => 'none',
            ];
        }

        $this->ensureCostsForMp($mpId, $refreshCosts);

        /** @var Collection<int, ConstituencyRequest> $open */
        $open = ConstituencyRequest::with(['cluster.ward:ward_id,name', 'user:user_id,phone_number'])
            ->where('mp_id', $mpId)
            ->where('status', '!=', ConstituencyRequest::STATUS_RESOLVED)
            ->get()
            ->filter(fn (ConstituencyRequest $r) => (int) $r->estimated_cost_kes > 0)
            ->values();

        $items = $open->map(function (ConstituencyRequest $r) {
            return [
                'request' => $r,
                'cost' => (int) $r->estimated_cost_kes,
                'value' => max(0.01, (float) ($r->priority_score ?? 0)),
            ];
        })->all();

        $n = count($items);
        $selectedIds = [];

        // Exact DP when N is small; otherwise greedy by value/cost then one-pass improve.
        if ($n > 0 && $n <= 22 && $budgetKes <= 100_000_000) {
            $selectedIds = $this->knapsackExact($items, $budgetKes);
            $method = 'knapsack_exact';
        } else {
            $selectedIds = $this->knapsackGreedy($items, $budgetKes);
            $method = 'knapsack_greedy';
        }

        $selectedSet = array_flip($selectedIds);
        $selected = [];
        $deferred = [];
        $totalCost = 0;
        $totalImpact = 0.0;
        $totalReports = 0;

        foreach ($open as $req) {
            $payload = $this->requestPayload($req);
            if (isset($selectedSet[$req->request_id])) {
                $selected[] = $payload;
                $totalCost += (int) $req->estimated_cost_kes;
                $totalImpact += (float) ($req->priority_score ?? 0);
                $totalReports += (int) ($req->cluster?->report_count ?? $req->similar_count ?? 1);
            } else {
                $deferred[] = $payload;
            }
        }

        // Keep selected ordered by priority_score desc for readability.
        usort($selected, fn ($a, $b) => ($b['priority_score'] <=> $a['priority_score']));
        usort($deferred, fn ($a, $b) => ($b['priority_score'] <=> $a['priority_score']));

        $remaining = max(0, $budgetKes - $totalCost);
        $summary = sprintf(
            'AI proposes funding %d of %d open requests (Ksh %s) to maximize impact score %.1f under a Ksh %s cap — covering ~%d related citizen reports. Ksh %s remains unallocated. This is a recommendation; the MP decides.',
            count($selected),
            $open->count(),
            number_format($totalCost),
            $totalImpact,
            number_format($budgetKes),
            $totalReports,
            number_format($remaining)
        );

        return [
            'available_budget_kes' => $budgetKes,
            'selected' => $selected,
            'deferred' => $deferred,
            'total_cost_kes' => $totalCost,
            'total_impact_score' => round($totalImpact, 2),
            'remaining_budget_kes' => $remaining,
            'reports_covered' => $totalReports,
            'summary' => $summary,
            'method' => $method,
            'banner' => 'Maximize impact under budget — AI recommends a fundable bundle; MP decides.',
        ];
    }

    public function setAvailableBudget(Mp $mp, ?int $budgetKes): Mp
    {
        $mp->available_budget_kes = $budgetKes;
        $mp->save();

        return $mp->fresh();
    }

    /**
     * @param  array<int, array{request:ConstituencyRequest,cost:int,value:float}>  $items
     * @return array<int, int> request_ids
     */
    private function knapsackExact(array $items, int $budget): array
    {
        // Scale costs down so DP table stays small (KES → units of 10,000).
        $scale = 10_000;
        $capacity = intdiv($budget, $scale);
        if ($capacity <= 0) {
            return [];
        }

        $n = count($items);
        $scaledCosts = [];
        foreach ($items as $i => $item) {
            $scaledCosts[$i] = max(1, (int) ceil($item['cost'] / $scale));
        }

        // dp[w] = best value; keep choice reconstruction via prev pointers is heavy —
        // use classic 2D for n<=22.
        $dp = array_fill(0, $n + 1, array_fill(0, $capacity + 1, 0.0));

        for ($i = 1; $i <= $n; $i++) {
            $c = $scaledCosts[$i - 1];
            $v = $items[$i - 1]['value'];
            for ($w = 0; $w <= $capacity; $w++) {
                $dp[$i][$w] = $dp[$i - 1][$w];
                if ($c <= $w) {
                    $dp[$i][$w] = max($dp[$i][$w], $dp[$i - 1][$w - $c] + $v);
                }
            }
        }

        $selected = [];
        $w = $capacity;
        for ($i = $n; $i >= 1; $i--) {
            if ($dp[$i][$w] !== $dp[$i - 1][$w]) {
                $selected[] = $items[$i - 1]['request']->request_id;
                $w -= $scaledCosts[$i - 1];
            }
        }

        return array_reverse($selected);
    }

    /**
     * @param  array<int, array{request:ConstituencyRequest,cost:int,value:float}>  $items
     * @return array<int, int>
     */
    private function knapsackGreedy(array $items, int $budget): array
    {
        usort($items, function ($a, $b) {
            $ra = $a['cost'] > 0 ? $a['value'] / $a['cost'] : 0;
            $rb = $b['cost'] > 0 ? $b['value'] / $b['cost'] : 0;

            return $rb <=> $ra;
        });

        $selected = [];
        $spent = 0;
        foreach ($items as $item) {
            if ($spent + $item['cost'] <= $budget) {
                $selected[] = $item['request']->request_id;
                $spent += $item['cost'];
            }
        }

        return $selected;
    }

    /**
     * @return array{estimated_cost_kes:int,cost_rationale:string,confidence:float}
     */
    private function heuristicOnly(ConstituencyRequest $request): array
    {
        // Reuse Gemma service fallback by calling with empty key path — call public estimate
        // after temporarily... better: duplicate via estimateProjectCost which falls back
        // when API fails. For pure heuristic without network, build context and use reflection
        // is overkill — call estimateProjectCost; if API is slow we already capped calls.
        // Instead invoke a lightweight local heuristic mirroring Gemma fallback:
        $category = strtolower((string) ($request->category ?? 'general'));
        $base = match (true) {
            str_contains($category, 'road') => 2_500_000,
            str_contains($category, 'water') => 800_000,
            str_contains($category, 'educ') || str_contains($category, 'school') => 1_500_000,
            str_contains($category, 'health') || str_contains($category, 'hosp') => 2_000_000,
            str_contains($category, 'secur') => 500_000,
            str_contains($category, 'sanit') || str_contains($category, 'drain') => 1_200_000,
            str_contains($category, 'electr') || str_contains($category, 'power') => 1_800_000,
            default => 1_000_000,
        };

        $urgency = (int) ($request->urgency_score ?? match (strtolower((string) $request->urgency)) {
            'high' => 8,
            'medium' => 5,
            default => 3,
        });
        $reports = max(1, (int) ($request->cluster?->report_count ?? $request->similar_count ?? 1));
        $cost = (int) round($base * (0.7 + ($urgency / 10) * 0.6) * (1 + min($reports, 20) * 0.05));

        return [
            'estimated_cost_kes' => max(50_000, min(50_000_000, $cost)),
            'cost_rationale' => 'Heuristic CDF-scale estimate from category, urgency, and related report volume.',
            'confidence' => 0.45,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(ConstituencyRequest $req): array
    {
        return [
            'request_id' => $req->request_id,
            'content' => $req->content ?? $req->raw_message,
            'category' => $req->category ?? 'General',
            'urgency' => $req->urgency,
            'priority_score' => (float) ($req->priority_score ?? 0),
            'estimated_cost_kes' => (int) ($req->estimated_cost_kes ?? 0),
            'cost_source' => $req->cost_source,
            'cost_rationale' => $req->cost_rationale,
            'similar_count' => $req->cluster?->report_count ?? ($req->similar_count ?? 1),
            'cluster_summary' => $req->cluster
                ? $req->cluster->summaryLine($req->cluster->ward?->name)
                : null,
            'value_per_kes' => $req->estimated_cost_kes
                ? round(((float) ($req->priority_score ?? 0)) / (int) $req->estimated_cost_kes, 8)
                : 0,
        ];
    }
}
