<?php

namespace App\Services\Telegram;

use App\Models\Constituency;
use App\Models\Mp;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use App\Service\GeocodingService;

class TelegramLocationService
{
    protected GeocodingService $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    public function resolve(float $latitude, float $longitude, ?string $clientIp): array
    {
        $rawCandidates = $this->geocodingService->resolveLocationCandidates($latitude, $longitude, $clientIp);
        
        $cleanCandidates = collect($rawCandidates)
            ->flatMap(fn($item) => explode(',', $item))
            ->map(fn($item) => trim($item))
            ->filter(fn($item) => !in_array(strtolower($item), ['kenya', 'africa', 'africa/nairobi']))
            ->unique()
            ->values()
            ->all();

        $constituency = null;

        foreach ($cleanCandidates as $areaName) {
            $constituency = Constituency::where('name', 'LIKE', '%' . $areaName . '%')
                ->orWhereRaw('? LIKE CONCAT("%", name, "%")', [$areaName])
                ->first();

            if ($constituency) break;
        }

        if (!$constituency) {
            $constituency = Constituency::select('*')
                ->selectRaw('( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                ->orderBy('distance')
                ->first();
        }

        if (!$constituency) {
            return ['constituency' => null, 'ward' => null, 'mp' => null];
        }

        $ward = DB::table('wards')
            ->select('*')
            ->selectRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance', [$latitude, $longitude, $latitude])
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= SQRT(approximate_size / PI())', [$latitude, $longitude, $latitude])
            ->orderBy('distance', 'asc')
            ->first();

        if (!$ward) {
            $ward = Ward::where('constituency_id', $constituency->constituency_id ?? $constituency->id)
                ->select('*')
                ->selectRaw('( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                ->orderBy('distance')
                ->first();
        }

        $mp = Mp::where('constituency_name', 'LIKE', '%' . $constituency->name . '%')->first() ?? Mp::first();

        return [
            'constituency' => $constituency,
            'ward' => $ward,
            'mp' => $mp,
        ];
    }
}