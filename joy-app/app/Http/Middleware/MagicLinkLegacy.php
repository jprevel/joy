<?php

namespace App\Http\Middleware;

use App\Services\MagicLinkService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MagicLinkLegacy
{
    public function __construct(
        private MagicLinkService $magicLinkService
    ) {}

    /**
     * Handle legacy magic link routes and redirect to new portal routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        if (!$token) {
            return redirect()->route('login');
        }

        // Check if the magic link exists and is valid
        $magicLink = $this->magicLinkService->findValidByToken($token);

        if (!$magicLink) {
            return redirect()->route('login')->with('error', 'Invalid or expired access link');
        }

        // For legacy routes, we'll let them continue but log a deprecation warning
        \Log::info('Legacy magic link route accessed', [
            'token' => substr($token, 0, 8) . '...',
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'client_id' => $magicLink->client_id
        ]);

        return $next($request);
    }
}