<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use App\Services\RoleDetectionService;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    public function __construct(
        private RateLimiter $limiter,
        private AuditService $auditService,
        private RoleDetectionService $roleDetectionService
    ) {}

    /**
     * Handle an incoming request with rate limiting.
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        // Apply role-based rate limiting
        [$adjustedMaxAttempts, $adjustedDecayMinutes] = $this->adjustLimitsForRole($request, $maxAttempts, $decayMinutes);

        if ($this->limiter->tooManyAttempts($key, $adjustedMaxAttempts)) {
            $this->logRateLimitExceeded($request, $key, $adjustedMaxAttempts);
            return $this->buildResponse($key, $adjustedMaxAttempts, $adjustedDecayMinutes);
        }

        $this->limiter->hit($key, $adjustedDecayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $adjustedMaxAttempts,
            $this->calculateRemainingAttempts($key, $adjustedMaxAttempts)
        );
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $this->roleDetectionService->getCurrentUser();

        // For authenticated users, use user ID
        if ($user) {
            return 'api_rate_limit:user:' . $user->id;
        }

        // For magic link access, use the magic link token
        $magicLink = $request->attributes->get('magic_link');
        if ($magicLink) {
            return 'api_rate_limit:magic_link:' . $magicLink->id;
        }

        // For unauthenticated requests, use IP address
        return 'api_rate_limit:ip:' . $request->ip();
    }

    /**
     * Adjust rate limits based on user role.
     */
    protected function adjustLimitsForRole(Request $request, int $maxAttempts, int $decayMinutes): array
    {
        $user = $this->roleDetectionService->getCurrentUser();

        if (!$user) {
            // Magic link or unauthenticated users get lower limits
            return [max(1, intval($maxAttempts * 0.5)), $decayMinutes];
        }

        $userRole = $this->roleDetectionService->getUserPrimaryRole($user);

        return match($userRole) {
            'admin' => [$maxAttempts * 3, max(1, intval($decayMinutes * 0.5))], // Higher limits, faster reset
            'agency' => [$maxAttempts * 2, $decayMinutes], // Higher limits
            'client' => [$maxAttempts, $decayMinutes], // Standard limits
            default => [max(1, intval($maxAttempts * 0.5)), $decayMinutes * 2] // Lower limits for unknown roles
        };
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->limiter->attempts($key));
    }

    /**
     * Add rate limit headers to the response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }

    /**
     * Create a response when rate limit is exceeded.
     */
    protected function buildResponse(string $key, int $maxAttempts, int $decayMinutes): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ];

        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after_seconds' => $retryAfter,
            'retry_after_human' => $this->formatRetryAfter($retryAfter)
        ], 429, $headers);
    }

    /**
     * Format retry after time in human readable format.
     */
    protected function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = intval($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($remainingSeconds === 0) {
            return $minutes === 1 ? "1 minute" : "{$minutes} minutes";
        }

        return $minutes === 1
            ? "1 minute and {$remainingSeconds} seconds"
            : "{$minutes} minutes and {$remainingSeconds} seconds";
    }

    /**
     * Log when rate limit is exceeded.
     */
    protected function logRateLimitExceeded(Request $request, string $key, int $maxAttempts): void
    {
        $user = $this->roleDetectionService->getCurrentUser();
        $magicLink = $request->attributes->get('magic_link');

        $this->auditService->log([
            'user_id' => $user?->id,
            'client_id' => $user?->client_id ?? $magicLink?->client_id,
            'event' => 'rate_limit.exceeded',
            'new_values' => [
                'rate_limit_key' => $key,
                'max_attempts' => $maxAttempts,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'user_role' => $user ? $this->roleDetectionService->getUserPrimaryRole($user) : null,
                'magic_link_id' => $magicLink?->id,
            ],
        ]);

        \Log::warning('API rate limit exceeded', [
            'key' => $key,
            'max_attempts' => $maxAttempts,
            'ip' => $request->ip(),
            'user_id' => $user?->id,
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl()
        ]);
    }
}