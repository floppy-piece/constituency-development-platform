<?php

namespace App\Services;

use App\Models\ConstituencyRequest;
use App\Models\IssueCluster;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IssueClusterService
{
    public const WINDOW_DAYS = 14;

    /**
     * Always keep the citizen request, then attach it to a theme cluster.
     * Matches are evidence on a systemic theme — not duplicates to discard.
     */
    public function attachRequest(
        ConstituencyRequest $request,
        array $analysis = [],
        ?ConstituencyRequest $matchedRequest = null
    ): IssueCluster {
        $cluster = null;

        if ($matchedRequest) {
            $cluster = $matchedRequest->cluster_id
                ? IssueCluster::find($matchedRequest->cluster_id)
                : null;

            if (! $cluster) {
                $cluster = $this->createClusterFromRequest($matchedRequest, $analysis);
                $matchedRequest->cluster_id = $cluster->cluster_id;
                $matchedRequest->save();
            }
        }

        if (! $cluster) {
            $cluster = $this->findDeterministicCandidate($request);
        }

        if (! $cluster) {
            $cluster = $this->createClusterFromRequest($request, $analysis);
            $this->linkRequest($request, $cluster, isFirstMember: true);

            return $this->refreshTrend($cluster);
        }

        $this->linkRequest($request, $cluster, isFirstMember: false);
        $this->absorbRequestIntoCluster($cluster, $request, $analysis);

        return $this->refreshTrend($cluster);
    }

    public function findDeterministicCandidate(ConstituencyRequest $request): ?IssueCluster
    {
        $since = now()->subDays(self::WINDOW_DAYS);
        $category = $request->category ?: 'General';

        $query = IssueCluster::query()
            ->where('mp_id', $request->mp_id)
            ->where('category', $category)
            ->where('last_seen_at', '>=', $since)
            ->orderByDesc('last_seen_at');

        if ($request->ward_id) {
            $byWard = (clone $query)->where('ward_id', $request->ward_id)->first();
            if ($byWard) {
                return $byWard;
            }
        }

        if ($request->latitude && $request->longitude) {
            foreach ($query->get() as $candidate) {
                if ($candidate->centroid_lat === null || $candidate->centroid_lng === null) {
                    continue;
                }

                if ($this->haversineKm(
                    (float) $request->latitude,
                    (float) $request->longitude,
                    (float) $candidate->centroid_lat,
                    (float) $candidate->centroid_lng
                ) <= 2.0) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    public function refreshTrend(IssueCluster $cluster): IssueCluster
    {
        $thisWeek = $cluster->requests()
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        $prevWeek = $cluster->requests()
            ->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ])
            ->count();

        if ($prevWeek === 0) {
            $cluster->trend = $thisWeek >= 2
                ? IssueCluster::TREND_RISING
                : IssueCluster::TREND_STABLE;
        } elseif ($thisWeek > $prevWeek * 1.2) {
            $cluster->trend = IssueCluster::TREND_RISING;
        } elseif ($thisWeek < $prevWeek * 0.8) {
            $cluster->trend = IssueCluster::TREND_FALLING;
        } else {
            $cluster->trend = IssueCluster::TREND_STABLE;
        }

        $cluster->save();

        return $cluster->fresh(['ward']);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function themesForMp(int $mpId): Collection
    {
        return IssueCluster::with('ward:ward_id,name')
            ->where('mp_id', $mpId)
            ->where('last_seen_at', '>=', now()->subDays(30))
            ->orderByDesc('report_count')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(function (IssueCluster $cluster) {
                $this->refreshTrend($cluster);
                $wardName = $cluster->ward?->name ?? 'Multiple wards';
                $changePct = $this->weekOverWeekChange($cluster);

                return [
                    'cluster_id' => $cluster->cluster_id,
                    'theme_label' => $cluster->theme_label,
                    'category' => $cluster->category,
                    'report_count' => $cluster->report_count,
                    'trend' => $cluster->trend,
                    'trend_change_pct' => $changePct,
                    'ward_id' => $cluster->ward_id,
                    'ward_name' => $wardName,
                    'severity_score' => $cluster->severity_score,
                    'centroid_lat' => $cluster->centroid_lat,
                    'centroid_lng' => $cluster->centroid_lng,
                    'first_seen_at' => $cluster->first_seen_at?->toIso8601String(),
                    'last_seen_at' => $cluster->last_seen_at?->toIso8601String(),
                    'summary' => $cluster->summaryLine($wardName)
                        . ($changePct !== null
                            ? sprintf(' (%s%d%% vs last week)', $changePct > 0 ? '+' : '', $changePct)
                            : ''),
                ];
            });
    }

    private function weekOverWeekChange(IssueCluster $cluster): ?int
    {
        $thisWeek = $cluster->requests()
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        $prevWeek = $cluster->requests()
            ->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ])
            ->count();

        if ($prevWeek === 0) {
            return $thisWeek > 0 ? 100 : null;
        }

        return (int) round((($thisWeek - $prevWeek) / $prevWeek) * 100);
    }

    private function createClusterFromRequest(ConstituencyRequest $request, array $analysis): IssueCluster
    {
        $theme = trim((string) ($analysis['theme_label'] ?? ''));
        if ($theme === '') {
            $theme = Str::limit($request->content ?: ($request->category ?: 'Constituency issue'), 80);
        }

        $urgencyScore = isset($analysis['urgency_score'])
            ? (int) $analysis['urgency_score']
            : match (strtolower((string) $request->urgency)) {
                'high' => 8,
                'medium' => 5,
                default => 2,
            };

        return IssueCluster::create([
            'mp_id' => $request->mp_id,
            'ward_id' => $request->ward_id,
            'category' => $request->category ?: 'General',
            'theme_label' => $theme,
            'report_count' => 1,
            'first_seen_at' => $request->created_at ?? now(),
            'last_seen_at' => $request->created_at ?? now(),
            'trend' => IssueCluster::TREND_STABLE,
            'centroid_lat' => $request->latitude,
            'centroid_lng' => $request->longitude,
            'severity_score' => $urgencyScore,
            'ward_ids' => $request->ward_id ? [$request->ward_id] : [],
        ]);
    }

    private function linkRequest(ConstituencyRequest $request, IssueCluster $cluster, bool $isFirstMember): void
    {
        $request->cluster_id = $cluster->cluster_id;

        if (! $isFirstMember) {
            // Keep legacy similar_count in sync on the lead signal for older UI pieces.
            $request->similar_count = max(1, (int) $request->similar_count);
        }

        $request->save();
    }

    private function absorbRequestIntoCluster(
        IssueCluster $cluster,
        ConstituencyRequest $request,
        array $analysis
    ): void {
        $count = max(1, (int) $cluster->report_count) + 1;
        $cluster->report_count = $count;
        $cluster->last_seen_at = $request->created_at ?? now();

        if ($request->ward_id) {
            $wardIds = $cluster->ward_ids ?? [];
            if (! is_array($wardIds)) {
                $wardIds = [];
            }
            $wardIds[] = $request->ward_id;
            $cluster->ward_ids = array_values(array_unique($wardIds));

            if (! $cluster->ward_id) {
                $cluster->ward_id = $request->ward_id;
            }
        }

        if ($request->latitude && $request->longitude) {
            if ($cluster->centroid_lat === null || $cluster->centroid_lng === null) {
                $cluster->centroid_lat = $request->latitude;
                $cluster->centroid_lng = $request->longitude;
            } else {
                $prev = $count - 1;
                $cluster->centroid_lat = (($cluster->centroid_lat * $prev) + $request->latitude) / $count;
                $cluster->centroid_lng = (($cluster->centroid_lng * $prev) + $request->longitude) / $count;
            }
        }

        $incomingScore = isset($analysis['urgency_score'])
            ? (int) $analysis['urgency_score']
            : null;

        if ($incomingScore !== null) {
            $cluster->severity_score = max((int) ($cluster->severity_score ?? 0), $incomingScore);
        }

        if (! empty($analysis['theme_label']) && $cluster->report_count <= 2) {
            $cluster->theme_label = trim((string) $analysis['theme_label']);
        }

        $cluster->save();

        // Mirror intensity onto any early "lead" request for backward-compatible UIs.
        ConstituencyRequest::where('cluster_id', $cluster->cluster_id)
            ->orderBy('request_id')
            ->limit(1)
            ->update(['similar_count' => $cluster->report_count]);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
