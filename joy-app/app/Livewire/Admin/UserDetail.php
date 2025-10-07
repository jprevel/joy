<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\RoleDetectionService;
use Livewire\Component;

class UserDetail extends Component
{
    public ?User $user = null;
    public ?User $currentUser = null;

    public function __construct(
        private RoleDetectionService $roleDetectionService
    ) {
        parent::__construct();
    }

    public function mount(User $user)
    {
        $this->user = $user;
        $this->currentUser = $this->roleDetectionService->getCurrentUser();

        if (!$this->roleDetectionService->isAdmin($this->currentUser)) {
            abort(403, 'Admin access required');
        }

        $this->user->load(['client', 'teams']);
    }

    public function render()
    {
        return view('livewire.admin.user-detail')
            ->layout('components.layouts.admin');
    }
}