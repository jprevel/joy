<?php

namespace App\Repositories\Contracts;

use App\Models\Concept;
use Illuminate\Support\Collection;

interface ConceptRepositoryInterface
{
    public function getAllForWorkspace(int $workspaceId): Collection;
    
    public function getByOwner(int $ownerId): Collection;
    
    public function getByStatus(string $status): Collection;
    
    public function getByDueDate(\Carbon\Carbon $date): Collection;
    
    public function find(int $id): ?Concept;
    
    public function create(array $data): Concept;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function getOverdueConcepts(): Collection;
    
    public function getConceptsWithVariants(): Collection;
}