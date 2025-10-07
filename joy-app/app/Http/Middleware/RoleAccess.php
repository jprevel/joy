<?php

namespace App\Http\Middleware;

use App\Services\RoleDetectionService;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAccess
{
    public function __construct(
        private RoleDetectionService $roleDetectionService,
        private AuditService $auditService
    ) {}

    /**
     * Handle role access validation for role switching functionality.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->roleDetectionService->getCurrentUser();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        $requestedRole = $request->route('role');

        // If no specific role requested, allow access
        if (!$requestedRole) {
            return $next($request);
        }

        // Validate role parameter
        if (!$this->roleDetectionService->isValidRole($requestedRole)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Invalid role specified'], 422);
            }
            return redirect()->back()->with('error', 'Invalid role specified');
        }

        // Check if user can access the requested role
        if (!$this->roleDetectionService->userCanAccessRole($user, $requestedRole)) {
            $userRole = $this->roleDetectionService->getUserPrimaryRole($user);
            $availableRoles = $this->roleDetectionService->getAvailableRoles($user);

            // Log role switch attempt
            $this->auditService->log([
                'user_id' => $user->id,
                'client_id' => $user->client_id,
                'event' => 'role.switch_denied',
                'auditable_type' => get_class($user),
                'auditable_id' => $user->id,
                'new_values' => [
                    'current_role' => $userRole,
                    'requested_role' => $requestedRole,
                    'available_roles' => $availableRoles,
                    'route' => $request->route()?->getName(),
                    'url' => $request->fullUrl(),
                    'ip_address' => $request->ip(),
                ],
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Role access denied',
                    'message' => "Cannot switch to role '{$requestedRole}'",
                    'available_roles' => $availableRoles
                ], 403);
            }

            return redirect()->back()->with('error', "You cannot access the '{$requestedRole}' role.");
        }

        // Log successful role switch
        $currentRole = $this->roleDetectionService->getUserPrimaryRole($user);
        if ($currentRole !== $requestedRole) {
            $this->roleDetectionService->logRoleSwitch($user, $currentRole, $requestedRole);
        }

        // Add role context to request
        $request->attributes->set('requested_role', $requestedRole);

        return $next($request);
    }
}