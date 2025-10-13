<?php

namespace App\Livewire\Admin;

use App\Contracts\UserManagementContract;
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

    // CRUD Form properties
    public array $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'role' => '',
    ];

    public ?int $editingUserId = null;
    public bool $editingSelf = false;
    public bool $showCreateForm = false;
    public bool $showEditForm = false;

    protected RoleDetectionService $roleDetectionService;
    protected UserManagementContract $userService;

    public function boot(
        RoleDetectionService $roleDetectionService,
        UserManagementContract $userService
    ) {
        $this->roleDetectionService = $roleDetectionService;
        $this->userService = $userService;
    }

    public function mount()
    {
        $this->currentUser = auth()->user();

        if (!$this->currentUser) {
            abort(403, 'Authentication required');
        }

        if (!$this->currentUser->hasRole('admin')) {
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

    public function createUser()
    {
        try {
            $this->userService->createUser($this->form);

            $this->form = ['name' => '', 'email' => '', 'password' => '', 'role' => ''];
            $this->showCreateForm = false;

            session()->flash('message', 'User created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('form', $e->getMessage());
        }
    }

    public function editUser(int $userId)
    {
        $user = $this->userService->findUser($userId);

        $this->editingUserId = $userId;
        $this->editingSelf = ($userId === $this->currentUser->id);

        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $user->roles->first()?->name ?? '',
        ];

        $this->showEditForm = true;
    }

    public function updateUser()
    {
        if (!$this->editingUserId) {
            return;
        }

        try {
            $this->userService->updateUser($this->editingUserId, $this->form);

            $this->form = ['name' => '', 'email' => '', 'password' => '', 'role' => ''];
            $this->editingUserId = null;
            $this->editingSelf = false;
            $this->showEditForm = false;

            session()->flash('message', 'User updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('form', $e->getMessage());
        }
    }

    public function cancel()
    {
        $this->form = ['name' => '', 'email' => '', 'password' => '', 'role' => ''];
        $this->editingUserId = null;
        $this->editingSelf = false;
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->resetErrorBag();
    }

    public function deleteUser(int $userId)
    {
        try {
            $this->userService->deleteUser($userId);
            session()->flash('message', 'User deleted successfully');
        } catch (\Exception $e) {
            $this->addError('delete', $e->getMessage());
        }
    }

    public function restoreUser(int $userId)
    {
        try {
            $this->userService->restoreUser($userId);
            session()->flash('message', 'User restored successfully');
        } catch (\Exception $e) {
            $this->addError('restore', $e->getMessage());
        }
    }

    public function render()
    {
        // Include soft-deleted users
        $query = User::withTrashed()->with('roles');

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Apply role filter
        if (!empty($this->roleFilter)) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->roleFilter);
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $users = $query->paginate(15);
        $availableRoles = $this->userService->getAvailableRoles();

        return view('livewire.admin.user-management', [
            'users' => $users,
            'availableRoles' => $availableRoles,
        ])->layout('components.layouts.admin');
    }
}