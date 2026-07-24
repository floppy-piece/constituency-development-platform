<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    private const BDC_CLIENT_ENDPOINT = 'https://api.bigdatacloud.net/data/reverse-geocode-client';

    /**
     * Resolve candidate locality names (ordered from most specific to broadest).
     *
     * @return array<string>
     */
    public function resolveLocationCandidates(?float $lat, ?float $lng, ?string $ip = null): array
    {
        // 1. Primary Method: Try GPS coordinates first
        if ($lat !== null && $lng !== null) {
            $candidates = $this->fetchFromBigDataCloud(['latitude' => $lat, 'longitude' => $lng]);
            if (!empty($candidates)) {
                return $candidates;
            }
        }

        // 2. Fallback Method: IP Location
        if ($ip === null || $this->isValidPublicIp($ip)) {
            $candidates = $this->fetchFromBigDataCloud();
            if (!empty($candidates)) {
                return $candidates;
            }
        }

        return [];
    }

    /**
     * Query BigDataCloud API and collect all geographic name candidates.
     *
     * @return array<string>
     */
    private function fetchFromBigDataCloud(array $queryParams = []): array
    {
        try {
            $params = array_merge([
                'localityLanguage' => 'en',
            ], $queryParams);

            $response = Http::timeout(5)->get(self::BDC_CLIENT_ENDPOINT, $params);

            if ($response->successful()) {
                $data = $response->json();
                $candidates = [];

                // A. Deep Administrative Levels (Sub-districts, wards, constituencies)
                if (!empty($data['localityInfo']['administrative'])) {
                    $adminLevels = collect($data['localityInfo']['administrative'])
                        ->sortByDesc('adminLevel'); // Higher level = smaller/more granular area

                    foreach ($adminLevels as $admin) {
                        if (!empty($admin['name']) && !in_array($admin['name'], $candidates)) {
                            $candidates[] = $admin['name'];
                        }
                    }
                }

                // B. Informative Locality Data (Neighborhoods, sub-locations)
                if (!empty($data['localityInfo']['informative'])) {
                    foreach ($data['localityInfo']['informative'] as $info) {
                        if (!empty($info['name']) && !in_array($info['name'], $candidates)) {
                            $candidates[] = $info['name'];
                        }
                    }
                }

                // C. Top-level Fallbacks (Locality, City, County/State)
                foreach (['locality', 'city', 'principalSubdivision'] as $key) {
                    if (!empty($data[$key]) && !in_array($data[$key], $candidates)) {
                        $candidates[] = $data[$key];
                    }
                }

                return $candidates;
            }
        } catch (\Exception $e) {
            Log::error('BigDataCloud Geocoding Request Failed: ' . $e->getMessage());
        }

        return [];
    }

    private function isValidPublicIp(string $ip): bool
    {
        return filter_var(
            $ip, 
            FILTER_VALIDATE_IP, 
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}