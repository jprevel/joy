<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\User;
use App\Services\RoleDetectionService;
use Livewire\Component;

class ClientDetail extends Component
{
    public ?Client $client = null;
    public ?User $currentUser = null;

    public function __construct(
        private RoleDetectionService $roleDetectionService
    ) {
        parent::__construct();
    }

    public function mount(Client $client)
    {
        $this->client = $client;
        $this->currentUser = $this->roleDetectionService->getCurrentUser();

        if (!$this->roleDetectionService->isAdmin($this->currentUser)) {
            abort(403, 'Admin access required');
        }
    }

    public function render()
    {
        return view('livewire.admin.client-detail')
            ->layout('components.layouts.admin');
    }
}