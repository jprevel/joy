<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\RoleDetectionService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

class ClientList extends Component
{
    use WithPagination;

    public ?User $currentUser = null;
    public Collection $clients;
    public string $search = '';
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

        if (!$this->hasPermission('view clients')) {
            abort(403, 'Access denied');
        }

        $this->loadClients();
    }

    public function loadClients()
    {
        if (!$this->currentUser) {
            $this->clients = collect();
            return;
        }

        $this->clients = $this->roleDetectionService->getAccessibleClients($this->currentUser);

        // Apply search filter
        if (!empty($this->search)) {
            $this->clients = $this->clients->filter(function ($client) {
                return stripos($client->name, $this->search) !== false ||
                       stripos($client->email, $this->search) !== false;
            });
        }

        // Apply sorting
        $this->clients = $this->clients->sortBy($this->sortBy, SORT_REGULAR, $this->sortDirection === 'desc');
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->loadClients();
    }

    public function sortBy(string $field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }

        $this->loadClients();
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->currentUser) {
            return false;
        }

        return $this->roleDetectionService->hasPermission($this->currentUser, $permission);
    }

    public function render()
    {
        return view('livewire.client-list')
            ->layout('components.layouts.app');
    }
}