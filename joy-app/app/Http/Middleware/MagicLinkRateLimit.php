<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MagicLinkRateLimit
{
    public function __construct(
        private RateLimiter $limiter,
        private AuditService $auditService
    ) {}

    /**
     * Handle rate limiting for magic link access attempts.
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '5', string $decayMinutes = '15'): Response
    {
        $token = $request->route('token') ?? $request->query('token');

        if (!$token) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request, $token);
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $this->logRateLimitExceeded($request, $token, $maxAttempts);
            return $this->buildResponse($key, $maxAttempts, $decayMinutes);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Only count failed attempts for rate limiting
        if ($response->getStatusCode() >= 400) {
            $this->logFailedAttempt($request, $token);
        }

        return $response;
    }

    /**
     * Resolve the request signature for magic link rate limiting.
     */
    protected function resolveRequestSignature(Request $request, string $token): string
    {
        // Combine IP and truncated token for rate limiting
        $truncatedToken = substr($token, 0, 8);
        return "magic_link_attempts:{$request->ip()}:{$truncatedToken}";
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
        ];

        return response()->json([
            'error' => 'Too Many Attempts',
            'message' => 'Too many magic link access attempts. Please try again later.',
            'retry_after_seconds' => $retryAfter,
            'retry_after_minutes' => ceil($retryAfter / 60)
        ], 429, $headers);
    }

    /**
     * Log when magic link rate limit is exceeded.
     */
    protected function logRateLimitExceeded(Request $request, string $token, int $maxAttempts): void
    {
        $this->auditService->log([
            'event' => 'magic_link.rate_limit_exceeded',
            'new_values' => [
                'token' => substr($token, 0, 8) . '...',
                'max_attempts' => $maxAttempts,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ],
        ]);

        \Log::warning('Magic link rate limit exceeded', [
            'token' => substr($token, 0, 8) . '...',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl()
        ]);
    }

    /**
     * Log failed magic link attempt.
     */
    protected function logFailedAttempt(Request $request, string $token): void
    {
        $this->auditService->log([
            'event' => 'magic_link.failed_attempt',
            'new_values' => [
                'token' => substr($token, 0, 8) . '...',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ],
        ]);
    }
}