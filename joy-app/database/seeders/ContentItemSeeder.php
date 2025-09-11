<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\AgencyUser;
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
        $clients = Client::all();
        $agencyUsers = AgencyUser::all();

        foreach ($clients as $client) {
            // Create 3-5 content items per client
            $contentCount = rand(3, 5);
            
            for ($i = 1; $i <= $contentCount; $i++) {
                $this->createContentItems($client, $agencyUsers->random());
            }
        }
    }

    private function createContentItems(Client $client, AgencyUser $owner)
    {
        $campaigns = [
            'Summer Product Launch',
            'Holiday Season Campaign',
            'New Year Wellness Series', 
            'Back to School Special',
            'Black Friday Promotion',
            'Customer Success Stories',
            'Industry Thought Leadership',
            'Behind the Scenes Content'
        ];

        $platforms = ['facebook', 'instagram', 'linkedin', 'blog'];
        $statuses = ['Draft', 'In Review', 'Approved', 'Scheduled'];
        
        $campaign = $campaigns[array_rand($campaigns)];
        
        // Create content items for different platforms
        foreach ($platforms as $platform) {
            ContentItem::create([
                'client_id' => $client->id,
                'title' => $campaign,
                'notes' => $this->getNotesForPlatform($platform, $client->name),
                'owner_id' => $owner->id,
                'platform' => $platform,
                'copy' => $this->getCopyForPlatform($platform, $client->name, $campaign),
                'media_url' => $this->getMediaUrl($platform),
                'scheduled_at' => now()->addDays(rand(1, 30))->addHours(rand(9, 17)),
                'status' => $statuses[array_rand($statuses)],
            ]);
        }
    }

    private function getNotesForPlatform($platform, $clientName)
    {
        $notes = [
            'facebook' => "Engagement-focused post for Facebook audience. Use brand colors and include call-to-action button.",
            'instagram' => "Visual-first content with hashtags. Ensure image is optimized for mobile viewing.",
            'linkedin' => "Professional tone for B2B audience. Focus on industry insights and thought leadership.",
            'blog' => "Long-form content for SEO. Include relevant keywords and internal links."
        ];

        return $notes[$platform] . " Target audience: {$clientName} customers and prospects.";
    }

    private function getCopyForPlatform($platform, $clientName, $campaign)
    {
        $copies = [
            'facebook' => "🚀 Exciting news from {$clientName}! Our {$campaign} is here and we can't wait to share what we've been working on. Click the link in our bio to learn more! #Innovation #Excellence",
            'instagram' => "✨ Behind the scenes of our {$campaign} ✨\n\nSwipe to see the journey → \n\n#{$clientName} #BehindTheScenes #ComingSoon",
            'linkedin' => "We're thrilled to announce our {$campaign} at {$clientName}. This initiative represents our commitment to delivering exceptional value to our clients and partners. Read more about our approach and what this means for the industry.",
            'blog' => "# {$campaign}: A Deep Dive into {$clientName}'s Latest Initiative\n\nIn today's rapidly evolving marketplace, innovation isn't just an advantage—it's a necessity. Our {$campaign} represents months of research, development, and strategic planning..."
        ];

        return $copies[$platform];
    }

    private function getMediaUrl($platform)
    {
        $images = [
            'facebook' => 'https://picsum.photos/1200/630?random=' . rand(1, 100),
            'instagram' => 'https://picsum.photos/1080/1080?random=' . rand(1, 100),
            'linkedin' => 'https://picsum.photos/1200/627?random=' . rand(1, 100),
            'blog' => 'https://picsum.photos/800/400?random=' . rand(1, 100),
        ];

        return $images[$platform];
    }
}