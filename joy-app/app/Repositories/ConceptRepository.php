<?php

namespace App\Repositories;

use App\Models\Concept;
use App\Repositories\Contracts\ConceptRepositoryInterface;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ConceptRepository implements ConceptRepositoryInterface
{
    public function getAllForWorkspace(int $workspaceId): Collection
    {
        return Concept::where('workspace_id', $workspaceId)
            ->with(['variants', 'owner', 'workspace'])
            ->get();
    }
    
    public function getByOwner(int $ownerId): Collection
    {
        return Concept::where('owner_id', $ownerId)
            ->with(['variants', 'owner', 'workspace'])
            ->get();
    }
    
    public function getByStatus(string $status): Collection
    {
        return Concept::where('status', $status)
            ->with(['variants', 'owner', 'workspace'])
            ->get();
    }
    
    public function getByDueDate(Carbon $date): Collection
    {
        return Concept::whereDate('due_date', $date)
            ->with(['variants', 'owner', 'workspace'])
            ->get();
    }
    
    public function find(int $id): ?Concept
    {
        return Concept::with(['variants', 'owner', 'workspace'])
            ->find($id);
    }
    
    public function create(array $data): Concept
    {
        return Concept::create($data);
    }
    
    public function update(int $id, array $data): bool
    {
        return Concept::where('id', $id)->update($data);
    }
    
    public function delete(int $id): bool
    {
        return Concept::where('id', $id)->delete();
    }
    
    public function getOverdueConcepts(): Collection
    {
        return Concept::where('due_date', '<', now())
            ->where('status', '!=', 'Completed')
            ->with(['variants', 'owner', 'workspace'])
            ->get();
    }
    
    public function getConceptsWithVariants(): Collection
    {
        return Concept::with(['variants' => function($query) {
            $query->with('comments')->withCount('comments');
        }, 'owner', 'workspace'])
            ->get();
    }
}