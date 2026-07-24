<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Models\Mp;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenLocationController extends Controller
{
    protected GeocodingService $geocoding;

    public function __construct(GeocodingService $geocoding)
    {
        $this->geocoding = $geocoding;
    }

    public function detectMp(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $clientIp = $request->ip();

        // Step A: Resolve location candidates from GeocodingService
        $rawCandidates = $this->geocoding->resolveLocationCandidates($lat, $lng, $clientIp);

        if (empty($rawCandidates)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to automatically detect location. Please select your constituency manually.',
            ], 404);
        }

        // Clean & normalize candidate names (e.g. split "Mvita, Mombasa" -> ["Mvita", "Mombasa"])
        $cleanCandidates = collect($rawCandidates)
            ->flatMap(fn($item) => explode(',', $item))
            ->map(fn($item) => trim($item))
            ->filter(fn($item) => !in_array(strtolower($item), ['kenya', 'africa', 'africa/nairobi']))
            ->unique()
            ->values()
            ->all();

        // Step B: Direct exact/partial match against the constituencies table
        foreach ($cleanCandidates as $areaName) {
            $constituency = Constituency::where('name', 'LIKE', '%' . $areaName . '%')
                ->orWhereRaw('? LIKE CONCAT("%", name, "%")', [$areaName])
                ->first();

            if ($constituency) {
                $mp = Mp::where('constituency_name', 'LIKE', '%' . $constituency->name . '%')->first();

                return response()->json([
                    'status' => 'success',
                    'detected_area' => $areaName,
                    'constituency' => [
                        'id' => $constituency->constituency_id,
                        'name' => $constituency->name,
                    ],
                    'mp' => $mp ? [
                        'mp_id' => $mp->mp_id,
                        'mp_name' => $mp->mp_name,
                        'email' => $mp->email,
                        'avatar_path' => $mp->avatar_path ? asset($mp->avatar_path) : '/default-avatar.png',
                    ] : null,
                ]);
            }
        }

        // Step C: Fallback — Search MPs directly for detected areas (e.g., Mombasa county MPs)
        $possibleMps = Mp::where(function ($query) use ($cleanCandidates) {
            foreach ($cleanCandidates as $candidate) {
                $query->orWhere('constituency_name', 'LIKE', '%' . $candidate . '%');
            }
        })
        ->get(['mp_id', 'mp_name', 'email', 'constituency_name', 'avatar_path'])
        ->map(function ($mp) {
            return [
                'mp_id' => $mp->mp_id,
                'mp_name' => $mp->mp_name,
                'email' => $mp->email,
                'constituency_name' => $mp->constituency_name,
                'avatar_path' => $mp->avatar_path ? asset($mp->avatar_path) : '/default-avatar.png',
            ];
        });

        if ($possibleMps->isNotEmpty()) {
            return response()->json([
                'status' => 'multiple_candidates_found',
                'message' => 'No exact constituency match found. Here are representatives for nearby areas:',
                'detected_candidates' => $cleanCandidates,
                'possible_mps' => $possibleMps,
            ]);
        }

        // Step D: Default error response
        return response()->json([
            'status' => 'error',
            'message' => "Detected area '" . implode(', ', array_slice($cleanCandidates, 0, 2)) . "', but no matching constituency or MP was found in our system.",
            'detected_candidates' => $cleanCandidates,
        ], 404);
    }
}