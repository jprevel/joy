<?php

namespace App\Repositories;

use App\Models\Variant;
use App\Repositories\Contracts\VariantRepositoryInterface;
use Illuminate\Support\Collection;

class VariantRepository implements VariantRepositoryInterface
{
    public function getAllForWorkspace(int $workspaceId): Collection
    {
        return Variant::whereHas('concept', function($query) use ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        })
        ->with(['concept', 'comments'])
        ->withCount('comments')
        ->get();
    }
    
    public function getByConceptId(int $conceptId): Collection
    {
        return Variant::where('concept_id', $conceptId)
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
    
    public function getByPlatform(string $platform): Collection
    {
        return Variant::where('platform', $platform)
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
    
    public function getByStatus(string $status): Collection
    {
        return Variant::where('status', $status)
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
    
    public function getScheduledVariants(): Collection
    {
        return Variant::whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->orderBy('scheduled_at')
            ->get();
    }
    
    public function find(int $id): ?Variant
    {
        return Variant::with(['concept', 'comments'])
            ->withCount('comments')
            ->find($id);
    }
    
    public function create(array $data): Variant
    {
        return Variant::create($data);
    }
    
    public function update(int $id, array $data): bool
    {
        return Variant::where('id', $id)->update($data);
    }
    
    public function delete(int $id): bool
    {
        return Variant::where('id', $id)->delete();
    }
    
    public function getByPlatformAndStatus(string $platform, string $status): Collection
    {
        return Variant::where('platform', $platform)
            ->where('status', $status)
            ->with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
    
    public function getVariantsWithCommentCount(): Collection
    {
        return Variant::with(['concept', 'comments'])
            ->withCount('comments')
            ->get();
    }
}