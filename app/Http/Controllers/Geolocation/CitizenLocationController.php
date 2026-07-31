<?php

namespace App\Http\Controllers\Geolocation;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Models\Mp;
Use App\Models\User;
Use App\Models\Ward;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CitizenLocationController extends Controller
{
    protected GeocodingService $geocoding;

    public function __construct(GeocodingService $geocoding)
    {
        $this->geocoding = $geocoding;
    }

    /**
     * Generate a secure, temporary cache token linked to the browser's coordinates
     * for Telegram deep-linking.
     */
    public function generateTelegramToken(Request $request): JsonResponse
    {
        Log::info('generateTelegramToken hit. Payload received:', $request->all());

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        // Retrieve user based on your session identifier (e.g., phone number)
        $user = null;
        
        if (session()->has('citizen_phone')) {
            $user = User::where('phone_number', session('citizen_phone'))->first();
        }

        // Fallback: If no session exists yet, default to the first user or create a session anchor
        // (Adjust this lookup if you pass a phone number or identifier from your frontend form)
        if (!$user) {
            // For testing/development, fallback to user_id 1 if present
            $user = User::where('user_id', 1)->first();
            
            if ($user) {
                session(['citizen_phone' => $user->phone_number]);
            }
        }

        if ($user) {
            try {
                // Explicitly targeting your database column names
                $user->forceFill([
                    'last_latitude' => $latitude,
                    'last_longitude' => $longitude,
                ])->save();

                Log::info("Successfully updated database coordinates for User ID: {$user->user_id} -> Lat: {$latitude}, Lng: {$longitude}");
            } catch (\Throwable $e) {
                Log::error("Failed to save coordinates to database for user: {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::warning("generateTelegramToken: No matching user record found in database to update coordinates for.");
        }

        $token = Str::random(40);

        // Store coordinates securely in cache for 15 minutes for Telegram deep-linking
        Cache::put('telegram_loc_' . $token, [
            'latitude' => $latitude,
            'longitude' => $longitude
        ], now()->addMinutes(15));

        Log::info("Generated temporary telegram cache token: [{$token}]");

        return response()->json([
            'status' => 'success',
            'start_payload' => $token
        ]);
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

        $ward = null;
        foreach ($cleanCandidates as $areaName) {
            $ward = Ward::where('name', 'LIKE', '%' . $areaName . '%')->first();
            if ($ward) {
                break;
            }
        }

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
                    'ward' => $ward ? $ward->name : null,
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