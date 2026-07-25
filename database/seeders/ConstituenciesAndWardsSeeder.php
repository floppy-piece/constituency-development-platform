<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ConstituenciesAndWardsSeeder extends Seeder
{
    public function run(): void
    {
        $termStart = Carbon::create(2022, 8, 9, 0, 0, 0);
        $termEnd   = Carbon::create(2027, 8, 10, 0, 0, 0);
        $defaultPassword = Hash::make('Password123!');

        $data = [
            // ==================== MOMBASA county_or_region ====================
            [
                'county_or_region' => 'Mombasa',
                'constituency' => [
                    'name' => 'Mvita',
                    'latitude' => -4.0547,
                    'longitude' => 39.6636,
                ],
                'mp' => [
                    'mp_name' => 'Mohamed Soud Machele',
                    'email' => 'mvita.mp@parliament.go.ke',
                    'avatar_path' => '/images/mps/machele.jpg',
                ],
                'wards' => [
                    ['name' => 'Mji wa Kale / Makadara', 'latitude' => -4.0530, 'longitude' => 39.6700, 'approximate_size' => 15.5],
                    ['name' => 'Tudor', 'latitude' => -4.0410, 'longitude' => 39.6730, 'approximate_size' => 12.0],
                    ['name' => 'Tononoka', 'latitude' => -4.0515, 'longitude' => 39.6580, 'approximate_size' => 10.0],
                    ['name' => 'Ganjoni / Shimanzi', 'latitude' => -4.0650, 'longitude' => 39.6650, 'approximate_size' => 14.0],
                    ['name' => 'Majengo', 'latitude' => -4.0580, 'longitude' => 39.6510, 'approximate_size' => 8.5],
                ]
            ],
            [
                'county_or_region' => 'Mombasa',
                'constituency' => [
                    'name' => 'Nyali',
                    'latitude' => -4.0325,
                    'longitude' => 39.7131,
                ],
                'mp' => [
                    'mp_name' => 'Mohamed Ali Mohamed',
                    'email' => 'nyali.mp@parliament.go.ke',
                    'avatar_path' => '/images/mps/moha.jpg',
                ],
                'wards' => [
                    ['name' => 'Frere Town', 'latitude' => -4.0200, 'longitude' => 39.6900, 'approximate_size' => 11.0],
                    ['name' => 'Ziwa la Ng\'ombe', 'latitude' => -4.0350, 'longitude' => 39.6850, 'approximate_size' => 9.0],
                    ['name' => 'Kongowea', 'latitude' => -4.0280, 'longitude' => 39.6750, 'approximate_size' => 13.5],
                    ['name' => 'Kadzandani', 'latitude' => -4.0150, 'longitude' => 39.6550, 'approximate_size' => 18.0],
                    ['name' => 'Bamburi', 'latitude' => -3.9980, 'longitude' => 39.7200, 'approximate_size' => 22.0],
                ]
            ],
            [
                'county_or_region' => 'Mombasa',
                'constituency' => [
                    'name' => 'Kisauni',
                    'latitude' => -4.0100,
                    'longitude' => 39.6800,
                ],
                'mp' => [
                    'mp_name' => 'Rashid Juma Bedzimba',
                    'email' => 'kisauni.mp@parliament.go.ke',
                    'avatar_path' => '/images/mps/bedzimba.jpg',
                ],
                'wards' => [
                    ['name' => 'Mjambere', 'latitude' => -4.0250, 'longitude' => 39.6600, 'approximate_size' => 10.0],
                    ['name' => 'Junda', 'latitude' => -4.0100, 'longitude' => 39.6700, 'approximate_size' => 7.5],
                    ['name' => 'Bamburi Ward', 'latitude' => -3.9950, 'longitude' => 39.7100, 'approximate_size' => 12.0],
                    ['name' => 'Mwakirunge', 'latitude' => -3.9350, 'longitude' => 39.6700, 'approximate_size' => 42.0],
                    ['name' => 'Magogoni', 'latitude' => -3.9800, 'longitude' => 39.7400, 'approximate_size' => 14.0],
                ]
            ],
            [
                'county_or_region' => 'Mombasa',
                'constituency' => [
                    'name' => 'Changamwe',
                    'latitude' => -4.0180,
                    'longitude' => 39.6150,
                ],
                'mp' => [
                    'mp_name' => 'Omar Mwinyi Shimbwa',
                    'email' => 'changamwe.mp@parliament.go.ke',
                    'avatar_path' => '/images/mps/mwinyi.jpg',
                ],
                'wards' => [
                    ['name' => 'Port Reitz', 'latitude' => -4.0300, 'longitude' => 39.6300, 'approximate_size' => 15.0],
                    ['name' => 'Kipevu', 'latitude' => -4.0200, 'longitude' => 39.6250, 'approximate_size' => 13.0],
                    ['name' => 'Airport', 'latitude' => -4.0350, 'longitude' => 39.5950, 'approximate_size' => 25.0],
                    ['name' => 'Changamwe Ward', 'latitude' => -4.0150, 'longitude' => 39.6100, 'approximate_size' => 9.5],
                ]
            ],
            [
                'county_or_region' => 'Mombasa',
                'constituency' => [
                    'name' => 'Jomvu',
                    'latitude' => -3.9510,
                    'longitude' => 39.6170,
                ],
                'mp' => [
                    'mp_name' => 'Bady Bady Twalib',
                    'email' => 'jomvu.mp@parliament.go.ke',
                    'avatar_path' => '/images/mps/twalib.jpg',
                ],
                'wards' => [
                    ['name' => 'Jomvu Kuu', 'latitude' => -3.9550, 'longitude' => 39.6100, 'approximate_size' => 20.0],
                    ['name' => 'Miritini', 'latitude' => -3.9400, 'longitude' => 39.6050, 'approximate_size' => 25.0],
                    ['name' => 'Mikindani', 'latitude' => -3.9680, 'longitude' => 39.6300, 'approximate_size' => 16.5],
                ]
            ],
            [
                'county_or_region' => 'Mombasa',
                'constituency' => [
                    'name' => 'Likoni',
                    'latitude' => -4.0830,
                    'longitude' => 39.6580,
                ],
                'mp' => [
                    'mp_name' => 'Mishi Juma Khamisi Mboko',
                    'email' => 'likoni.mp@parliament.go.ke',
                    'avatar_path' => '/images/mps/mboko.jpg',
                ],
                'wards' => [
                    ['name' => 'Mtongwe', 'latitude' => -4.0750, 'longitude' => 39.6350, 'approximate_size' => 14.0],
                    ['name' => 'Shika Adabu', 'latitude' => -4.0950, 'longitude' => 39.6150, 'approximate_size' => 18.5],
                    ['name' => 'Bofu', 'latitude' => -4.0850, 'longitude' => 39.6300, 'approximate_size' => 11.0],
                    ['name' => 'Likoni Ward', 'latitude' => -4.0780, 'longitude' => 39.6600, 'approximate_size' => 6.5],
                ]
            ],

            // ==================== KILIFI county_or_region ====================
            [
                'county_or_region' => 'Kilifi',
                'constituency' => [
                    'name' => 'Malindi',
                    'latitude' => -3.2200,
                    'longitude' => 40.1190,
                ],
                'mp' => [
                    'mp_name' => 'Amina Mnyazi Laura',
                    'email' => 'malindi.mp@parliament.go.ke',
                    'avatar_path' => '/images/mps/mnyazi.jpg',
                ],
                'wards' => [
                    ['name' => 'Jilore', 'latitude' => -3.2400, 'longitude' => 39.9500, 'approximate_size' => 80.0],
                    ['name' => 'Kakuyuni', 'latitude' => -3.2800, 'longitude' => 39.9200, 'approximate_size' => 60.0],
                    ['name' => 'Ganda', 'latitude' => -3.2600, 'longitude' => 40.0500, 'approximate_size' => 35.0],
                    ['name' => 'Malindi Town', 'latitude' => -3.2190, 'longitude' => 40.1169, 'approximate_size' => 20.0],
                ]
            ],
            [
                'county_or_region' => 'Kilifi',
                'constituency' => [
                    'name' => 'Kilifi South',
                    'latitude' => -3.6333,
                    'longitude' => 39.8500,
                ],
                'mp' => [
                    'mp_name' => 'Ken Chonga',
                    'email' => 'kilifisouth.mp@parliament.go.ke',
                    'avatar_path' => '/images/mps/chonga.jpg',
                ],
                'wards' => [
                    ['name' => 'Junju', 'latitude' => -3.7150, 'longitude' => 39.7550, 'approximate_size' => 55.0],
                    ['name' => 'Mwarakaya', 'latitude' => -3.7500, 'longitude' => 39.7900, 'approximate_size' => 48.0],
                    ['name' => 'Shimo la Tewa', 'latitude' => -3.6150, 'longitude' => 39.7350, 'approximate_size' => 25.0],
                    ['name' => 'Chasimba', 'latitude' => -3.6800, 'longitude' => 39.7800, 'approximate_size' => 40.0],
                    ['name' => 'Takaungu', 'latitude' => -3.6850, 'longitude' => 39.8500, 'approximate_size' => 30.0],
                ]
            ],
        ];

        foreach ($data as $entry) {
            // 1. Insert or update the MP record
            DB::table('mps')->updateOrInsert(
                ['email' => $entry['mp']['email']],
                [
                    'mp_name'           => $entry['mp']['mp_name'],
                    'constituency_name' => $entry['constituency']['name'],
                    'email_verified_at' => now(),
                    'password'          => $defaultPassword,
                    'term_start'        => $termStart,
                    'term_end'          => $termEnd,
                    'avatar_path'       => $entry['mp']['avatar_path'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]
            );

            // Retrieve the saved MP row to capture its primary key (supporting both 'id' or 'mp_id')
            $mpRecord = DB::table('mps')->where('email', $entry['mp']['email'])->first();
            $mpId = $mpRecord->mp_id ?? $mpRecord->id ?? null;

            // 2. Insert or update the Constituency record
            DB::table('constituencies')->updateOrInsert(
                ['name' => $entry['constituency']['name']],
                [
                    'county_or_region'       => $entry['county_or_region'],
                    'latitude'     => $entry['constituency']['latitude'],
                    'longitude'    => $entry['constituency']['longitude'],
                    'mp_id'        => $mpId,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );

            // Retrieve the saved Constituency ID
            $constituencyRecord = DB::table('constituencies')->where('name', $entry['constituency']['name'])->first();

            if ($constituencyRecord) {
                // 3. Insert or update Wards linked to the Constituency
                foreach ($entry['wards'] as $ward) {
                    DB::table('wards')->updateOrInsert(
                        [
                            'constituency_id' => $constituencyRecord->constituency_id,
                            'name'            => $ward['name'],
                        ],
                        [
                            'latitude'         => $ward['latitude'],
                            'longitude'        => $ward['longitude'],
                            'approximate_size' => $ward['approximate_size'],
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]
                    );
                }
            }
        }
    }
}