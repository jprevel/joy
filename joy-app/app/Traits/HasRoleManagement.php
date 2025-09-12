<?php

namespace App\Traits;

use App\Models\User;
use App\Services\RoleDetectionService;

trait HasRoleManagement
{
    /**
     * Get the current authenticated user or fallback to demo user for role simulation.
     */
    public function getCurrentUserRole(): ?User
    {
        $roleService = app(RoleDetectionService::class);
        return $roleService->getCurrentUserRole($this->currentRole ?? null);
    }

    /**
     * Check if the current user has the specified permission.
     */
    public function hasPermission(string $permission): bool
    {
        $roleService = app(RoleDetectionService::class);
        return $roleService->hasPermission($permission, $this->currentRole ?? null);
    }
}