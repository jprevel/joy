<?php

namespace App\Services;

use App\Models\ClientWorkspace;
use App\Repositories\Contracts\VariantRepositoryInterface;
use App\Helpers\PlatformHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContentCalendarService
{
    public function __construct(
        private VariantRepositoryInterface $variantRepository
    ) {}

    public function getAllVariantsForWorkspace(int $workspaceId): Collection
    {
        $variants = $this->variantRepository->getAllForWorkspace($workspaceId);
        
        return $variants->map(function($variant) {
            return [
                'id' => $variant->id,
                'platform' => $variant->platform,
                'copy' => $variant->copy,
                'media_url' => $variant->media_url,
                'scheduled_at' => $variant->scheduled_at,
                'status' => $variant->status,
                'comment_count' => $variant->comments_count,
                'concept' => [
                    'id' => $variant->concept->id,
                    'title' => $variant->concept->title,
                    'status' => $variant->concept->status,
                ],
            ];
        });
    }
    
    public function getJoyDemoVariants(): Collection
    {
        $workspace = ClientWorkspace::where('name', 'Joy Demo Company')->first();
        
        if (!$workspace) {
            return collect([]);
        }
        
        return $this->getAllVariantsForWorkspace($workspace->id);
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
        $statusEnum = \App\Enums\VariantStatus::tryFrom($status);
        return $statusEnum ? $statusEnum->getColor() : 'bg-gray-400';
    }
}