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
        // Create admin user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@majormajoragency.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Seed the core data
        $this->call([
            TeamSeeder::class,
            AgencyUserSeeder::class,
            ClientSeeder::class,
            ContentItemSeeder::class,
        ]);
    }
}
