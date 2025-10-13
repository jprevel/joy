<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for User Management operations in admin section
 *
 * Defines CRUD operations for User entities with soft delete support,
 * role assignment, and audit logging integration.
 *
 * @package App\Contracts
 */
interface UserManagementContract
{
    /**
     * Get paginated list of all users including soft-deleted ones
     *
     * @param int $perPage Number of users per page
     * @param bool $includeTrashed Whether to include soft-deleted users
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listUsers(int $perPage = 15, bool $includeTrashed = true);

    /**
     * Create a new user with specified role
     *
     * @param array $data User data [name, email, password, role]
     * @return User Created user instance
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createUser(array $data): User;

    /**
     * Update existing user details
     *
     * @param int $userId User ID to update
     * @param array $data Updated user data [name, email, role, password?]
     * @return User Updated user instance
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateUser(int $userId, array $data): User;

    /**
     * Soft delete a user (preserves relationships)
     *
     * @param int $userId User ID to delete
     * @return bool True if deleted successfully
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteUser(int $userId): bool;

    /**
     * Restore a soft-deleted user
     *
     * @param int $userId User ID to restore
     * @return User Restored user instance
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restoreUser(int $userId): User;

    /**
     * Get user by ID including soft-deleted users
     *
     * @param int $userId User ID
     * @param bool $includeTrashed Whether to include soft-deleted users
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findUser(int $userId, bool $includeTrashed = true): User;

    /**
     * Get available roles for user assignment
     *
     * @return array Array of role names ['Admin', 'Agency Team', 'Client']
     */
    public function getAvailableRoles(): array;

    /**
     * Check if user can modify another user (prevent self-role-change issues)
     *
     * @param User $currentUser The admin performing the action
     * @param int $targetUserId The user being modified
     * @return bool True if modification is allowed
     */
    public function canModifyUser(User $currentUser, int $targetUserId): bool;

    /**
     * Validate user data before creation or update
     *
     * @param array $data User data to validate
     * @param int|null $userId User ID for updates (null for creation)
     * @return array Validated data
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateUserData(array $data, ?int $userId = null): array;
}
