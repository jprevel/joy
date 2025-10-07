<?php

namespace App\Http\Middleware;

use App\Http\Middleware\ValidateMagicLink;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MagicLinkAuth extends ValidateMagicLink
{
    /**
     * Handle an incoming request with magic link authentication.
     * This is an alias for ValidateMagicLink for cleaner route definitions.
     */
    public function handle(Request $request, Closure $next, ?string $requiredScope = null): Response
    {
        return parent::handle($request, $next, $requiredScope);
    }
}