<?php

namespace App\Services\WhatsApp;

use App\Models\Constituency;
use App\Models\Mp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WhatsAppLocationService
{
    protected GeocodingService $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    /**
     * Resolve the most likely ward based on incoming coordinates.
     */
    public function resolveWard(?float $latitude, ?float $longitude): ?object
    {
        if (! $latitude || ! $longitude) {
            return null;
        }

        return DB::table('wards')
            ->select('*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                [$latitude, $longitude, $latitude]
            )
            ->whereRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= SQRT(approximate_size / PI())',
                [$latitude, $longitude, $latitude]
            )
            ->orderBy('distance', 'asc')
            ->first();
    }

    /**
     * Dynamically match MP based on live coordinates or fallback attributes.
     */
    public function resolveMp(User $user, ?float $latitude, ?float $longitude): object
    {
        $lat = $latitude ?? $user->last_latitude;
        $lng = $longitude ?? $user->last_longitude;

        if ($lat && $lng) {
            $rawCandidates = $this->geocodingService->resolveLocationCandidates($lat, $lng, null);
            
            $cleanCandidates = collect($rawCandidates)
                ->flatMap(fn($item) => explode(',', $item))
                ->map(fn($item) => trim($item))
                ->filter(fn($item) => !in_array(strtolower($item), ['kenya', 'africa', 'africa/nairobi']))
                ->unique()
                ->values()
                ->all();

            foreach ($cleanCandidates as $areaName) {
                $constituency = Constituency::where('name', 'LIKE', '%' . $areaName . '%')
                    ->orWhereRaw('? LIKE CONCAT("%", name, "%")', [$areaName])
                    ->first();

                if ($constituency) {
                    $mp = Mp::where('constituency_name', 'LIKE', '%' . $constituency->name . '%')->first();
                    if ($mp) return $mp;
                }
            }
        }

        if (!empty($user->constituency_name)) {
            $mp = Mp::where('constituency_name', $user->constituency_name)->first();
            if ($mp) return $mp;
        }

        return Mp::first() ?? new Mp(['mp_id' => 1, 'mp_name' => 'Default MP']);
    }
}