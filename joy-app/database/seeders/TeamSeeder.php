<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create teams only if they don't already exist
        $bukonutsTeam = Team::firstOrCreate([
            'name' => 'Bukonuts'
        ], [
            'description' => 'The Bukonuts team handles creative campaigns and brand strategy.',
        ]);

        $kalamansiTeam = Team::firstOrCreate([
            'name' => 'Kalamansi'
        ], [
            'description' => 'The Kalamansi team focuses on digital marketing and social media management.',
        ]);

        // Clear any existing team assignments to prevent duplicates
        $bukonutsTeam->users()->detach();
        $kalamansiTeam->users()->detach();

        // Assign Shaira to Bukonuts team
        $shairaUser = User::where('email', 'shaira@majormajor.marketing')->first();
        if ($shairaUser) {
            $shairaUser->teams()->syncWithoutDetaching([$bukonutsTeam->id]);
        }

        // Assign Ariane to Kalamansi team
        $arianeUser = User::where('email', 'ariane@majormajor.marketing')->first();
        if ($arianeUser) {
            $arianeUser->teams()->syncWithoutDetaching([$kalamansiTeam->id]);
        }

        // Assign admin user to both teams (admins can see all teams)
        $adminUser = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();
        if ($adminUser) {
            $adminUser->teams()->syncWithoutDetaching([$bukonutsTeam->id, $kalamansiTeam->id]);
        }
    }
}
