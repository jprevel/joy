<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClientWorkspace;
use App\Models\AgencyUser;
use App\Models\Concept;
use App\Models\Variant;
use App\Models\Comment;

class JoySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Joy Demo workspace
        $workspace = ClientWorkspace::create([
            'name' => 'Joy Demo Company',
            'logo' => null,
            'trello_board_id' => null,
            'trello_list_id' => null,
        ]);

        // Create agency user
        $agencyUser = AgencyUser::create([
            'workspace_id' => $workspace->id,
            'role' => 'Agency Team',
            'name' => 'Alex Johnson',
            'email' => 'alex@joymarketing.com',
        ]);

        // Create concepts with variants (matching our mock data)
        $concepts = [
            [
                'title' => 'Q4 Product Launch Campaign',
                'notes' => 'Multi-platform campaign for new widget product line',
                'status' => 'In Review',
                'variants' => [
                    [
                        'platform' => 'facebook',
                        'copy' => '🚀 Exciting news! Our new widget is revolutionizing the industry. Get ready for something amazing! #Innovation #TechNews',
                        'scheduled_at' => '2025-09-12 10:00:00',
                        'status' => 'Draft',
                    ],
                    [
                        'platform' => 'instagram', 
                        'copy' => '✨ Behind the scenes of our widget development process. Swipe to see the journey! #BehindTheScenes #Innovation',
                        'scheduled_at' => '2025-09-12 14:00:00',
                        'status' => 'Draft',
                    ],
                    [
                        'platform' => 'linkedin',
                        'copy' => 'We\'re proud to announce our latest innovation in widget technology. This breakthrough represents months of dedicated R&D work.',
                        'scheduled_at' => '2025-09-13 09:00:00',
                        'status' => 'In Review',
                    ],
                ]
            ],
            [
                'title' => 'Holiday Season Content',
                'notes' => 'Festive content for holiday season',
                'status' => 'Draft',
                'variants' => [
                    [
                        'platform' => 'facebook',
                        'copy' => '🎄 Wishing all our customers a wonderful holiday season! Special offers coming soon...',
                        'scheduled_at' => '2025-09-19 16:00:00',
                        'status' => 'Draft',
                    ],
                    [
                        'platform' => 'instagram',
                        'copy' => '❄️ Holiday vibes at Joy Demo Company! Check out our festive office decorations',
                        'scheduled_at' => '2025-09-19 12:00:00',
                        'status' => 'Approved',
                    ],
                ]
            ],
            [
                'title' => 'Weekly Blog Content',
                'notes' => 'Regular blog posts for thought leadership',
                'status' => 'Scheduled',
                'variants' => [
                    [
                        'platform' => 'blog',
                        'copy' => 'The Future of Widget Technology: 5 Trends to Watch in 2024',
                        'scheduled_at' => '2025-09-10 08:00:00',
                        'status' => 'Scheduled',
                    ],
                ]
            ],
            [
                'title' => 'Customer Success Stories',
                'notes' => 'Showcase client achievements',
                'status' => 'In Review', 
                'variants' => [
                    [
                        'platform' => 'linkedin',
                        'copy' => 'Case Study: How Widget Corp increased efficiency by 300% using our solutions. Read their story.',
                        'scheduled_at' => '2025-09-15 11:00:00',
                        'status' => 'In Review',
                    ],
                    [
                        'platform' => 'facebook',
                        'copy' => '💼 Success Story: Meet Widget Corp, one of our amazing clients who transformed their business!',
                        'scheduled_at' => '2025-09-16 13:00:00',
                        'status' => 'Draft',
                    ],
                ]
            ],
            [
                'title' => 'Team Spotlight Series',
                'notes' => 'Highlight team members',
                'status' => 'Approved',
                'variants' => [
                    [
                        'platform' => 'instagram',
                        'copy' => '👋 Meet Sarah, our lead developer! She\'s been instrumental in our latest widget innovations.',
                        'scheduled_at' => '2025-09-14 15:00:00',
                        'status' => 'Approved',
                    ],
                    [
                        'platform' => 'linkedin',
                        'copy' => 'Employee Spotlight: Sarah Johnson, Senior Developer at Joy Demo Company, shares her journey in tech.',
                        'scheduled_at' => '2025-09-14 10:00:00',
                        'status' => 'Approved',
                    ],
                ]
            ],
        ];

        foreach ($concepts as $conceptData) {
            $concept = Concept::create([
                'workspace_id' => $workspace->id,
                'title' => $conceptData['title'],
                'notes' => $conceptData['notes'],
                'owner_id' => $agencyUser->id,
                'status' => $conceptData['status'],
                'due_date' => null,
            ]);

            foreach ($conceptData['variants'] as $variantData) {
                $variant = Variant::create([
                    'concept_id' => $concept->id,
                    'platform' => $variantData['platform'],
                    'copy' => $variantData['copy'],
                    'media_url' => null,
                    'scheduled_at' => $variantData['scheduled_at'],
                    'status' => $variantData['status'],
                    'trello_card_id' => null,
                ]);
            }
        }
    }
}
