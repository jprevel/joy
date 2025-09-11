<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait HasRoleManagement
{
    /**
     * Get the current authenticated user or fallback to demo user for role simulation.
     */
    public function getCurrentUserRole(): ?User
    {
        // If user is authenticated, return the actual authenticated user
        if (Auth::check()) {
            return Auth::user();
        }
        
        // Fallback to demo users for testing (when not authenticated)
        $demoUsers = [
            'client' => User::whereHas('roles', fn($q) => $q->where('name', 'client'))->first(),
            'agency' => User::whereHas('roles', fn($q) => $q->where('name', 'agency'))->first(),
            'admin' => User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first(),
        ];
        
        return $demoUsers[$this->currentRole] ?? null;
    }

    /**
     * Check if the current user has the specified permission.
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->getCurrentUserRole();
        return $user?->can($permission) ?? false;
    }
}