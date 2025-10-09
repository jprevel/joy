<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientStatusUpdate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StatusfactionSeeder extends Seeder
{
    /**
     * Seed 5 weeks of statusfaction data for Urban Kitchen Co
     */
    public function run(): void
    {
        $client = Client::where('name', 'LIKE', '%Urban Kitchen%')->first();
        $user = User::where('email', 'ariane@majormajor.marketing')->first();

        if (!$client) {
            $this->command->error('Urban Kitchen client not found');
            return;
        }

        if (!$user) {
            $this->command->error('Ariane user not found');
            return;
        }

        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();

        $this->command->info("Creating 5 weeks of status data for {$client->name}...");

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // Create data for the last 5 weeks
        $statusData = [
            ['weeks_ago' => 4, 'satisfaction' => 7, 'health' => 6, 'notes' => 'Initial onboarding phase. Client getting familiar with our process.'],
            ['weeks_ago' => 3, 'satisfaction' => 8, 'health' => 7, 'notes' => 'Great progress on website redesign. Client very responsive to feedback.'],
            ['weeks_ago' => 2, 'satisfaction' => 6, 'health' => 6, 'notes' => 'Some delays due to client feedback turnaround. Team adjusting timeline.'],
            ['weeks_ago' => 1, 'satisfaction' => 9, 'health' => 8, 'notes' => 'Launched new homepage. Client thrilled with results. Team morale high.'],
            ['weeks_ago' => 0, 'satisfaction' => 9, 'health' => 9, 'notes' => 'Excellent week. All deliverables on track. Client requesting expansion of scope.'],
        ];

        foreach ($statusData as $data) {
            $weekDate = $weekStart->copy()->subWeeks($data['weeks_ago']);

            ClientStatusUpdate::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'week_start_date' => $weekDate,
                ],
                [
                    'user_id' => $user->id,
                    'status_notes' => $data['notes'],
                    'client_satisfaction' => $data['satisfaction'],
                    'team_health' => $data['health'],
                    'status_date' => $weekDate->copy()->addDays(3), // Mid-week submission
                    'approval_status' => 'approved',
                    'approved_by' => $admin?->id ?? 1,
                    'approved_at' => $weekDate->copy()->addDays(4),
                ]
            );

            $this->command->line("✓ Week: {$weekDate->format('M j, Y')} (Satisfaction: {$data['satisfaction']}, Health: {$data['health']})");
        }

        $this->command->info('');
        $this->command->info('✅ Done! Created 5 weeks of status data.');
    }
}
