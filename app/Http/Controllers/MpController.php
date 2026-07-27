<?php

namespace App\Http\Controllers;

use App\Models\ConstituencyRequest as ConstituentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ProposalScoringService;
use App\Services\TelegramNotifier;
use App\Models\ConstituencyFacility;


class MpController extends Controller
{
    /**
     * Get the authenticated MP's dashboard content.
     */
    public function dashboard(): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $mpId = $mp->mp_id ?? $mp->id;

        $totalRequests = ConstituentRequest::where('mp_id', $mpId)
            ->where('status', '!=', ConstituentRequest::STATUS_RESOLVED)
            ->count();
        $highUrgencyRequests = ConstituentRequest::where('mp_id', $mpId)
            ->where('urgency', 'high')
            ->where('status', '!=', ConstituentRequest::STATUS_RESOLVED)
            ->count();
        $needsReviewCount = ConstituentRequest::where('mp_id', $mpId)
            ->where('status', ConstituentRequest::STATUS_PENDING_REVIEW)
            ->count();

        // Fetch ALL constituent requests tied to this MP (exclude resolved from main feed)
        $recentRequests = ConstituentRequest::with(['user:user_id,phone_number', 'ward:ward_id,name'])
            ->where('mp_id', $mpId)
            ->where('status', '!=', ConstituentRequest::STATUS_RESOLVED)
            ->latest('created_at')
            ->get()
            ->map(function ($req, $index) {
                // Aligned with model primary key 'request_id'
                $id = $req->request_id ?? $req->id ?? ($index + 1);
                $similarCount = (int) ($req->similar_count ?? 1);

                $clusterWardIds = $req->cluster_ward_ids ?? [];
                if (! is_array($clusterWardIds)) {
                    $clusterWardIds = [];
                }

                $clusterWardCount = count(array_unique(array_filter($clusterWardIds)));
                if ($clusterWardCount === 0 && ! empty($req->ward_id)) {
                    $clusterWardCount = 1;
                }

                $reportWord = $similarCount === 1 ? 'report' : 'reports';
                $wardWord = $clusterWardCount === 1 ? 'ward' : 'wards';
                $firstSeen = $req->created_at ? $req->created_at->format('M j, Y') : 'N/A';
                $clusterSummary = sprintf(
                    '%d %s · %d %s · first seen %s',
                    $similarCount,
                    $reportWord,
                    $clusterWardCount,
                    $wardWord,
                    $firstSeen
                );

                return [
                    'id' => $id,
                    'request_id' => $id,
                    'urgency' => $req->urgency ?? 'low',
                    'category' => $req->category ?? $req->primary_topic ?? 'General',
                    'status' => $req->status ?? ConstituentRequest::STATUS_PENDING,
                    'confidence' => $req->confidence,
                    'file_type' => $req->file_type ?? 'text',
                    'upload_file_path' => $req->upload_file_path ?? null,
                    'content' => $req->content ?? $req->raw_message,
                    'raw_message' => $req->raw_message ?? $req->content,
                    'created_at' => $req->created_at ? $req->created_at->toIso8601String() : null,
                    'latitude' => $req->latitude,
                    'longitude' => $req->longitude,
                    'user' => [
                        'phone_number' => $req->user?->phone_number ?? 'N/A',
                    ],
                    'similar_count' => $similarCount,
                    'ward_name' => $req->ward?->name,
                    'cluster_summary' => $clusterSummary,
                ];
            });

