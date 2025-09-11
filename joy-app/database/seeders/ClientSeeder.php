<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get teams
        $bukonutsTeam = Team::where('name', 'Bukonuts')->first();
        $kalamansiTeam = Team::where('name', 'Kalamansi')->first();

        // Create clients and assign to teams
        Client::create([
            'name' => 'TechCorp Solutions',
            'description' => 'Leading B2B software company specializing in enterprise automation tools.',
            'team_id' => $bukonutsTeam?->id,
        ]);

        Client::create([
            'name' => 'Green Valley Wellness',
            'description' => 'Organic health and wellness brand focused on natural supplements and lifestyle products.',
            'team_id' => $bukonutsTeam?->id,
        ]);

        Client::create([
            'name' => 'Urban Kitchen Co',
            'description' => 'Modern restaurant chain known for farm-to-table dining and sustainable practices.',
            'team_id' => $kalamansiTeam?->id,
        ]);

        Client::create([
            'name' => 'Bright Future Education',
            'description' => 'Online learning platform providing courses in technology and professional development.',
            'team_id' => $kalamansiTeam?->id,
        ]);

        Client::create([
            'name' => 'Creative Studio Arts',
            'description' => 'Boutique design studio specializing in brand identity and creative campaigns.',
            'team_id' => $bukonutsTeam?->id,
        ]);

        Client::create([
            'name' => 'Pacific Real Estate Group',
            'description' => 'Premium real estate agency serving luxury properties across the Pacific coast.',
            'team_id' => $kalamansiTeam?->id,
        ]);

        Client::create([
            'name' => 'NextGen Fitness',
            'description' => 'Modern fitness center chain with cutting-edge equipment and personal training.',
            'team_id' => $bukonutsTeam?->id,
        ]);

        Client::create([
            'name' => 'Coastal Coffee Roasters',
            'description' => 'Artisanal coffee roasting company with sustainable sourcing practices.',
            'team_id' => $kalamansiTeam?->id,
        ]);
    }
}