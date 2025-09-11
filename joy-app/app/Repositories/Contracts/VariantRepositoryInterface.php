<?php

namespace App\Repositories\Contracts;

use App\Models\Variant;
use Illuminate\Support\Collection;

interface VariantRepositoryInterface
{
    public function getAllForWorkspace(int $workspaceId): Collection;
    
    public function getByConceptId(int $conceptId): Collection;
    
    public function getByPlatform(string $platform): Collection;
    
    public function getByStatus(string $status): Collection;
    
    public function getScheduledVariants(): Collection;
    
    public function find(int $id): ?Variant;
    
    public function create(array $data): Variant;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function getByPlatformAndStatus(string $platform, string $status): Collection;
    
    public function getVariantsWithCommentCount(): Collection;
}