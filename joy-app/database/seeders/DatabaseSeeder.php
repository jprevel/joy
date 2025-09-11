<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed data in the correct order to ensure proper relationships
        $this->call([
            RolePermissionSeeder::class,  // Create roles, permissions, and users first
            TeamSeeder::class,            // Create teams and assign users to teams
            AgencyUserSeeder::class,      // Create legacy agency users (if still needed)
            ClientSeeder::class,          // Create clients and assign them to teams
            ContentItemSeeder::class,     // Create content items with proper owner/client relationships
        ]);
    }
}
