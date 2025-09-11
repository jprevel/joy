<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::create([
            'name' => 'TechCorp Solutions',
            'description' => 'Leading B2B software company specializing in enterprise automation tools.',
        ]);

        Client::create([
            'name' => 'Green Valley Wellness',
            'description' => 'Organic health and wellness brand focused on natural supplements and lifestyle products.',
        ]);

        Client::create([
            'name' => 'Urban Kitchen Co',
            'description' => 'Modern restaurant chain known for farm-to-table dining and sustainable practices.',
        ]);

        Client::create([
            'name' => 'Bright Future Education',
            'description' => 'Online learning platform providing courses in technology and professional development.',
        ]);
    }
}