<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ClientAccessResolver;
use Symfony\Component\HttpFoundation\Response;

class ResolveClientAccess
{
    public function __construct(
        private ClientAccessResolver $clientResolver
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->get('authenticated_user');
        $clientId = $request->input('client_id') ?? $request->route('client');

        try {
            $client = $this->clientResolver->resolveClient($clientId, $user);
            $request->merge(['resolved_client' => $client]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
