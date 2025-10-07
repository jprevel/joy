<?php

namespace App\Http\Middleware;

use App\Services\RoleDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleDetection
{
    public function __construct(
        private RoleDetectionService $roleDetectionService
    ) {}

    /**
     * Handle an incoming request.
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

        // Detect and set the current role context
        $requestedRole = $request->route('role') ?? $request->input('role');
        $detectedRole = $this->roleDetectionService->detectRole($requestedRole);

        // Add role information to the request
        $request->attributes->set('user_role', $detectedRole);
        $request->attributes->set('user_permissions', $this->roleDetectionService->getRolePermissions($detectedRole));
        $request->attributes->set('available_roles', $this->roleDetectionService->getAvailableRoles($user));

        // Share role information with views
        view()->share('currentRole', $detectedRole);
        view()->share('availableRoles', $this->roleDetectionService->getAvailableRoles($user));
        view()->share('userPermissions', $this->roleDetectionService->getRolePermissions($detectedRole));

        return $next($request);
    }
}