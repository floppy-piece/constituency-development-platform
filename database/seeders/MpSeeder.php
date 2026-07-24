<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Mp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data matching your schema
        $mps = [
            [
                'mp_name' => 'John Doe',
                'constituency_name' => 'Central Constituency',
                'email' => 'john.doe@parliament.go.ke',
                'email_verified_at' => now(),
                'password' => Hash::make('securepassword123'),
                'term_start' => Carbon::create(2022, 8, 9, 0, 0, 0),
                'term_end' => Carbon::create(2027, 8, 9, 23, 59, 59),
                'avatar_path' => 'avatars/john_doe.jpg',
            ],
            [
                'mp_name' => 'Jane Smith',
                'constituency_name' => 'North Constituency',
                'email' => 'jane.smith@parliament.go.ke',
                'email_verified_at' => now(),
                'password' => Hash::make('memberofparliament2026'),
                'term_start' => Carbon::create(2022, 8, 9, 0, 0, 0),
                'term_end' => Carbon::create(2027, 8, 9, 23, 59, 59),
                'avatar_path' => null, // Optional column
            ],
        ];

        foreach ($mps as $mp) {
            Mp::create($mp);
        }
    }
}
