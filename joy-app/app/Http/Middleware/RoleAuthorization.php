<?php

namespace App\Http\Middleware;

use App\Services\RoleDetectionService;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAuthorization
{
    public function __construct(
        private RoleDetectionService $roleDetectionService,
        private AuditService $auditService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $user = $this->roleDetectionService->getCurrentUser();

        if (!$user) {
            $this->logUnauthorizedAccess($request, 'no_user', $allowedRoles);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return redirect()->route('login');
        }

        $userRole = $this->roleDetectionService->getUserPrimaryRole($user);

        // Check if user has any of the allowed roles
        if (!$this->hasAllowedRole($user, $allowedRoles)) {
            $this->logUnauthorizedAccess($request, 'insufficient_role', $allowedRoles, $userRole, $user->id);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden - Insufficient role permissions'], 403);
            }

            // Redirect based on user's actual role
            $redirectRoute = $this->roleDetectionService->getDefaultRoute($user);
            return redirect($redirectRoute)->with('error', 'You do not have permission to access this area.');
        }

        // Log successful authorization
        $this->logSuccessfulAccess($request, $user, $userRole, $allowedRoles);

        return $next($request);
    }

    /**
     * Check if user has any of the allowed roles.
     */
    private function hasAllowedRole($user, array $allowedRoles): bool
    {
        $userRole = $this->roleDetectionService->getUserPrimaryRole($user);

        // Check direct role match
        if (in_array($userRole, $allowedRoles)) {
            return true;
        }

        // Check if any allowed role can be accessed by user
        foreach ($allowedRoles as $allowedRole) {
            if ($this->roleDetectionService->userCanAccessRole($user, $allowedRole)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log successful authorization.
     */
    private function logSuccessfulAccess(Request $request, $user, string $userRole, array $allowedRoles): void
    {
        $this->auditService->log([
            'user_id' => $user->id,
            'client_id' => $user->client_id,
            'event' => 'role.authorized',
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'new_values' => [
                'user_role' => $userRole,
                'allowed_roles' => $allowedRoles,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
            ],
        ]);
    }

    /**
     * Log unauthorized access attempt.
     */
    private function logUnauthorizedAccess(Request $request, string $reason, array $allowedRoles, ?string $userRole = null, ?int $userId = null): void
    {
        $this->auditService->log([
            'user_id' => $userId,
            'event' => 'role.unauthorized',
            'auditable_type' => 'User',
            'auditable_id' => $userId,
            'new_values' => [
                'reason' => $reason,
                'user_role' => $userRole,
                'allowed_roles' => $allowedRoles,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ],
        ]);
    }
}