        return response()->json([
            'status' => 'success',
            'mp_info' => [
                'id' => $mpId,
                'name' => $mp->mp_name,
                'email' => $mp->email,
                'constituency' => $mp->constituency_name ?? $mp->constituency,
                'avatar' => $mp->avatar_path ? asset($mp->avatar_path) : null,
            ],
            'metrics' => [
                'total_requests' => $totalRequests,
                'high_urgency_requests' => $highUrgencyRequests,
                'needs_review_count' => $needsReviewCount,
            ],
            'recent_requests' => $recentRequests,
        ]);
    }

    /**
     * Get paginated constituent requests for the MP.
     */
    public function getIssues(Request $request): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $mpId = $mp->mp_id ?? $mp->id;

        $query = ConstituentRequest::with('user:user_id,phone_number')
            ->where('mp_id', $mpId);

        if ($request->has('urgency')) {
            $query->where('urgency', $request->query('urgency'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->has('file_type')) {
            $query->where('file_type', $request->query('file_type'));
        }

        $requests = $query->latest('created_at')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    /**
     * Update request workflow status and notify the citizen via Telegram when applicable.
     */
    public function updateStatus(Request $request, int $id, TelegramNotifier $telegramNotifier): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,pending_review',
        ]);

        $mpId = $mp->mp_id ?? $mp->id;

        $requestItem = ConstituentRequest::with('user')
            ->where('request_id', $id)
            ->where('mp_id', $mpId)
            ->first();

        if (! $requestItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request not found or unauthorized.',
            ], 404);
        }

        $newStatus = $validated['status'];
        $previousStatus = $requestItem->status;

        if ($previousStatus === $newStatus) {
            return response()->json([
                'status' => 'success',
                'message' => 'Status unchanged.',
                'request_status' => $newStatus,
            ]);
        }

        $requestItem->update(['status' => $newStatus]);

        // Notify citizen on meaningful transitions (confirm from review = categorized)
        if (in_array($newStatus, [
            ConstituentRequest::STATUS_PENDING,
            ConstituentRequest::STATUS_IN_PROGRESS,
            ConstituentRequest::STATUS_RESOLVED,
        ], true)) {
            $telegramNotifier->notifyRequestStatus($requestItem->fresh('user'), $newStatus);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Request status updated.',
            'request_status' => $newStatus,
        ]);
    }

    /**
     * Mark a constituent request as resolved (compat endpoint).
     */
    public function markAsResolved(int $id, TelegramNotifier $telegramNotifier): JsonResponse
    {
        return $this->updateStatus(
            new Request(['status' => ConstituentRequest::STATUS_RESOLVED]),
            $id,
            $telegramNotifier
        );
    }

    /**
     * Render Proposal Comparison Matrix View
     */
    public function matrixView()
    {
        return view('mp.comparison-matrix');
    }

    /**
     * API: Compare two competing proposed projects against real metrics.
     */
    public function compareProposals(Request $request, ProposalScoringService $scoringService): JsonResponse
    {
        $request->validate([
            'proposal_a_id' => 'required|integer|exists:requests,request_id',
            'proposal_b_id' => 'required|integer|exists:requests,request_id',
        ]);

        $result = $scoringService->compareProposals(
            $request->input('proposal_a_id'),
            $request->input('proposal_b_id')
        );

        return response()->json([
            'status' => 'success',
            'comparison' => $result,
        ]);
    }

    /**
     * Map Demand Hotspots: Return GeoJSON FeatureCollection of constituent requests for GIS maps.
     */
    public function getDemandHotspots(Request $request): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (!$mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $mpId = $mp->mp_id ?? $mp->id;

        $requests = ConstituentRequest::with('ward:ward_id,name,latitude,longitude')
            ->where('mp_id', $mpId)
            ->where('status', '!=', ConstituentRequest::STATUS_RESOLVED)
            ->get();

        $features = [];
        $heatPoints = [];
        $highUrgency = 0;
        $withCoords = 0;

        foreach ($requests as $item) {
            $lat = (float) ($item->latitude ?? 0);
            $lng = (float) ($item->longitude ?? 0);

            // WhatsApp sometimes stores 0,0 — fall back to ward centroid when available
            $coordSource = 'gps';
            if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
                if ($item->ward && $item->ward->latitude && $item->ward->longitude) {
                    $lat = (float) $item->ward->latitude;
                    $lng = (float) $item->ward->longitude;
                    $coordSource = 'ward_centroid';
                } else {
                    continue;
                }
            }

            $reportsCount = max(1, (int) ($item->similar_count ?? 1));
            $urgency = strtolower($item->urgency ?? 'low');
            $weight = match ($urgency) {
                'high' => 3,
                'medium' => 2,
                default => 1,
            } * $reportsCount;

            $intensity = min(10, $weight);
            if ($urgency === 'high') {
                $highUrgency++;
            }
            $withCoords++;

            // Leaflet.heat expects [lat, lng, intensity 0–1]
            $heatPoints[] = [
                $lat,
                $lng,
                max(0.2, min(1.0, $intensity / 10)),
            ];

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$lng, $lat],
                ],
                'properties' => [
                    'request_id' => $item->request_id,
                    'summary' => $item->content,
                    'category' => $item->category ?? 'General',
                    'urgency' => $item->urgency,
                    'status' => $item->status,
                    'ward_name' => $item->ward?->name,
                    'reports_count' => $reportsCount,
                    'heatmap_intensity' => $intensity,
                    'coord_source' => $coordSource,
                    'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                ],
            ];
        }

        // Ward-level aggregates strengthen the heatmap narrative even with sparse GPS
        $wardHeat = ConstituentRequest::query()
            ->where('mp_id', $mpId)
            ->where('status', '!=', ConstituentRequest::STATUS_RESOLVED)
            ->whereNotNull('ward_id')
            ->join('wards', 'requests.ward_id', '=', 'wards.ward_id')
            ->select(
                'wards.ward_id',
                'wards.name as ward_name',
                'wards.latitude',
                'wards.longitude',
                DB::raw('count(requests.request_id) as total_requests'),
                DB::raw("sum(case when requests.urgency = 'high' then 1 else 0 end) as high_urgency_count"),
                DB::raw('coalesce(sum(requests.similar_count), count(requests.request_id)) as report_weight')
            )
            ->groupBy('wards.ward_id', 'wards.name', 'wards.latitude', 'wards.longitude')
            ->get()
            ->map(function ($ward) {
                $total = max(1, (int) $ward->total_requests);
                $high = (int) $ward->high_urgency_count;
                $weight = max(1, (int) $ward->report_weight);
                $intensity = min(1.0, ($weight / max(1, $total)) * (0.35 + ($high / $total) * 0.65));

                return [
                    'ward_id' => $ward->ward_id,
                    'ward_name' => $ward->ward_name,
                    'latitude' => (float) $ward->latitude,
                    'longitude' => (float) $ward->longitude,
                    'total_requests' => $total,
                    'high_urgency_count' => $high,
                    'heat' => [
                        (float) $ward->latitude,
                        (float) $ward->longitude,
                        max(0.25, $intensity),
                    ],
                ];
            })
            ->values();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'heat_points' => $heatPoints,
            'ward_heat' => $wardHeat,
            'meta' => [
                'mapped_requests' => $withCoords,
                'high_urgency_mapped' => $highUrgency,
                'ward_clusters' => $wardHeat->count(),
            ],
        ]);
    }

    public function getAnalyticsData(Request $request)
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $mpId = $mp->mp_id ?? $mp->id;

        // Query requests scoped to the authenticated MP
        $baseQuery = ConstituentRequest::where('mp_id', $mpId);

        $totalRequests = (clone $baseQuery)->count();
        $resolvedCount = (clone $baseQuery)->where('status', ConstituentRequest::STATUS_RESOLVED)->count();
        $openQuery = (clone $baseQuery)->where('status', '!=', ConstituentRequest::STATUS_RESOLVED);
        $openCount = (clone $openQuery)->count();

        // Category Breakdown (all requests)
        $categories = (clone $baseQuery)
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        // Community priorities: open requests by category with percentages
        $priorityRows = (clone $openQuery)
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->map(function ($row) use ($openCount) {
                $count = (int) $row->count;
                $label = $row->category ?: 'General';

                return [
                    'category' => $label,
                    'count' => $count,
                    'percentage' => $openCount > 0 ? round(($count / $openCount) * 100, 1) : 0,
                ];
            })
            ->values();

        // Ward Distribution with high-urgency share (severity signal)
        $wardDistribution = (clone $baseQuery)
            ->join('wards', 'requests.ward_id', '=', 'wards.ward_id')
            ->select(
                'wards.name as ward_name',
                DB::raw('count(requests.request_id) as total_requests'),
                DB::raw("sum(case when requests.urgency = 'high' then 1 else 0 end) as high_urgency_count")
            )
            ->groupBy('wards.name')
            ->orderByDesc('total_requests')
            ->get()
            ->map(function ($ward) {
                $total = (int) $ward->total_requests;
                $high = (int) $ward->high_urgency_count;
                $share = $total > 0 ? round(($high / $total) * 100, 1) : 0;

                $severity = 'low';
                if ($share >= 40 || $high >= 5) {
                    $severity = 'high';
                } elseif ($share >= 20 || $high >= 2) {
                    $severity = 'medium';
                }

                return [
                    'ward_name' => $ward->ward_name,
                    'total_requests' => $total,
                    'high_urgency_count' => $high,
                    'high_urgency_share' => $share,
                    'severity' => $severity,
                ];
            });

        // Top ward × category combinations among open requests
        $wardCategoryTop = (clone $openQuery)
            ->join('wards', 'requests.ward_id', '=', 'wards.ward_id')
            ->select(
                'wards.name as ward_name',
                'requests.category',
                DB::raw('count(requests.request_id) as count')
            )
            ->groupBy('wards.name', 'requests.category')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'ward_name' => $row->ward_name,
                'category' => $row->category ?: 'General',
                'count' => (int) $row->count,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_requests' => $totalRequests,
                'resolved_requests' => $resolvedCount,
                'open_requests' => $openCount,
                'categories' => $categories,
                'community_priorities' => $priorityRows,
                'ward_distribution' => $wardDistribution,
                'ward_category_top' => $wardCategoryTop,
            ],
        ]);
    }
}