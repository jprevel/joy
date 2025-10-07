<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCors
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Handle CORS for API requests with enhanced security logging.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        // Log cross-origin requests for security monitoring
        if ($origin && $this->isExternalOrigin($origin)) {
            $this->logCorsRequest($request, $origin);
        }

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return $this->handleApiPreflightRequest($request);
        }

        $response = $next($request);

        return $this->addApiCorsHeaders($response, $request);
    }

    /**
     * Handle preflight requests for API endpoints.
     */
    protected function handleApiPreflightRequest(Request $request): Response
    {
        $response = response('', 200);
        return $this->addApiCorsHeaders($response, $request);
    }

    /**
     * Add CORS headers specifically for API responses.
     */
    protected function addApiCorsHeaders(Response $response, Request $request): Response
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = $this->getApiAllowedOrigins();

        if ($this->isOriginAllowed($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);

            // More restrictive headers for API
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', implode(', ', [
                'Accept',
                'Authorization',
                'Content-Type',
                'X-Requested-With',
                'X-CSRF-TOKEN',
                'X-Magic-Link-Token',
            ]));
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '3600'); // 1 hour for API

            // API-specific exposed headers
            $response->headers->set('Access-Control-Expose-Headers', implode(', ', [
                'X-RateLimit-Limit',
                'X-RateLimit-Remaining',
                'X-RateLimit-Reset',
                'Retry-After',
                'X-Total-Count',
                'Link',
            ]));
        } else {
            // Log rejected CORS request
            $this->logRejectedCorsRequest($request, $origin);
        }

        return $response;
    }

    /**
     * Get allowed origins for API endpoints.
     */
    protected function getApiAllowedOrigins(): array
    {
        $allowedOrigins = config('cors.api_allowed_origins', []);

        // Add development origins in non-production
        if (!app()->environment('production')) {
            $allowedOrigins = array_merge($allowedOrigins, [
                'http://localhost:3000',
                'http://localhost:8080',
                'http://localhost:5173',
                'http://127.0.0.1:3000',
                'http://127.0.0.1:8080',
                'http://127.0.0.1:5173',
            ]);
        }

        return array_unique($allowedOrigins);
    }

    /**
     * Check if origin is allowed.
     */
    protected function isOriginAllowed(?string $origin, array $allowedOrigins): bool
    {
        if (!$origin) {
            return false;
        }

        // Check for exact matches
        if (in_array($origin, $allowedOrigins)) {
            return true;
        }

        // Check for wildcard matches
        foreach ($allowedOrigins as $allowedOrigin) {
            if ($allowedOrigin === '*') {
                return true;
            }

            if (str_contains($allowedOrigin, '*')) {
                $pattern = str_replace('*', '.*', preg_quote($allowedOrigin, '/'));
                if (preg_match("/^{$pattern}$/", $origin)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if origin is external (not same origin).
     */
    protected function isExternalOrigin(string $origin): bool
    {
        $appUrl = config('app.url');
        return $origin !== $appUrl && !str_starts_with($origin, $appUrl);
    }

    /**
     * Log CORS request for security monitoring.
     */
    protected function logCorsRequest(Request $request, string $origin): void
    {
        $this->auditService->log([
            'event' => 'cors.request',
            'new_values' => [
                'origin' => $origin,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route' => $request->route()?->getName(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'referer' => $request->headers->get('Referer'),
            ],
        ]);
    }

    /**
     * Log rejected CORS request.
     */
    protected function logRejectedCorsRequest(Request $request, ?string $origin): void
    {
        if (!$origin) {
            return;
        }

        $this->auditService->log([
            'event' => 'cors.rejected',
            'new_values' => [
                'origin' => $origin,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route' => $request->route()?->getName(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'reason' => 'origin_not_allowed',
            ],
        ]);

        \Log::warning('CORS request rejected', [
            'origin' => $origin,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent()
        ]);
    }
}