<?php

namespace App\Repositories\Contracts;

use App\Models\ContentItem;
use Illuminate\Support\Collection;

interface ContentItemRepositoryInterface
{
    public function getAllForWorkspace(int $workspaceId): Collection;
    
    public function getByConceptId(int $conceptId): Collection;
    
    public function getByPlatform(string $platform): Collection;
    
    public function getByStatus(string $status): Collection;
    
    public function getScheduledContentItems(): Collection;
    
    public function find(int $id): ?ContentItem;
    
    public function create(array $data): ContentItem;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function getByPlatformAndStatus(string $platform, string $status): Collection;
    
    public function getContentItemsWithCommentCount(): Collection;
}