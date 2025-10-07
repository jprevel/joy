<?php

namespace App\Services;

use App\Models\ClientWorkspace;
use App\Repositories\Contracts\ContentItemRepositoryInterface;
use App\Helpers\PlatformHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContentCalendarService
{
    public function __construct(
        private ContentItemRepositoryInterface $contentItemRepository
    ) {}

    public function getAllContentItemsForWorkspace(int $workspaceId): Collection
    {
        $contentItems = $this->contentItemRepository->getAllForWorkspace($workspaceId);
        
        return $contentItems->map(function($contentItem) {
            return [
                'id' => $contentItem->id,
                'platform' => $contentItem->platform,
                'copy' => $contentItem->copy,
                'media_url' => $contentItem->media_url,
                'scheduled_at' => $contentItem->scheduled_at,
                'status' => $contentItem->status,
                'comment_count' => $contentItem->comments_count,
                'concept' => [
                    'id' => $contentItem->concept->id,
                    'title' => $contentItem->concept->title,
                    'status' => $contentItem->concept->status,
                ],
            ];
        });
    }
    
    public function getJoyDemoContentItems(): Collection
    {
        $workspace = ClientWorkspace::where('name', 'Joy Demo Company')->first();
        
        if (!$workspace) {
            return collect([]);
        }
        
        return $this->getAllContentItemsForWorkspace($workspace->id);
    }
    
    public static function getPlatformIcon(string $platform): string
    {
        return PlatformHelper::getIcon($platform);
    }
    
    public static function getPlatformColor(string $platform): string
    {
        return PlatformHelper::getBackgroundColor($platform);
    }
    
    public static function getStatusColor(string $status): string
    {
        $statusEnum = \App\Enums\ContentItemStatus::tryFrom($status);
        return $statusEnum ? $statusEnum->getColor() : 'bg-gray-400';
    }
}