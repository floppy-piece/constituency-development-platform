<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MpSeeder extends Seeder
{
    public function run(): void
    {
        $termStart = Carbon::create(2022, 8, 9, 0, 0, 0); // 13th Parliament election date
        $termEnd   = Carbon::create(2027, 8, 10, 0, 0, 0);
        $defaultPassword = Hash::make('Password123!'); // Secure hashed password for initial login

        $mps = [
            // Mombasa County MPs
            [
                'mp_name'           => 'Mohamed Soud Machele',
                'constituency_name' => 'Mvita',
                'email'             => 'mvita.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/machele.jpg',
            ],
            [
                'mp_name'           => 'Mohamed Ali Mohamed',
                'constituency_name' => 'Nyali',
                'email'             => 'nyali.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/moha.jpg',
            ],
            [
                'mp_name'           => 'Rashid Juma Bedzimba',
                'constituency_name' => 'Kisauni',
                'email'             => 'kisauni.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/bedzimba.jpg',
            ],
            [
                'mp_name'           => 'Omar Mwinyi Shimbwa',
                'constituency_name' => 'Changamwe',
                'email'             => 'changamwe.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/mwinyi.jpg',
            ],
            [
                'mp_name'           => 'Bady Bady Twalib',
                'constituency_name' => 'Jomvu',
                'email'             => 'jomvu.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/twalib.jpg',
            ],
            [
                'mp_name'           => 'Mishi Juma Khamisi Mboko',
                'constituency_name' => 'Likoni',
                'email'             => 'likoni.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/mboko.jpg',
            ],

            // Kilifi & Kwale Notable MPs
            [
                'mp_name'           => 'Amina Mnyazi Laura',
                'constituency_name' => 'Malindi',
                'email'             => 'malindi.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/mnyazi.jpg',
            ],
            [
                'mp_name'           => 'Feisal Abdalla Bader',
                'constituency_name' => 'Msambweni',
                'email'             => 'msambweni.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/bader.jpg',
            ],

            // Major Urban / Capital MPs
            [
                'mp_name'           => 'Babu Owino (Paul Ongili)',
                'constituency_name' => 'Embakasi East',
                'email'             => 'embakasieast.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/babu.jpg',
            ],
            [
                'mp_name'           => 'Beatrice Elachi Kadeveresia',
                'constituency_name' => 'Dagoretti North',
                'email'             => 'dagorettinorth.mp@parliament.go.ke',
                'avatar_path'       => '/images/mps/elachi.jpg',
            ]
        ];

        foreach ($mps as $mp) {
            DB::table('mps')->updateOrInsert(
                ['email' => $mp['email']], // Prevents duplicate email errors on re-run
                [
                    'mp_name'           => $mp['mp_name'],
                    'constituency_name' => $mp['constituency_name'],
                    'email_verified_at' => now(),
                    'password'          => $defaultPassword,
                    'term_start'        => $termStart,
                    'term_end'          => $termEnd,
                    'avatar_path'       => $mp['avatar_path'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]
            );
        }
    }
}