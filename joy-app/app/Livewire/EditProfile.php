<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\RoleDetectionService;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Hash;

class EditProfile extends Component
{
    public ?User $currentUser = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|min:8|confirmed')]
    public ?string $password = null;

    public ?string $password_confirmation = null;

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

        // Populate form fields
        $this->name = $this->currentUser->name;
        $this->email = $this->currentUser->email;
    }

    public function save()
    {
        // Validate email uniqueness (except for current user)
        $this->validate([
            'email' => 'required|email|max:255|unique:users,email,' . $this->currentUser->id,
        ]);

        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
            ];

            // Only update password if provided
            if (!empty($this->password)) {
                $data['password'] = Hash::make($this->password);
            }

            $this->currentUser->update($data);

            // Clear password fields
            $this->password = null;
            $this->password_confirmation = null;

            session()->flash('success', 'Profile updated successfully!');

            return redirect()->route('profile');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update profile: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('profile');
    }

    public function render()
    {
        return view('livewire.edit-profile')
            ->layout('components.layouts.app');
    }
}