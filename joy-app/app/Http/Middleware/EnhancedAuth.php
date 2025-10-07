<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use App\Services\RoleDetectionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnhancedAuth
{
    public function __construct(
        private AuditService $auditService,
        private RoleDetectionService $roleDetectionService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            $this->logFailedAuth($request, 'not_authenticated');

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            return redirect()->guest(route('login'));
        }

        $user = Auth::user();

        // Check if user account is active
        if (!$user->is_active ?? true) {
            $this->logFailedAuth($request, 'account_inactive', $user->id);
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Account inactive'], 403);
            }

            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Update last login timestamp
        $this->updateLastLogin($user);

        // Log successful authentication
        $this->logSuccessfulAuth($request, $user);

        return $next($request);
    }

    /**
     * Update user's last login timestamp.
     */
    private function updateLastLogin($user): void
    {
        try {
            $user->update(['last_login_at' => now()]);
        } catch (\Exception $e) {
            // Silently fail if updating last login fails
            \Log::warning('Failed to update last login time', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log successful authentication.
     */
    private function logSuccessfulAuth(Request $request, $user): void
    {
        $this->auditService->log([
            'user_id' => $user->id,
            'event' => 'auth.success',
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'new_values' => [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'role' => $this->roleDetectionService->getUserPrimaryRole($user),
            ],
        ]);
    }

    /**
     * Log failed authentication attempt.
     */
    private function logFailedAuth(Request $request, string $reason, ?int $userId = null): void
    {
        $this->auditService->log([
            'user_id' => $userId,
            'event' => 'auth.failed',
            'auditable_type' => 'User',
            'auditable_id' => $userId,
            'new_values' => [
                'reason' => $reason,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ],
        ]);
    }
}