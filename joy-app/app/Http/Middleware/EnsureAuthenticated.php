<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\RoleDetectionService;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function __construct(
        private RoleDetectionService $roleDetection
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->roleDetection->getCurrentUser();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->merge(['authenticated_user' => $user]);

        return $next($request);
    }
}
