<?php

namespace App\Http\Controllers\MP;

use App\Models\ConstituencyRequest as ConstituentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Gemma4Service;
use App\Services\IssueClusterService;
use App\Services\PriorityScoringService;
use App\Services\BudgetOptimizerService;
use App\Services\ResolutionVerificationService;
use App\Models\ConstituencyFacility;
use App\Http\Controllers\Controller;


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

<<<<<<< HEAD
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
=======
        $activeScope = function ($q) {
            $q->where('status', '!=', ConstituentRequest::STATUS_RESOLVED)
                ->orWhere(function ($q2) {
                    $q2->where('status', ConstituentRequest::STATUS_RESOLVED)
                        ->where('verification_status', ConstituentRequest::VERIFICATION_PENDING);
                });
        };

        $totalRequests = ConstituentRequest::where('mp_id', $mpId)->where($activeScope)->count();
        $highUrgencyRequests = ConstituentRequest::where('mp_id', $mpId)
            ->where('urgency', 'high')
            ->where('status', '!=', ConstituentRequest::STATUS_RESOLVED)
            ->count();
        $needsReviewCount = ConstituentRequest::where('mp_id', $mpId)
            ->where('status', ConstituentRequest::STATUS_PENDING_REVIEW)
            ->count();
        $awaitingVerificationCount = ConstituentRequest::where('mp_id', $mpId)
            ->where('status', ConstituentRequest::STATUS_RESOLVED)
            ->where('verification_status', ConstituentRequest::VERIFICATION_PENDING)
            ->count();
        $equityFlaggedCount = ConstituentRequest::where('mp_id', $mpId)
            ->where($activeScope)
            ->where('equity_flag', true)
            ->count();

        $recentRequests = ConstituentRequest::with([
                'user:user_id,phone_number',
                'cluster.ward:ward_id,name',
            ])
            ->where('mp_id', $mpId)
            ->where($activeScope)
            ->orderByRaw('mp_priority_rank IS NULL')
            ->orderBy('mp_priority_rank')
            ->orderByDesc('priority_score')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($req, $index) {
                $id = $req->request_id ?? $req->id ?? ($index + 1);
                $cluster = $req->cluster;
                $clusterSummary = $cluster
                    ? $cluster->summaryLine($cluster->ward?->name)
                    : null;
>>>>>>> origin/feature/communities-clustering

                return [
                    'id' => $id,
                    'request_id' => $id,
<<<<<<< HEAD
                    'urgency' => $req->urgency ?? 'low',
                    'category' => $req->category ?? $req->primary_topic ?? 'General',
                    'file_type' => $req->file_type ?? 'text',
=======
                    'display_rank' => $index + 1,
                    'urgency' => $req->urgency ?? 'low',
                    'urgency_score' => $req->urgency_score,
                    'priority_score' => (float) ($req->priority_score ?? 0),
                    'priority_factors' => $req->priority_factors,
                    'mp_priority_rank' => $req->mp_priority_rank,
                    'override_reason' => $req->override_reason,
                    'category' => $req->category ?? $req->primary_topic ?? 'General',
                    'file_type' => $req->file_type ?? 'text',
                    'source_channel' => $req->source_channel,
>>>>>>> origin/feature/communities-clustering
                    'upload_file_path' => $req->upload_file_path ?? null,
                    'content' => $req->content ?? $req->raw_message,
                    'raw_message' => $req->raw_message ?? $req->content,
                    'created_at' => $req->created_at ? $req->created_at->toIso8601String() : null,
                    'latitude' => $req->latitude,
                    'longitude' => $req->longitude,
<<<<<<< HEAD
=======
                    'status' => $req->status ?? ConstituentRequest::STATUS_PENDING,
                    'confidence' => $req->confidence,
                    'detected_language' => $req->detected_language,
                    'equity_flag' => (bool) $req->equity_flag,
                    'equity_reasons' => $req->equity_reasons,
                    'equity_boost' => (int) ($req->equity_boost ?? 0),
                    'similar_count' => $cluster?->report_count ?? ($req->similar_count ?? 1),
                    'cluster_id' => $req->cluster_id,
                    'cluster_summary' => $clusterSummary,
                    'cluster_ward_ids' => $req->cluster_ward_ids ?? [],
                    'evaluation_thoughts' => $req->evaluation_thoughts,
                    'suggested_fix' => $req->suggested_fix,
                    'verification_status' => $req->verification_status,
                    'verification_requested_at' => $req->verification_requested_at?->toIso8601String(),
                    'verified_at' => $req->verified_at?->toIso8601String(),
                    'verification_note' => $req->verification_note,
                    'verification_file_path' => $req->verification_file_path,
>>>>>>> origin/feature/communities-clustering
                    'user' => [
                        'phone_number' => $req->user?->phone_number ?? 'N/A',
                    ],
                    'similarity_hash' => $req->similarity_hash ?? 1,
                ];
            });

