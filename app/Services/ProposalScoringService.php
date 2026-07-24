<?php

namespace App\Services;

use App\Models\ConstituencyFacility;
use App\Models\ConstituencyRequest;

class ProposalScoringService
{
    /**
     * Compute objective Priority Score (0 - 100) combining Citizen Feedback, Infrastructure Gaps, 
     * Demographics, and County Development Plan (CIDP) alignment.
     */
    public function calculatePriorityScore(ConstituencyRequest $request, ?ConstituencyFacility $facility = null): float
    {
        // 1. Citizen Demand Weight (0 - 30 points)
        $reportCount = $request->similarity_hash ?? 1;
        $demandScore = min(30, $reportCount * 3); 

        // 2. Urgency Weight (0 - 15 points)
        $urgencyScore = match (strtolower($request->urgency ?? 'low')) {
            'high' => 15,
            'medium' => 8,
            default => 3,
        };

        // 3. Infrastructure Deficit Metrics Weight (0 - 25 points)
        $infrastructureScore = 0;
        if ($facility) {
            // Overcrowding / Deficit factor (up to 15 pts)
            if ($facility->current_capacity > 0) {
                $overcrowdingRatio = ($facility->current_enrollment / $facility->current_capacity);
                if ($overcrowdingRatio > 1.0) {
                    $infrastructureScore += min(15, ($overcrowdingRatio - 1) * 15);
                }
            }

            // Travel Distance factor (up to 10 pts)
            $distanceKm = $facility->avg_travel_distance_km ?? 0;
            $infrastructureScore += min(10, ($distanceKm / 10) * 10);
        } else {
            $infrastructureScore = 10;
        }

        // 4. Demographic Vulnerability Score (0 - 15 points)
        $demographicsScore = 0;
        if ($facility) {
            // Higher poverty score increases priority
            $povertyScore = $facility->poverty_index_score ?? 50.0;
            $demographicsScore = ($povertyScore / 100) * 15;
        } else {
            $demographicsScore = 7.5;
        }

        // 5. Local Development Plan (CIDP) Alignment Weight (0 - 15 points)
        $cidpScore = 0;
        if ($facility && $facility->is_in_cidp_plan) {
            $cidpScore = match (strtolower($facility->cidp_priority_tier ?? 'medium')) {
                'high' => 15,
                'medium' => 10,
                'low' => 5,
                default => 5,
            };
        }

        $totalScore = $demandScore + $urgencyScore + $infrastructureScore + $demographicsScore + $cidpScore;

        return min(100.0, round($totalScore, 2));
    }

    /**
     * Compare two competing project proposals side-by-side with demographics & CIDP context.
     */
    public function compareProposals(int $requestIdA, int $requestIdB): array
    {
        $reqA = ConstituencyRequest::with(['facility', 'mp'])->findOrFail($requestIdA);
        $reqB = ConstituencyRequest::with(['facility', 'mp'])->findOrFail($requestIdB);

        $scoreA = $this->calculatePriorityScore($reqA, $reqA->facility);
        $scoreB = $this->calculatePriorityScore($reqB, $reqB->facility);

        return [
            'proposal_a' => [
                'request_id' => $reqA->request_id,
                'title' => $reqA->content,
                'category' => $reqA->category ?? 'General',
                'urgency' => $reqA->urgency,
                'citizen_reports' => $reqA->similarity_hash ?? 1,
                'facility_name' => $reqA->facility?->facility_name ?? 'N/A',
                'enrollment' => $reqA->facility?->current_enrollment ?? 'N/A',
                'capacity' => $reqA->facility?->current_capacity ?? 'N/A',
                'avg_travel_distance_km' => $reqA->facility?->avg_travel_distance_km ?? 'N/A',
                'is_in_cidp_plan' => $reqA->facility?->is_in_cidp_plan ?? false,
                'poverty_index_score' => $reqA->facility?->poverty_index_score ?? 'N/A',
                'score' => $scoreA,
            ],
            'proposal_b' => [
                'request_id' => $reqB->request_id,
                'title' => $reqB->content,
                'category' => $reqB->category ?? 'General',
                'urgency' => $reqB->urgency,
                'citizen_reports' => $reqB->similarity_hash ?? 1,
                'facility_name' => $reqB->facility?->facility_name ?? 'N/A',
                'enrollment' => $reqB->facility?->current_enrollment ?? 'N/A',
                'capacity' => $reqB->facility?->current_capacity ?? 'N/A',
                'avg_travel_distance_km' => $reqB->facility?->avg_travel_distance_km ?? 'N/A',
                'is_in_cidp_plan' => $reqB->facility?->is_in_cidp_plan ?? false,
                'poverty_index_score' => $reqB->facility?->poverty_index_score ?? 'N/A',
                'score' => $scoreB,
            ],
            'recommended_winner' => $scoreA >= $scoreB ? 'proposal_a' : 'proposal_b',
            'score_difference' => abs(round($scoreA - $scoreB, 2)),
        ];
    }
}