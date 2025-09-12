<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RoleDetectionService
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_AGENCY = 'agency';
    public const ROLE_CLIENT = 'client';

    /**
     * Get the current authenticated user or fallback to demo user for role simulation
     */
    public function getCurrentUserRole(?string $fallbackRole = null): ?User
    {
        // If user is authenticated, return the actual authenticated user
        if (Auth::check()) {
            return Auth::user();
        }
        
        // Fallback to demo users for testing (when not authenticated)
        if ($fallbackRole) {
            return $this->getDemoUserForRole($fallbackRole);
        }
        
        return null;
    }

    /**
     * Detect the appropriate role for the current user
     */
    public function detectRole(?string $requestedRole = null): string
    {
        $user = Auth::user();
        
        if (!$user) {
            return $requestedRole ?? self::ROLE_CLIENT;
        }
        
        // If a specific role is requested, validate it's allowed for this user
        if ($requestedRole && $this->userCanAccessRole($user, $requestedRole)) {
            return $requestedRole;
        }
        
        // Auto-detect based on user's primary role
        return $this->getUserPrimaryRole($user);
    }

    /**
     * Get the primary role for a user (highest privilege)
     */
    public function getUserPrimaryRole(User $user): string
    {
        if ($user->hasRole('admin')) {
            return self::ROLE_ADMIN;
        }
        
        if ($user->hasRole('Account Manager') || $user->hasRole('agency')) {
            return self::ROLE_AGENCY;
        }
        
        return self::ROLE_CLIENT;
    }

    /**
     * Check if a user can access a specific role
     */
    public function userCanAccessRole(User $user, string $role): bool
    {
        return match($role) {
            self::ROLE_ADMIN => $user->hasRole('admin'),
            self::ROLE_AGENCY => $user->hasRole('admin') || $user->hasRole('Account Manager') || $user->hasRole('agency'),
            self::ROLE_CLIENT => true, // All users can access client view
            default => false,
        };
    }

    /**
     * Get available roles for a user
     */
    public function getAvailableRoles(User $user): array
    {
        $roles = [self::ROLE_CLIENT]; // Everyone can access client view
        
        if ($user->hasRole('Account Manager') || $user->hasRole('agency')) {
            $roles[] = self::ROLE_AGENCY;
        }
        
        if ($user->hasRole('admin')) {
            $roles[] = self::ROLE_ADMIN;
        }
        
        return array_unique($roles);
    }

    /**
     * Get demo user for role simulation (for testing purposes)
     */
    private function getDemoUserForRole(string $role): ?User
    {
        $demoUsers = [
            self::ROLE_CLIENT => User::whereHas('roles', fn($q) => $q->where('name', 'client'))->first(),
            self::ROLE_AGENCY => User::whereHas('roles', fn($q) => $q->where('name', 'agency'))->first(),
            self::ROLE_ADMIN => User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first(),
        ];
        
        return $demoUsers[$role] ?? null;
    }

    /**
     * Check if the current user has the specified permission
     */
    public function hasPermission(string $permission, ?string $role = null): bool
    {
        $user = $this->getCurrentUserRole($role);
        return $user?->can($permission) ?? false;
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(string $role): string
    {
        return match($role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_AGENCY => 'Agency Team',
            self::ROLE_CLIENT => 'Client',
            default => ucfirst($role),
        };
    }

    /**
     * Determine default redirect route based on user role
     */
    public function getDefaultRoute(?User $user = null): string
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return '/login';
        }
        
        $primaryRole = $this->getUserPrimaryRole($user);
        
        return match($primaryRole) {
            self::ROLE_ADMIN => '/admin',
            self::ROLE_AGENCY => '/calendar/agency',
            self::ROLE_CLIENT => '/calendar/client',
            default => '/calendar/client',
        };
    }
}