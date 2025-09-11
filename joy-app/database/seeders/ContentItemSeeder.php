<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use App\Models\ContentItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing content items to prevent duplicates
        ContentItem::truncate();
        
        $clients = Client::with('team')->get();
        $agencyUsers = User::whereHas('roles', fn($q) => $q->where('name', 'agency'))->get();

        foreach ($clients as $client) {
            // Find agency users who belong to the same team as this client
            $teamUsers = $agencyUsers->filter(function($user) use ($client) {
                return $client->team && $user->teams->contains('id', $client->team_id);
            });
            
            // If no team users found, use any agency user
            $availableUsers = $teamUsers->isNotEmpty() ? $teamUsers : $agencyUsers;
            
            if ($availableUsers->isNotEmpty()) {
                // Create 1-2 campaigns per client (each creates 4 platform variants)
                $campaignCount = rand(1, 2);
                
                for ($i = 1; $i <= $campaignCount; $i++) {
                    $this->createContentItems($client, $availableUsers->random());
                }
            }
        }
    }

    private function createContentItems(Client $client, User $owner)
    {
        // Create client-specific campaigns
        $campaignTemplates = [
            'TechCorp Solutions' => [
                'AI Innovation Showcase',
                'Digital Transformation Summit',
                'Cloud Security Webinar Series',
                'Tech Leadership Spotlight'
            ],
            'Green Valley Wellness' => [
                'Mindful Living Workshop',
                'Organic Nutrition Guide',
                'Wellness Journey Stories',
                'Sustainable Health Tips'
            ],
            'Urban Kitchen Co' => [
                'Farm-to-Table Feature',
                'Chef\'s Special Recipes',
                'Local Ingredients Spotlight',
                'Cooking Class Series'
            ],
            'Bright Future Education' => [
                'Student Success Stories',
                'Educational Technology Trends',
                'Parent Engagement Tips',
                'Learning Innovation Showcase'
            ],
            'Creative Studio Arts' => [
                'Artist Collaboration Project',
                'Creative Process Behind-the-Scenes',
                'Art Community Showcase',
                'Design Inspiration Series'
            ],
            'Pacific Real Estate Group' => [
                'Market Trends Analysis',
                'Dream Home Features',
                'Neighborhood Spotlight',
                'Investment Opportunities'
            ],
            'NextGen Fitness' => [
                'Personal Training Success',
                'Fitness Challenge Series',
                'Nutrition & Exercise Tips',
                'Member Transformation Stories'
            ],
            'Coastal Coffee Roasters' => [
                'Bean Origin Stories',
                'Brewing Techniques Guide',
                'Coffee Culture Features',
                'Seasonal Blend Launch'
            ]
        ];

        $campaigns = $campaignTemplates[$client->name] ?? [
            'Brand Awareness Campaign',
            'Product Feature Series',
            'Customer Stories',
            'Industry Insights'
        ];

        // Select different platforms for variety (not always all 4)
        $allPlatforms = ['Facebook', 'Instagram', 'LinkedIn', 'Blog'];
        $platformCount = rand(2, 4); // Use 2-4 platforms per campaign
        $selectedPlatforms = array_slice(array_values(array_intersect_key(
            $allPlatforms, 
            array_flip(array_rand($allPlatforms, $platformCount))
        )), 0, $platformCount);
        
        $statuses = ['Draft', 'In Review', 'Approved', 'Scheduled'];
        
        $campaign = $campaigns[array_rand($campaigns)];
        
        // Create content items for selected platforms with unique dates
        $baseDate = now()->addDays(rand(1, 45)); // Start with a random base date
        foreach ($selectedPlatforms as $index => $platform) {
            // Spread content across different days to avoid clustering
            $scheduledDate = $baseDate->copy()->addDays($index * rand(2, 7))->addHours(rand(9, 17));
            
            ContentItem::create([
                'client_id' => $client->id,
                'title' => $campaign,
                'notes' => $this->getNotesForPlatform($platform, $client->name),
                'owner_id' => $owner->id,
                'platform' => $platform,
                'copy' => $this->getCopyForPlatform($platform, $client->name, $campaign),
                'media_url' => $this->getMediaUrl($platform),
                'scheduled_at' => $scheduledDate,
                'status' => $statuses[array_rand($statuses)],
            ]);
        }
    }

    private function getNotesForPlatform($platform, $clientName)
    {
        $notes = [
            'Facebook' => "Engagement-focused post for Facebook audience. Use brand colors and include call-to-action button.",
            'Instagram' => "Visual-first content with hashtags. Ensure image is optimized for mobile viewing.",
            'LinkedIn' => "Professional tone for B2B audience. Focus on industry insights and thought leadership.",
            'Blog' => "Long-form content for SEO. Include relevant keywords and internal links."
        ];

        return $notes[$platform] . " Target audience: {$clientName} customers and prospects.";
    }

    private function getCopyForPlatform($platform, $clientName, $campaign)
    {
        $copies = [
            'Facebook' => "🚀 Exciting news from {$clientName}! Our {$campaign} is here and we can't wait to share what we've been working on. Click the link in our bio to learn more! #Innovation #Excellence",
            'Instagram' => "✨ Behind the scenes of our {$campaign} ✨\n\nSwipe to see the journey → \n\n#{$clientName} #BehindTheScenes #ComingSoon",
            'LinkedIn' => "We're thrilled to announce our {$campaign} at {$clientName}. This initiative represents our commitment to delivering exceptional value to our clients and partners. Read more about our approach and what this means for the industry.",
            'Blog' => "# {$campaign}: A Deep Dive into {$clientName}'s Latest Initiative\n\nIn today's rapidly evolving marketplace, innovation isn't just an advantage—it's a necessity. Our {$campaign} represents months of research, development, and strategic planning..."
        ];

        return $copies[$platform];
    }

    private function getMediaUrl($platform)
    {
        $images = [
            'Facebook' => 'https://picsum.photos/1200/630?random=' . rand(1, 100),
            'Instagram' => 'https://picsum.photos/1080/1080?random=' . rand(1, 100),
            'LinkedIn' => 'https://picsum.photos/1200/627?random=' . rand(1, 100),
            'Blog' => 'https://picsum.photos/800/400?random=' . rand(1, 100),
        ];

        return $images[$platform];
    }
}