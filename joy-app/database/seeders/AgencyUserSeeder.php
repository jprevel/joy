<?php

namespace Database\Seeders;

use App\Models\AgencyUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgencyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AgencyUser::create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah@majormajoragency.com',
            'role' => 'Content Manager',
        ]);

        AgencyUser::create([
            'name' => 'Mike Chen',
            'email' => 'mike@majormajoragency.com', 
            'role' => 'Creative Director',
        ]);

        AgencyUser::create([
            'name' => 'Emma Rodriguez',
            'email' => 'emma@majormajoragency.com',
            'role' => 'Social Media Specialist',
        ]);
    }
}