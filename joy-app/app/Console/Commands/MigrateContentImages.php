<?php

namespace App\Console\Commands;

use App\Models\ContentItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MigrateContentImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:migrate-images {--dry-run : Show what would be migrated without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing content items to use local sample images instead of external URLs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        // Get sample images
        $sampleImages = [
            'content-images/sample/marketing-1.jpg',
            'content-images/sample/marketing-2.jpg', 
            'content-images/sample/marketing-3.jpg',
            'content-images/sample/marketing-4.jpg',
            'content-images/sample/marketing-5.jpg',
        ];
        
        // Filter to only existing images
        $availableImages = collect($sampleImages)->filter(function ($imagePath) {
            return Storage::disk('public')->exists($imagePath);
        })->values();
        
        if ($availableImages->isEmpty()) {
            $this->error('No sample images found in storage/app/public/content-images/sample/');
            return 1;
        }
        
        $this->info("Found {$availableImages->count()} sample images");
        
        // Get content items that need migration
        $contentItems = ContentItem::whereNotNull('media_url')
            ->whereNull('image_path')
            ->get();
            
        if ($contentItems->isEmpty()) {
            $this->info('No content items need migration');
            return 0;
        }
        
        $this->info("Found {$contentItems->count()} content items to migrate");
        
        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made');
            $this->table(['ID', 'Title', 'Current URL', 'Would use'], 
                $contentItems->map(function ($item, $index) use ($availableImages) {
                    $imageIndex = $index % $availableImages->count();
                    return [
                        $item->id,
                        $item->title,
                        $item->media_url,
                        $availableImages[$imageIndex]
                    ];
                })->toArray()
            );
            return 0;
        }
        
        $index = 0;
        $this->withProgressBar($contentItems, function ($item) use ($availableImages, &$index) {
            // Assign images in rotation
            $imageIndex = $index % $availableImages->count();
            $imagePath = $availableImages[$imageIndex];
            
            // Get file info
            $fullPath = Storage::disk('public')->path($imagePath);
            $filename = basename($imagePath);
            $mimeType = mime_content_type($fullPath);
            $fileSize = Storage::disk('public')->size($imagePath);
            
            // Update the content item
            $item->update([
                'image_path' => $imagePath,
                'image_filename' => $filename,
                'image_mime_type' => $mimeType,
                'image_size' => $fileSize,
            ]);
            
            $index++;
        });
        
        $this->newLine();
        $this->info("Successfully migrated {$contentItems->count()} content items to use local images");
        
        return 0;
    }
}