<<<<<<< HEAD
=======

        $sectors = collect($recentRequests)
            ->groupBy(fn ($req) => $this->normalizeSector($req['category'] ?? 'General'))
            ->map(function ($items, $sector) {
                $items = collect($items);
                $high = $items->where('urgency', 'high')->count();
                $review = $items->where('status', 'pending_review')->count();

                return [
                    'sector' => $sector,
                    'count' => $items->count(),
                    'high_urgency' => $high,
                    'needs_review' => $review,
                    'avg_priority' => round((float) $items->avg('priority_score'), 1),
                    'latest_at' => $items->max('created_at'),
                    'samples' => $items->sortByDesc('priority_score')->take(3)->values()->all(),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

>>>>>>> origin/feature/communities-clustering
        return response()->json([
            'status' => 'success',
            'mp_info' => [
                'id' => $mpId,
                'name' => $mp->mp_name,
                'email' => $mp->email,
                'constituency' => $mp->constituency_name ?? $mp->constituency,
                'avatar' => $mp->avatar_path ? asset($mp->avatar_path) : null,
<<<<<<< HEAD
=======
                'priorities_locked' => $mp->priorities_locked_at !== null,
>>>>>>> origin/feature/communities-clustering
            ],
            'metrics' => [
                'total_requests' => $totalRequests,
                'high_urgency_requests' => $highUrgencyRequests,
<<<<<<< HEAD
            ],
            'recent_requests' => $recentRequests,
=======
                'needs_review_count' => $needsReviewCount,
                'awaiting_verification_count' => $awaitingVerificationCount,
                'equity_flagged_count' => $equityFlaggedCount,
            ],
            'recent_requests' => $recentRequests,
            'sectors' => $sectors,
>>>>>>> origin/feature/communities-clustering
        ]);
    }

    /**
<<<<<<< HEAD
     * Get paginated constituent requests for the MP.
=======
     * Get paginated constituent requests for the MP (search + filters).
>>>>>>> origin/feature/communities-clustering
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

<<<<<<< HEAD
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
=======
        $validated = $request->validate([
            'q' => 'sometimes|nullable|string|max:200',
            'urgency' => 'sometimes|nullable|string|in:low,medium,high',
            'status' => 'sometimes|nullable|string|in:'.implode(',', ConstituentRequest::STATUSES),
            'category' => 'sometimes|nullable|string|max:80',
            'sector' => 'sometimes|nullable|string|max:80',
            'file_type' => 'sometimes|nullable|string|max:40',
            'equity_flag' => 'sometimes|nullable|boolean',
            'date_from' => 'sometimes|nullable|date',
            'date_to' => 'sometimes|nullable|date',
            'sort' => 'sometimes|nullable|string|in:newest,oldest,urgency,priority',
            'per_page' => 'sometimes|nullable|integer|min:5|max:100',
        ]);

        $query = ConstituentRequest::with(['user:user_id,phone_number', 'cluster.ward:ward_id,name'])
            ->where('mp_id', $mpId);

        if (! empty($validated['q'])) {
            $term = '%'.trim($validated['q']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('content', 'like', $term)
                    ->orWhere('raw_message', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('evaluation_thoughts', 'like', $term);
            });
        }

        if (! empty($validated['urgency'])) {
            $query->where('urgency', $validated['urgency']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (! empty($validated['sector'])) {
            $aliases = $this->sectorCategoryAliases($validated['sector']);
            $query->where(function ($q) use ($aliases, $validated) {
                $q->whereIn('category', $aliases)
                    ->orWhere('category', 'like', '%'.$validated['sector'].'%');
            });
        }

        if (! empty($validated['file_type'])) {
            $query->where('file_type', $validated['file_type']);
        }

        if (array_key_exists('equity_flag', $validated) && $validated['equity_flag'] !== null) {
            $query->where('equity_flag', (bool) $validated['equity_flag']);
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $sort = $validated['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'urgency' => $query->orderByRaw("FIELD(urgency, 'high', 'medium', 'low')")->orderByDesc('created_at'),
            'priority' => $query->orderByDesc('priority_score')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $perPage = (int) ($validated['per_page'] ?? 20);
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($req) {
            return [
                'request_id' => $req->request_id,
                'content' => $req->content ?? $req->raw_message,
                'raw_message' => $req->raw_message,
                'category' => $req->category ?? 'General',
                'sector' => $this->normalizeSector($req->category),
                'urgency' => $req->urgency ?? 'low',
                'urgency_score' => $req->urgency_score,
                'status' => $req->status,
                'priority_score' => (float) ($req->priority_score ?? 0),
                'confidence' => $req->confidence,
                'file_type' => $req->file_type,
                'source_channel' => $req->source_channel,
                'detected_language' => $req->detected_language,
                'equity_flag' => (bool) $req->equity_flag,
                'verification_status' => $req->verification_status,
                'created_at' => $req->created_at?->toIso8601String(),
                'cluster_summary' => $req->cluster
                    ? $req->cluster->summaryLine($req->cluster->ward?->name)
                    : null,
                'user' => [
                    'phone_number' => $req->user?->phone_number ?? 'N/A',
                ],
            ];
        });

        $categories = ConstituentRequest::where('mp_id', $mpId)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return response()->json([
            'status' => 'success',
            'filters' => [
                'categories' => $categories,
                'sectors' => [
                    'Roads', 'Water', 'Drainage & Sanitation', 'Fire & Emergency',
                    'Health', 'Education', 'Security', 'Electricity', 'General',
                ],
                'urgencies' => ['high', 'medium', 'low'],
                'statuses' => ConstituentRequest::STATUSES,
            ],
            'data' => $paginator,
        ]);
    }

    public function requestsView()
    {
        return view('mp.requests');
    }

    /**
     * Map raw Gemma categories into stable sector labels for the dashboard.
     */
    public function normalizeSector(?string $category): string
    {
        $c = strtolower(trim((string) $category));

        return match (true) {
            $c === '' => 'General',
            str_contains($c, 'road') || str_contains($c, 'pothole') || str_contains($c, 'bridge') => 'Roads',
            str_contains($c, 'water') || str_contains($c, 'borehole') => 'Water',
            str_contains($c, 'drain') || str_contains($c, 'sanit') || str_contains($c, 'sewer') || str_contains($c, 'garbage') || str_contains($c, 'waste') => 'Drainage & Sanitation',
            str_contains($c, 'fire') || str_contains($c, 'emergenc') || str_contains($c, 'disaster') => 'Fire & Emergency',
            str_contains($c, 'health') || str_contains($c, 'hosp') || str_contains($c, 'clinic') || str_contains($c, 'medic') => 'Health',
            str_contains($c, 'educ') || str_contains($c, 'school') => 'Education',
            str_contains($c, 'secur') || str_contains($c, 'police') || str_contains($c, 'crime') => 'Security',
            str_contains($c, 'electr') || str_contains($c, 'power') || str_contains($c, 'light') => 'Electricity',
            default => ucwords($category ?: 'General'),
        };
    }

    /**
     * @return array<int, string>
     */
    private function sectorCategoryAliases(string $sector): array
    {
        $map = [
            'Roads' => ['Roads', 'Road', 'Transport'],
            'Water' => ['Water'],
            'Drainage & Sanitation' => ['Sanitation', 'Drainage', 'Waste', 'Garbage'],
            'Fire & Emergency' => ['Fire', 'Emergency', 'Disaster'],
            'Health' => ['Health', 'Healthcare'],
            'Education' => ['Education'],
            'Security' => ['Security'],
            'Electricity' => ['Electricity', 'Power'],
            'General' => ['General'],
        ];

        return $map[$sector] ?? [$sector];
    }

    /**
     * Update a constituent request status (AI recommends, MP decides).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
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
            'status' => 'required|string|in:' . implode(',', ConstituentRequest::STATUSES),
        ]);

        $mpId = $mp->mp_id ?? $mp->id;
        $newStatus = $validated['status'];

        $requestItem = ConstituentRequest::where('request_id', $id)
            ->where('mp_id', $mpId)
            ->first();

        if (! $requestItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request not found or unauthorized.',
            ], 404);
        }

        $requestItem->status = $newStatus;
        $requestItem->resolved_at = $newStatus === ConstituentRequest::STATUS_RESOLVED
            ? now()
            : null;

        if ($newStatus !== ConstituentRequest::STATUS_RESOLVED
            && $requestItem->verification_status === ConstituentRequest::VERIFICATION_PENDING) {
            $requestItem->verification_status = null;
            $requestItem->verification_requested_at = null;
        }

        $requestItem->save();

        $verification = null;
        if ($newStatus === ConstituentRequest::STATUS_RESOLVED) {
            $verificationService = app(\App\Services\ResolutionVerificationService::class);
            $sent = $verificationService->requestCitizenVerification($requestItem->fresh(['user']));
            $requestItem->refresh();
            $verification = [
                'requested' => true,
                'message_sent' => $sent,
                'verification_status' => $requestItem->verification_status,
            ];
        }

        return response()->json([
            'status'  => 'success',
            'message' => $newStatus === ConstituentRequest::STATUS_RESOLVED
                ? 'Marked resolved. Citizen verification requested on their messaging channel.'
                : 'Request status updated.',
            'data'    => [
                'request_id'  => $requestItem->request_id,
                'status'      => $requestItem->status,
                'resolved_at' => $requestItem->resolved_at?->toIso8601String(),
                'verification_status' => $requestItem->verification_status,
                'verification' => $verification,
            ],
>>>>>>> origin/feature/communities-clustering
        ]);
    }

    /**
<<<<<<< HEAD
     * Mark a constituent request as resolved.
=======
     * Ranked priorities board — AI recommends, MP decides.
     */
    public function getPriorities(PriorityScoringService $priorityService): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $mpId = $mp->mp_id ?? $mp->id;

        // Backfill scores for older rows that predate Sprint C.
        ConstituentRequest::where('mp_id', $mpId)
            ->where('status', '!=', ConstituentRequest::STATUS_RESOLVED)
            ->where(function ($q) {
                $q->whereNull('priority_score')->orWhere('priority_score', 0);
            })
            ->limit(100)
            ->get()
            ->each(fn ($req) => $priorityService->score($req));

        return response()->json([
            'status' => 'success',
            'locked' => $priorityService->prioritiesAreLocked($mp),
            'locked_at' => $mp->priorities_locked_at?->toIso8601String(),
            'available_budget_kes' => $mp->available_budget_kes,
            'banner' => 'AI recommendation — not an allocation decision until you lock priorities.',
            'priorities' => $priorityService->rankedListForMp($mpId),
        ]);
    }

    /**
     * Reorder the full priority list. Requires a reason when ranks change.
     */
    public function reorderPriorities(Request $request, PriorityScoringService $priorityService): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        if ($priorityService->prioritiesAreLocked($mp)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Priorities are locked. Unlock before reordering.',
            ], 423);
        }

        $validated = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'integer',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $mpId = $mp->mp_id ?? $mp->id;
        $reason = trim($validated['reason']);

        DB::transaction(function () use ($validated, $mpId, $mp, $reason) {
            foreach ($validated['ordered_ids'] as $index => $requestId) {
                ConstituentRequest::where('mp_id', $mpId)
                    ->where('request_id', $requestId)
                    ->update([
                        'mp_priority_rank' => $index + 1,
                        'override_reason' => $reason,
                        'overridden_by' => $mp->mp_id,
                        'overridden_at' => now(),
                    ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Priority order updated by MP.',
            'priorities' => $priorityService->rankedListForMp($mpId),
        ]);
    }

    /**
     * Promote or demote a single request one step, with required override reason.
     */
    public function overridePriority(Request $request, int $id, PriorityScoringService $priorityService): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        if ($priorityService->prioritiesAreLocked($mp)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Priorities are locked. Unlock before changing ranks.',
            ], 423);
        }

        $validated = $request->validate([
            'direction' => 'required|string|in:promote,demote',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $mpId = $mp->mp_id ?? $mp->id;
        $list = $priorityService->rankedListForMp($mpId);
        $ids = array_column($list, 'request_id');
        $currentIndex = array_search((int) $id, $ids, true);

        if ($currentIndex === false) {
            return response()->json(['status' => 'error', 'message' => 'Request not found in open priorities.'], 404);
        }

        $swapWith = $validated['direction'] === 'promote'
            ? $currentIndex - 1
            : $currentIndex + 1;

        if ($swapWith < 0 || $swapWith >= count($ids)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot move further in that direction.',
            ], 422);
        }

        $ids[$currentIndex] = $ids[$swapWith];
        $ids[$swapWith] = (int) $id;

        DB::transaction(function () use ($ids, $mpId, $mp, $validated) {
            foreach ($ids as $index => $requestId) {
                ConstituentRequest::where('mp_id', $mpId)
                    ->where('request_id', $requestId)
                    ->update([
                        'mp_priority_rank' => $index + 1,
                        'override_reason' => trim($validated['reason']),
                        'overridden_by' => $mp->mp_id,
                        'overridden_at' => now(),
                    ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Priority override saved.',
            'priorities' => $priorityService->rankedListForMp($mpId),
        ]);
    }

    public function lockPriorities(Request $request, PriorityScoringService $priorityService): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'locked' => 'required|boolean',
        ]);

        $mp->priorities_locked_at = $validated['locked'] ? now() : null;
        $mp->save();

        return response()->json([
            'status' => 'success',
            'locked' => $priorityService->prioritiesAreLocked($mp->fresh()),
            'locked_at' => $mp->priorities_locked_at?->toIso8601String(),
            'message' => $validated['locked']
                ? 'Priorities locked. AI recommendations are frozen until you unlock.'
                : 'Priorities unlocked. You can reorder again.',
        ]);
    }

    public function rescorePriorities(PriorityScoringService $priorityService): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $mpId = $mp->mp_id ?? $mp->id;
        $count = $priorityService->rescoreOpenForMp($mpId);

        return response()->json([
            'status' => 'success',
            'rescored' => $count,
            'priorities' => $priorityService->rankedListForMp($mpId),
        ]);
    }

    /**
     * Save the MP's available CDF / allocation budget for knapsack bundling.
     */
    public function updateBudget(Request $request): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'available_budget_kes' => 'nullable|integer|min:0|max:5000000000',
        ]);

        $mp->available_budget_kes = $validated['available_budget_kes'] ?? null;
        $mp->save();

        return response()->json([
            'status' => 'success',
            'available_budget_kes' => $mp->available_budget_kes,
            'message' => 'Available budget saved.',
        ]);
    }

    /**
     * Maximize impact under budget: propose a fundable request bundle (AI recommends, MP decides).
     */
    public function budgetBundle(Request $request, BudgetOptimizerService $budgetService): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'available_budget_kes' => 'nullable|integer|min:0|max:5000000000',
            'refresh_costs' => 'sometimes|boolean',
            'persist_budget' => 'sometimes|boolean',
        ]);

        $budget = $validated['available_budget_kes'] ?? $mp->available_budget_kes;

        if ($budget === null || (int) $budget <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Provide available_budget_kes (or save a budget on your profile first).',
            ], 422);
        }

        if (! empty($validated['persist_budget'])) {
            $mp->available_budget_kes = (int) $budget;
            $mp->save();
        }

        $mpId = $mp->mp_id ?? $mp->id;
        $bundle = $budgetService->proposeBundle(
            $mpId,
            (int) $budget,
            (bool) ($validated['refresh_costs'] ?? false)
        );

        return response()->json([
            'status' => 'success',
            'available_budget_kes' => $mp->fresh()->available_budget_kes,
            'bundle' => $bundle,
        ]);
    }

    public function refreshCostEstimates(BudgetOptimizerService $budgetService): JsonResponse
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (! $mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $mpId = $mp->mp_id ?? $mp->id;
        $updated = $budgetService->ensureCostsForMp($mpId, true);

        return response()->json([
            'status' => 'success',
            'updated' => $updated,
            'message' => "Refreshed cost estimates for {$updated} open request(s) via Gemma 4 (heuristic fallback when needed).",
        ]);
    }

    public function prioritiesView()
    {
        return view('mp.priorities');
    }

    /**
     * Mark a constituent request as resolved (status update only — never delete).
>>>>>>> origin/feature/communities-clustering
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

<<<<<<< HEAD
        $requestItem->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Request marked as resolved successfully.',
=======
        $requestItem->status = ConstituentRequest::STATUS_RESOLVED;
        $requestItem->resolved_at = now();
        $requestItem->save();

        $verificationService = app(\App\Services\ResolutionVerificationService::class);
        $sent = $verificationService->requestCitizenVerification($requestItem->fresh(['user']));
        $requestItem->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Request marked as resolved. Citizen verification requested.',
            'data' => [
                'request_id' => $requestItem->request_id,
                'verification_status' => $requestItem->verification_status,
                'message_sent' => $sent,
            ],
>>>>>>> origin/feature/communities-clustering
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
    public function compareProposals(Request $request, Gemma4Service $gemmaService)
    {
        $request->validate([
            'proposal_a_id' => 'required|integer|exists:requests,request_id',
            'proposal_b_id' => 'required|integer|exists:requests,request_id',
        ]);

        // Fetch the two raw constituent requests independently of any facility table
        $reqA = ConstituentRequest::where('request_id', $request->input('proposal_a_id'))->firstOrFail();
        $reqB = ConstituentRequest::where('request_id', $request->input('proposal_b_id'))->firstOrFail();

        // Package raw request fields for Gemma 4 analysis
        $proposalA = [
            'request_id'       => $reqA->request_id,
            'title'            => $reqA->content ?? $reqA->raw_message ?? 'N/A',
            'category'         => $reqA->category ?? 'General',
            'declared_urgency' => strtolower($reqA->urgency ?? 'low'),
            'citizen_reports'  => (int) ($reqA->similarity_hash ?? 1),
            'created_at'       => $reqA->created_at?->toIso8601String(),
        ];

        $proposalB = [
            'request_id'       => $reqB->request_id,
            'title'            => $reqB->content ?? $reqB->raw_message ?? 'N/A',
            'category'         => $reqB->category ?? 'General',
            'declared_urgency' => strtolower($reqB->urgency ?? 'low'),
            'citizen_reports'  => (int) ($reqB->similarity_hash ?? 1),
            'created_at'       => $reqB->created_at?->toIso8601String(),
        ];

        // Gemma 4 performs full multi-factor comparison and analysis independently
        $comparisonResult = $gemmaService->evaluateAndCompareProposals($proposalA, $proposalB);

        return response()->json([
            'status'     => 'success',
            'comparison' => [
                'proposal_a'         => array_merge($proposalA, [
                    'score'                 => $comparisonResult['score_proposal_a'],
                    'estimated_budget_kes'  => $comparisonResult['predicted_budget_a'],
                    'implementation_period' => $comparisonResult['predicted_timeline_a'],
                ]),
                'proposal_b'         => array_merge($proposalB, [
                    'score'                 => $comparisonResult['score_proposal_b'],
                    'estimated_budget_kes'  => $comparisonResult['predicted_budget_b'],
                    'implementation_period' => $comparisonResult['predicted_timeline_b'],
                ]),
                'recommended_winner' => $comparisonResult['recommended_winner'],
                'ai_reasoning'       => $comparisonResult['ai_reasoning'],
                'trade_off_analysis' => $comparisonResult['trade_off_analysis'],
                'suggested_fix'      => $comparisonResult['suggested_fix'],
                'confidence_score'   => $comparisonResult['confidence_score'],
            ]
        ]);
    }

    /**
<<<<<<< HEAD
     * Map Demand Hotspots: Return GeoJSON FeatureCollection of constituent requests for GIS maps.
     */
    public function getDemandHotspots(Request $request): JsonResponse
