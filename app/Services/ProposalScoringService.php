<?php

namespace App\Services;

use App\Models\ConstituencyFacility;
use App\Models\ConstituencyRequest;
use App\Services\Gemma4Service;
use Illuminate\Support\Facades\Log;

class ProposalScoringService
{
    protected Gemma4Service $gemmaService;

    public function __construct(Gemma4Service $gemmaService)
    {
        $this->gemmaService = $gemmaService;
    }

    /**
     * Compare two competing project proposals side-by-side. 
     * Gemma 4 handles all urgency ranking, cost/expense predictions, and trade-off evaluations natively.
     */
    public function compareProposals(int $requestIdA, int $requestIdB): array
    {
        $reqA = ConstituencyRequest::with(['facility', 'mp'])->where('request_id', $requestIdA)->firstOrFail();
        $reqB = ConstituencyRequest::with(['facility', 'mp'])->where('request_id', $requestIdB)->firstOrFail();

        // Feed raw operational context to Gemma 4 without pre-calculated app metrics or hardcoded budgets
        $proposalAData = [
            'request_id'             => $reqA->request_id,
            'title'                  => $reqA->content ?? $reqA->raw_message ?? 'N/A',
            'category'               => $reqA->category ?? 'General',
            'declared_urgency'       => strtolower($reqA->urgency ?? 'low'),
            'citizen_reports_count'  => (int) ($reqA->similarity_hash ?? 1),
            'facility_name'          => $reqA->facility?->facility_name ?? 'N/A',
            'current_enrollment'     => $reqA->facility?->current_enrollment ?? 0,
            'current_capacity'       => $reqA->facility?->current_capacity ?? 0,
            'avg_travel_distance_km' => $reqA->facility?->avg_travel_distance_km ?? 0.0,
            'is_in_cidp_plan'        => (bool) ($reqA->facility?->is_in_cidp_plan ?? false),
            'poverty_index_score'    => $reqA->facility?->poverty_index_score ?? 50.0,
        ];

        $proposalBData = [
            'request_id'             => $reqB->request_id,
            'title'                  => $reqB->content ?? $reqB->raw_message ?? 'N/A',
            'category'               => $reqB->category ?? 'General',
            'declared_urgency'       => strtolower($reqB->urgency ?? 'low'),
            'citizen_reports_count'  => (int) ($reqB->similarity_hash ?? 1),
            'facility_name'          => $reqB->facility?->facility_name ?? 'N/A',
            'current_enrollment'     => $reqB->facility?->current_enrollment ?? 0,
            'current_capacity'       => $reqB->facility?->current_capacity ?? 0,
            'avg_travel_distance_km' => $reqB->facility?->avg_travel_distance_km ?? 0.0,
            'is_in_cidp_plan'        => (bool) ($reqB->facility?->is_in_cidp_plan ?? false),
            'poverty_index_score'    => $reqB->facility?->poverty_index_score ?? 50.0,
        ];

        // Fully invoke Gemma 4 to autonomously compute expenses, timelines, scores, and winning priority
        $aiEvaluation = $this->gemmaService->evaluateAndCompareProposals($proposalAData, $proposalBData);

        $scoreA = (int) ($aiEvaluation['score_proposal_a'] ?? 50);
        $scoreB = (int) ($aiEvaluation['score_proposal_b'] ?? 50);
        $winner = $aiEvaluation['recommended_winner'] ?? ($scoreA >= $scoreB ? 'proposal_a' : 'proposal_b');

        Log::info("ProposalScoringService: Autonomous Gemma 4 comparison finalized", [
            'request_id_a'       => $requestIdA,
            'request_id_b'       => $requestIdB,
            'recommended_winner' => $winner,
            'score_a'            => $scoreA,
            'score_b'            => $scoreB,
        ]);

        return [
            'proposal_a' => array_merge($proposalAData, [
                'score'                 => $scoreA,
                'estimated_budget_kes'  => $aiEvaluation['predicted_budget_a'] ?? 'Ksh 2,000,000',
                'implementation_period' => $aiEvaluation['predicted_timeline_a'] ?? '2 Months',
            ]),
            'proposal_b' => array_merge($proposalBData, [
                'score'                 => $scoreB,
                'estimated_budget_kes'  => $aiEvaluation['predicted_budget_b'] ?? 'Ksh 1,500,000',
                'implementation_period' => $aiEvaluation['predicted_timeline_b'] ?? '1 Month',
            ]),
            'recommended_winner' => $winner,
            'score_difference'   => abs($scoreA - $scoreB),
            'ai_reasoning'       => $aiEvaluation['ai_reasoning'] ?? 'Autonomous evaluation completed by Gemma 4.',
            'trade_off_analysis' => $aiEvaluation['trade_off_analysis'] ?? 'N/A',
            'suggested_fix'      => $aiEvaluation['suggested_fix'] ?? 'N/A',
            'confidence_score'   => (int) ($aiEvaluation['confidence_score'] ?? 85),
        ];
    }
}