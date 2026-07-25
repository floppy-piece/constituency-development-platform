<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use App\Models\ConstituentFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // 1. Total Count & Averages
        $totalFeedback = ConstituentFeedback::count();
        $avgResponseDays = ConstituentFeedback::whereNotNull('resolved_at')
            ->select(DB::raw('AVG(DATEDIFF(resolved_at, created_at)) as avg_days'))
            ->value('avg_days') ?? 2.4;

        $feasibleCount = ConstituentFeedback::where('status', 'feasible')->count();
        $feasibilityRate = $totalFeedback > 0 ? ($feasibleCount / $totalFeedback) * 100 : 0;

        // 2. Timeline Data (Grouped by Month)
        $timelineData = ConstituentFeedback::select(
                DB::raw("DATE_FORMAT(created_at, '%b') as month"),
                DB::raw("COUNT(*) as total")
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('created_at', 'ASC')
            ->pluck('total', 'month');

        // 3. Category Breakdown
        $categories = ConstituentFeedback::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category');

       // 4. Ward Distribution
        $wards = ConstituentFeedback::join('wards', 'requests.ward_id', '=', 'wards.ward_id')
        ->select('wards.name as ward_name', DB::raw('count(*) as total'))
        ->groupBy('wards.name')
        ->get();

        return view('mp.analytics', compact(
            'totalFeedback',
            'avgResponseDays',
            'feasibilityRate',
            'timelineData',
            'categories',
            'wards'
        ));
    }
}