=======
     * Map Demand Hotspots: GeoJSON points + recurring theme clusters for the MP UI.
     */
    public function getDemandHotspots(Request $request, IssueClusterService $clusterService): JsonResponse
>>>>>>> origin/feature/communities-clustering
    {
        /** @var \App\Models\Mp|null $mp */
        $mp = Auth::guard('mp_api')->user();

        if (!$mp) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $mpId = $mp->mp_id ?? $mp->id;

<<<<<<< HEAD
        // Fetch requests that have latitude & longitude coordinates
        $requests = ConstituentRequest::where('mp_id', $mpId)
=======
        $themes = $clusterService->themesForMp($mpId);

        // Fetch open requests that have latitude & longitude coordinates
        $requests = ConstituentRequest::with('cluster')
            ->where('mp_id', $mpId)
            ->where('status', '!=', ConstituentRequest::STATUS_RESOLVED)
>>>>>>> origin/feature/communities-clustering
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $features = $requests->map(function ($item) {
<<<<<<< HEAD
            // Determine map weight based on urgency and recurring reports count
=======
            $reportCount = (int) ($item->cluster?->report_count ?? $item->similar_count ?? 1);

>>>>>>> origin/feature/communities-clustering
            $weight = match (strtolower($item->urgency ?? 'low')) {
                'high' => 3,
                'medium' => 2,
                default => 1,
<<<<<<< HEAD
            } * ($item->similarity_hash ?? 1);
=======
            } * max(1, $reportCount);
>>>>>>> origin/feature/communities-clustering

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $item->longitude, (float) $item->latitude],
                ],
                'properties' => [
                    'request_id' => $item->request_id,
<<<<<<< HEAD
                    'summary' => $item->content,
                    'category' => $item->category ?? 'General',
                    'urgency' => $item->urgency,
                    'reports_count' => $item->similarity_hash ?? 1,
=======
                    'cluster_id' => $item->cluster_id,
                    'summary' => $item->content,
                    'theme_label' => $item->cluster?->theme_label,
                    'category' => $item->category ?? 'General',
                    'urgency' => $item->urgency,
                    'reports_count' => $reportCount,
>>>>>>> origin/feature/communities-clustering
                    'heatmap_intensity' => min(10, $weight),
                    'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                ],
            ];
        });

