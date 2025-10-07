<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContentItemService
{
    public function __construct(
        private ImageUploadService $imageUploadService
    ) {}

    /**
     * Create multiple content items from form data
     */
    public function createContentItems(array $contentItems, int $clientId): array
    {
        $defaultStatus = $this->getDefaultStatus();
        $createdItems = [];

        foreach ($contentItems as $itemData) {
            $contentItem = $this->createSingleContentItem($itemData, $clientId, $defaultStatus);
            
            if (isset($itemData['image']) && $itemData['image']) {
                $this->imageUploadService->storeContentItemImage($contentItem, $itemData['image']);
            }
            
            $createdItems[] = $contentItem;
        }

        return $createdItems;
    }

    /**
     * Create a single content item
     */
    private function createSingleContentItem(array $itemData, int $clientId, ?Status $defaultStatus): ContentItem
    {
        return ContentItem::create([
            'client_id' => $clientId,
            'title' => $itemData['title'],
            'copy' => $itemData['copy'] ?? '',
            'platform' => $itemData['platform'], // Platform should already be lowercase from config
            'scheduled_at' => Carbon::parse($itemData['scheduled_at'])->startOfDay(),
            'status_id' => $defaultStatus?->id,
            'status' => strtolower($defaultStatus?->name ?? 'draft'), // Convert to lowercase to match enum
            'user_id' => auth()->id() ?? 1, // Use authenticated user or fallback to user 1
        ]);
    }

    /**
     * Get the default status for new content items
     */
    private function getDefaultStatus(): ?Status
    {
        return Status::where('name', 'Draft')->first();
    }

    /**
     * Validate content items data
     */
    public function validateContentItems(array $contentItems): array
    {
        $rules = [];
        $platforms = config('platforms.available', ['Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog']);

        foreach ($contentItems as $index => $item) {
            $rules["contentItems.{$index}.title"] = 'required|string|max:255';
            $rules["contentItems.{$index}.platform"] = 'required|in:' . implode(',', $platforms);
            $rules["contentItems.{$index}.scheduled_at"] = 'required|date_format:Y-m-d';
            $rules["contentItems.{$index}.image"] = 'nullable|image|max:1024'; // 1MB to match PHP limits
        }

        return $rules;
    }
}