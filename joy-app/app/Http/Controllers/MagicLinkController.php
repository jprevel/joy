<?php

namespace App\Http\Controllers;
use App\Http\Traits\ApiResponse;

use App\Http\Requests\MagicLinkStoreRequest;
use App\Models\MagicLink;
use App\Models\Client;
use App\Services\MagicLinkService;
use App\Services\RoleDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MagicLinkController extends Controller
{
    use ApiResponse;

    public function __construct(
        private MagicLinkService $magicLinkService,
        private RoleDetectionService $roleDetectionService
    ) {}

    /**
     * Display a listing of magic links for a client.
     */
    public function index(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        $request->validate([
            'client_id' => 'required|exists:clients,id'
        ]);

        $client = Client::findOrFail($request->input('client_id'));

        // Get magic links for the client
        $magicLinks = $this->magicLinkService->getForClient($client);

        return $this->success([
            'data' => $magicLinks->map(fn($link) => $this->magicLinkService->formatForApi($link)),
            'meta' => [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'active_links' => $this->magicLinkService->getActiveForClient($client)->count()
            ]
        ]);
    }

    /**
     * Store a newly created magic link.
     */
    public function store(MagicLinkStoreRequest $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $validatedData = $request->validated();
            $magicLink = $this->magicLinkService->create($validatedData);

            return $this->created([
                'data' => $this->magicLinkService->formatForApi($magicLink),
                'access_url' => $this->magicLinkService->getAccessUrl($magicLink)
            ], 'Magic link created successfully');

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create magic link', $e);
        }
    }

    /**
     * Display the specified magic link.
     */
    public function show(Request $request, string $token): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        $magicLink = $this->magicLinkService->findByToken($token);

        if (!$magicLink) {
            return $this->notFound('Magic link not found');
        }

        return $this->success([
            'data' => $this->magicLinkService->formatForApi($magicLink),
            'access_url' => $this->magicLinkService->getAccessUrl($magicLink)
        ]);
    }

    /**
     * Validate magic link access with optional PIN.
     */
    public function validateAccess(Request $request, string $token): JsonResponse
    {
        try {
            $request->validate([
                'pin' => 'sometimes|nullable|string|size:4|regex:/^\d{4}$/'
            ]);

            $pin = $request->input('pin');
            $magicLink = $this->magicLinkService->validateAccess($token, $pin);

            return $this->success([
                'token' => $magicLink->token,
                'client_id' => $magicLink->client_id,
                'client_name' => $magicLink->client->name,
                'scopes' => $magicLink->scopes,
                'expires_at' => $magicLink->expires_at->toISOString(),
            ], 'Access granted');

        } catch (ValidationException $e) {
            return $this->unauthorized('Access denied');
        } catch (\Exception $e) {
            return $this->unauthorized('Invalid or expired magic link');
        }
    }

    /**
     * Check magic link scope permissions.
     */
    public function checkScope(Request $request, string $token): JsonResponse
    {
        try {
            $request->validate([
                'scope' => 'required|string|in:view,comment,approve'
            ]);

            $magicLink = $this->magicLinkService->findValidByToken($token);

            if (!$magicLink) {
                return $this->unauthorized('Invalid or expired magic link');
            }

            $scope = $request->input('scope');
            $hasScope = $this->magicLinkService->hasScope($magicLink, $scope);

            return $this->success([
                'has_scope' => $hasScope,
                'scope' => $scope,
                'available_scopes' => $magicLink->scopes
            ]);

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }

    /**
     * Send magic link via email.
     */
    public function sendEmail(Request $request, string $token): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $magicLink = $this->magicLinkService->findByToken($token);

            if (!$magicLink) {
                return $this->notFound('Magic link not found');
            }

            $email = $request->input('email');
            $this->magicLinkService->sendMagicLink($magicLink, $email);

            return $this->success(
                ['email' => $email],
                'Magic link sent successfully'
            );

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to send magic link', $e);
        }
    }

    /**
     * Get available scopes for magic links.
     */
    public function availableScopes(): JsonResponse
    {
        $scopes = $this->magicLinkService->getAvailableScopes();

        return $this->success([
            'data' => $scopes,
            'default_scopes' => $this->magicLinkService->getDefaultScopes()
        ]);
    }

    /**
     * Clean up expired magic links.
     */
    public function cleanup(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $deletedCount = $this->magicLinkService->cleanupExpired();

            return $this->success(
                ['deleted_count' => $deletedCount],
                'Cleanup completed successfully'
            );

        } catch (\Exception $e) {
            return $this->serverError('Cleanup failed', $e);
        }
    }

    /**
     * Get magic link statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        $clientId = $request->input('client_id');
        $stats = [];

        if ($clientId) {
            $client = Client::findOrFail($clientId);
            $allLinks = $this->magicLinkService->getForClient($client);
            $activeLinks = $this->magicLinkService->getActiveForClient($client);

            $stats = [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'total_links' => $allLinks->count(),
                'active_links' => $activeLinks->count(),
                'expired_links' => $allLinks->count() - $activeLinks->count(),
                'by_scope' => $allLinks->flatMap(fn($link) => $link->scopes)->countBy(),
                'accessed_links' => $allLinks->whereNotNull('accessed_at')->count(),
            ];
        } else {
            // System-wide stats for admins
            if (!$this->roleDetectionService->isAdmin($user)) {
                return $this->forbidden();
            }

            $totalLinks = MagicLink::count();
            $activeLinks = MagicLink::where('expires_at', '>', now())->count();
            $accessedLinks = MagicLink::whereNotNull('accessed_at')->count();

            $stats = [
                'total_links' => $totalLinks,
                'active_links' => $activeLinks,
                'expired_links' => $totalLinks - $activeLinks,
                'accessed_links' => $accessedLinks,
                'by_client' => MagicLink::selectRaw('client_id, count(*) as count')
                    ->with('client:id,name')
                    ->groupBy('client_id')
                    ->get()
                    ->mapWithKeys(fn($item) => [$item->client->name => $item->count]),
            ];
        }

        return $this->success($stats);
    }

    /**
     * Revoke (delete) a magic link.
     */
    public function revoke(Request $request, string $token): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        $magicLink = $this->magicLinkService->findByToken($token);

        if (!$magicLink) {
            return $this->notFound('Magic link not found');
        }

        try {
            $deleted = $magicLink->delete();

            if ($deleted) {
                return $this->deleted('Magic link revoked successfully');
            } else {
                return $this->serverError('Failed to revoke magic link');
            }

        } catch (\Exception $e) {
            return $this->serverError('Failed to revoke magic link', $e);
        }
    }
}