<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsHandler
{
    /**
     * Handle an incoming request with CORS support.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return $this->handlePreflightRequest($request);
        }

        $response = $next($request);

        return $this->addCorsHeaders($response, $request);
    }

    /**
     * Handle preflight OPTIONS requests.
     */
    protected function handlePreflightRequest(Request $request): Response
    {
        $response = response('', 200);

        return $this->addCorsHeaders($response, $request);
    }

    /**
     * Add CORS headers to the response.
     */
    protected function addCorsHeaders(Response $response, Request $request): Response
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = $this->getAllowedOrigins();

        // Check if origin is allowed
        if ($this->isOriginAllowed($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        $response->headers->set('Access-Control-Allow-Methods', $this->getAllowedMethods());
        $response->headers->set('Access-Control-Allow-Headers', $this->getAllowedHeaders());
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours

        // Expose headers that the client can read
        $response->headers->set('Access-Control-Expose-Headers', $this->getExposedHeaders());

        return $response;
    }

    /**
     * Get allowed origins from configuration.
     */
    protected function getAllowedOrigins(): array
    {
        $allowedOrigins = config('cors.allowed_origins', []);

        // Add localhost and development origins in non-production environments
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

            // Support subdomain wildcards like *.example.com
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
     * Get allowed HTTP methods.
     */
    protected function getAllowedMethods(): string
    {
        return config('cors.allowed_methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    /**
     * Get allowed headers.
     */
    protected function getAllowedHeaders(): string
    {
        return config('cors.allowed_headers', implode(', ', [
            'Accept',
            'Authorization',
            'Content-Type',
            'X-Requested-With',
            'X-CSRF-TOKEN',
            'X-Magic-Link-Token',
            'X-Client-ID',
            'X-User-Role',
        ]));
    }

    /**
     * Get headers that can be exposed to the client.
     */
    protected function getExposedHeaders(): string
    {
        return config('cors.exposed_headers', implode(', ', [
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset',
            'Retry-After',
            'X-Total-Count',
            'X-Pagination-Current-Page',
            'X-Pagination-Per-Page',
            'X-Pagination-Total',
        ]));
    }
}