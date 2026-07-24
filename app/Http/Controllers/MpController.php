<?php

namespace App\Http\Controllers;

use App\Models\ConstituencyRequest as ConstituentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ProposalScoringService;
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

        $totalRequests = ConstituentRequest::where('mp_id', $mpId)->count();
        $highUrgencyRequests = ConstituentRequest::where('mp_id', $mpId)
            ->where('urgency', 'high')
            ->count();

        // Fetch ALL constituent requests tied to this MP
        $recentRequests = ConstituentRequest::with('user:user_id,phone_number')
            ->where('mp_id', $mpId)
            ->latest('created_at')
            ->get()
            ->map(function ($req, $index) {
                // Aligned with model primary key 'request_id'
                $id = $req->request_id ?? $req->id ?? ($index + 1);

                return [
                    'id' => $id,
                    'request_id' => $id,
                    'urgency' => $req->urgency ?? 'low',
                    'category' => $req->category ?? $req->primary_topic ?? 'General',
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
                    'similarity_hash' => $req->similarity_hash ?? 1,
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
     * Mark a constituent request as resolved.
     */
    public function markAsResolved(int $id): JsonResponse
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

        $requestItem = ConstituentRequest::where('request_id', $id)
            ->where('mp_id', $mpId)
            ->first();

        if (! $requestItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request not found or unauthorized.',
            ], 404);
        }

        $requestItem->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Request marked as resolved successfully.',
        ]);
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

        // Fetch requests that have latitude & longitude coordinates
        $requests = ConstituentRequest::where('mp_id', $mpId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $features = $requests->map(function ($item) {
            // Determine map weight based on urgency and recurring reports count
            $weight = match (strtolower($item->urgency ?? 'low')) {
                'high' => 3,
                'medium' => 2,
                default => 1,
            } * ($item->similarity_hash ?? 1);

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $item->longitude, (float) $item->latitude],
                ],
                'properties' => [
                    'request_id' => $item->request_id,
                    'summary' => $item->content,
                    'category' => $item->category ?? 'General',
                    'urgency' => $item->urgency,
                    'reports_count' => $item->similarity_hash ?? 1,
                    'heatmap_intensity' => min(10, $weight),
                    'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function getAnalyticsData(Request $request)
    {
        $mp = auth('mp_api')->user();

        // Query requests scoped to the authenticated MP
        $baseQuery = ConstituencyRequest::where('mp_id', $mp->mp_id);

        $totalRequests = (clone $baseQuery)->count();
        $resolvedCount = (clone $baseQuery)->where('status', 'resolved')->count();
        
        // Category Breakdown
        $categories = (clone $baseQuery)
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        // Ward/Location Distribution
        $wardDistribution = (clone $baseQuery)
            ->select('ward_name', DB::raw('count(*) as total_requests'))
            ->groupBy('ward_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_requests' => $totalRequests,
                'resolved_requests' => $resolvedCount,
                'categories' => $categories,
                'ward_distribution' => $wardDistribution,
            ]
        ]);
    }
}