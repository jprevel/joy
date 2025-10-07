<?php

namespace App\Repositories;

use App\Models\ContentItem;
use App\Repositories\Contracts\ContentItemRepositoryInterface;
use Illuminate\Support\Collection;

class ContentItemRepository implements ContentItemRepositoryInterface
{
    public function getAllForWorkspace(int $workspaceId): Collection
    {
        return ContentItem::whereHas('concept', function($query) use ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        })
        ->with(['concept', 'comments'])
        ->withCount('comments')
        ->get();
    }
    
    public function getByConceptId(int $conceptId): Collection
    {
        return ContentItem::where('concept_id', $conceptId)
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
    
    public function getByPlatform(string $platform): Collection
    {
        return ContentItem::where('platform', $platform)
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
    
    public function getByStatus(string $status): Collection
    {
        return ContentItem::where('status', $status)
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
    
    public function getScheduledContentItems(): Collection
    {
        return ContentItem::whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->orderBy('scheduled_at')
            ->get();
    }
    
    public function find(int $id): ?ContentItem
    {
        return ContentItem::with(['concept', 'comments'])
            ->withCount('comments')
            ->find($id);
    }
    
    public function create(array $data): ContentItem
    {
        return ContentItem::create($data);
    }
    
    public function update(int $id, array $data): bool
    {
        return ContentItem::where('id', $id)->update($data);
    }
    
    public function delete(int $id): bool
    {
        return ContentItem::where('id', $id)->delete();
    }
    
    public function getByPlatformAndStatus(string $platform, string $status): Collection
    {
        return ContentItem::where('platform', $platform)
            ->where('status', $status)
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
    
    public function getContentItemsWithCommentCount(): Collection
    {
        return ContentItem::with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
}