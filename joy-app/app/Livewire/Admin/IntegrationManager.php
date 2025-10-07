<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\RoleDetectionService;
use Livewire\Component;

class IntegrationManager extends Component
{
    public ?User $currentUser = null;

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

    public function render()
    {
        return view('livewire.admin.integration-manager')
            ->layout('components.layouts.admin');
    }
}