<<<<<<< HEAD
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
=======
        $clusterFeatures = $themes
            ->filter(fn ($theme) => $theme['centroid_lat'] !== null && $theme['centroid_lng'] !== null)
            ->map(function (array $theme) {
                $weight = max(1, (int) $theme['report_count']);

                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $theme['centroid_lng'], (float) $theme['centroid_lat']],
                    ],
                    'properties' => [
                        'cluster_id' => $theme['cluster_id'],
                        'is_theme_centroid' => true,
                        'summary' => $theme['summary'],
                        'theme_label' => $theme['theme_label'],
                        'category' => $theme['category'],
                        'urgency' => ($theme['severity_score'] ?? 0) >= 8 ? 'high' : (($theme['severity_score'] ?? 0) >= 4 ? 'medium' : 'low'),
                        'reports_count' => $theme['report_count'],
                        'trend' => $theme['trend'],
                        'heatmap_intensity' => min(10, $weight),
                    ],
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'type' => 'FeatureCollection',
            'features' => $features->values(),
            'cluster_features' => $clusterFeatures,
            'themes' => $themes->values(),
            'metrics' => [
                'theme_count' => $themes->count(),
                'rising_themes' => $themes->where('trend', 'rising')->count(),
                'total_clustered_reports' => $themes->sum('report_count'),
            ],
>>>>>>> origin/feature/communities-clustering
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
        $resolvedCount = (clone $baseQuery)->where('status', 'resolved')->count();
        
        // Category Breakdown
        $categories = (clone $baseQuery)
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        // Ward Distribution (Now utilizing the relationship)
        $wardDistribution = (clone $baseQuery)
        ->join('wards', 'requests.ward_id', '=', 'wards.ward_id')
        ->select('wards.name as ward_name', DB::raw('count(requests.request_id) as total_requests'))
        ->groupBy('wards.name')
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