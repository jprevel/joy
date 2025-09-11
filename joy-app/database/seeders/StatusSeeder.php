<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Draft',
                'sort_order' => 1,
                'is_reviewable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Ready for Review',
                'sort_order' => 2,
                'is_reviewable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'In Review',
                'sort_order' => 3,
                'is_reviewable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Changes Requested',
                'sort_order' => 4,
                'is_reviewable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Approved',
                'sort_order' => 5,
                'is_reviewable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Scheduled',
                'sort_order' => 6,
                'is_reviewable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Published',
                'sort_order' => 7,
                'is_reviewable' => false,
                'is_active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            \App\Models\Status::updateOrCreate(
                ['name' => $status['name']],
                $status
            );
        }
    }
}
