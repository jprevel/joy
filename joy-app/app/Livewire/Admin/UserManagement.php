<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\RoleDetectionService;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public ?User $currentUser = null;
    public string $search = '';
    public string $roleFilter = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';

    public function __construct(
        private RoleDetectionService $roleDetectionService
    ) {
        parent::__construct();
    }

    public function mount()
    {
        $this->currentUser = $this->roleDetectionService->getCurrentUser();

        if (!$this->roleDetectionService->isAdmin($this->currentUser)) {
            abort(403, 'Admin access required');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedRoleFilter()
    {
        $this->resetPage();
    }

    public function sortBy(string $field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $query = User::with(['client', 'teams']);

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Apply role filter
        if (!empty($this->roleFilter)) {
            $query->where('role', $this->roleFilter);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $users = $query->paginate(15);

        return view('livewire.admin.user-management', [
            'users' => $users,
        ])->layout('components.layouts.admin');
    }
}