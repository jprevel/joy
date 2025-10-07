<?php

namespace App\Http\Middleware;

use App\Services\RoleDetectionService;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function __construct(
        private RoleDetectionService $roleDetectionService,
        private AuditService $auditService
    ) {}

    /**
     * Handle an incoming request requiring specific role.
     */
    public function handle(Request $request, Closure $next, string $requiredRole): Response
    {
        $user = $this->roleDetectionService->getCurrentUser();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        // Check if user can access the required role
        if (!$this->roleDetectionService->userCanAccessRole($user, $requiredRole)) {
            $userRole = $this->roleDetectionService->getUserPrimaryRole($user);

            // Log unauthorized access
            $this->auditService->log([
                'user_id' => $user->id,
                'client_id' => $user->client_id,
                'event' => 'role.access_denied',
                'auditable_type' => get_class($user),
                'auditable_id' => $user->id,
                'new_values' => [
                    'user_role' => $userRole,
                    'required_role' => $requiredRole,
                    'route' => $request->route()?->getName(),
                    'url' => $request->fullUrl(),
                    'ip_address' => $request->ip(),
                ],
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => "Role '{$requiredRole}' required"
                ], 403);
            }

            $redirectRoute = $this->roleDetectionService->getDefaultRoute($user);
            return redirect($redirectRoute)->with('error', "Access denied. Role '{$requiredRole}' required.");
        }

        return $next($request);
    }
}