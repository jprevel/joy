<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\RoleDetectionService;
use Livewire\Component;

class UserProfile extends Component
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

        if (!$this->currentUser) {
            abort(401, 'Unauthorized');
        }

        // Load relationships
        $this->currentUser->load(['client', 'teams']);
    }

    public function render()
    {
        return view('livewire.user-profile')
            ->layout('components.layouts.app');
    }
}