<?php

namespace App\Services;

use App\Contracts\UserManagementContract;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserManagementService implements UserManagementContract
{
    /**
     * Get paginated list of all users including soft-deleted ones
     */
    public function listUsers(int $perPage = 15, bool $includeTrashed = true): LengthAwarePaginator
    {
        $query = $includeTrashed ? User::withTrashed() : User::query();
        return $query->with('roles')->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new user with specified role
     */
    public function createUser(array $data): User
    {
        $validated = $this->validateUserData($data);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if (isset($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        return $user->load('roles');
    }

    /**
     * Update existing user details
     */
    public function updateUser(int $userId, array $data): User
    {
        $validated = $this->validateUserData($data, $userId);
        $user = $this->findUser($userId);

        // Only update password if provided
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $user->update($updateData);

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return $user->fresh('roles');
    }

    /**
     * Soft delete a user (preserves relationships)
     */
    public function deleteUser(int $userId): bool
    {
        $user = $this->findUser($userId);
        return $user->delete();
    }

    /**
     * Restore a soft-deleted user
     */
    public function restoreUser(int $userId): User
    {
        $user = $this->findUser($userId, true);

        if (!$user->trashed()) {
            throw new \RuntimeException('User is not deleted');
        }

        $user->restore();
        return $user->fresh('roles');
    }

    /**
     * Get user by ID including soft-deleted users
     */
    public function findUser(int $userId, bool $includeTrashed = true): User
    {
        $query = $includeTrashed ? User::withTrashed() : User::query();
        return $query->with('roles')->findOrFail($userId);
    }

    /**
     * Get available roles for user assignment
     */
    public function getAvailableRoles(): array
    {
        return Role::pluck('name')->toArray();
    }

    /**
     * Check if user can modify another user
     */
    public function canModifyUser(User $currentUser, int $targetUserId): bool
    {
        // Admins can modify anyone including themselves
        return $currentUser->hasRole('admin');
    }

    /**
     * Validate user data before creation or update
     */
    public function validateUserData(array $data, ?int $userId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['sometimes', 'string', 'exists:roles,name'],
        ];

        // Email uniqueness rule
        $emailRule = 'unique:users,email';
        if ($userId !== null) {
            $emailRule .= ',' . $userId;
            // Password is optional for updates
            $rules['password'] = ['sometimes', 'nullable', 'string', 'min:8'];
        } else {
            // Password is required for new users
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $rules['email'][] = $emailRule;

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
