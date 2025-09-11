<?php

namespace App\Http\Middleware;

use App\Services\MagicLinkService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateMagicLink
{
    public function __construct(
        private MagicLinkService $magicLinkService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token') ?? $request->query('token');

        if (!$token) {
            return response()->view('errors.401', ['message' => 'Access token required'], 401);
        }

        $magicLink = $this->magicLinkService->validateToken($token);

        if (!$magicLink) {
            return response()->view('errors.401', ['message' => 'Invalid or expired access link'], 401);
        }

        // Mark the access
        $magicLink->markAccessed();

        // Add magic link to request for use in controllers
        $request->merge(['magic_link' => $magicLink]);
        $request->attributes->add(['magic_link' => $magicLink]);

        return $next($request);
    }
}
