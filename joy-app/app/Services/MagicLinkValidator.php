<?php

namespace App\Services;

use App\Models\MagicLink;
use Illuminate\Http\Request;

class MagicLinkValidator
{
    /**
     * Validate magic link from request attributes
     */
    public function validateFromRequest(Request $request): ?MagicLink
    {
        return $request->attributes->get('magic_link');
    }

    /**
     * Validate magic link token and return the magic link if valid
     */
    public function validateToken(string $token): ?MagicLink
    {
        $magicLink = MagicLink::where('token', $token)
            ->where('expires_at', '>', now())
            ->where('is_active', true)
            ->first();

        return $magicLink;
    }

    /**
     * Check if magic link is valid and not expired
     */
    public function isValid(MagicLink $magicLink): bool
    {
        return $magicLink->is_active && 
               $magicLink->expires_at > now();
    }

    /**
     * Check if magic link has access to specific workspace
     */
    public function hasWorkspaceAccess(MagicLink $magicLink, int $workspaceId): bool
    {
        return $magicLink->workspace_id === $workspaceId;
    }

    /**
     * Get error response for invalid magic link
     */
    public function getInvalidLinkResponse(string $message = 'Invalid access'): \Illuminate\Http\Response
    {
        return response()->view('errors.401', ['message' => $message], 401);
    }

    /**
     * Validate magic link access for controller actions
     * This centralizes the common pattern used across ClientController
     */
    public function validateOrFail(Request $request, string $errorMessage = 'Invalid access'): MagicLink
    {
        $magicLink = $this->validateFromRequest($request);
        
        if (!$magicLink) {
            abort(401, $errorMessage);
        }

        return $magicLink;
    }

    /**
     * Check if magic link has permission to access specific content item
     */
    public function canAccessContentItem(MagicLink $magicLink, $contentItem): bool
    {
        // Check if content item belongs to the workspace
        if (method_exists($contentItem, 'workspace')) {
            return $contentItem->workspace->id === $magicLink->workspace_id;
        }
        
        // For variants, check through the concept relationship
        if (method_exists($contentItem, 'concept') && $contentItem->concept) {
            return $contentItem->concept->workspace_id === $magicLink->workspace_id;
        }

        return false;
    }

    /**
     * Log magic link access for audit purposes
     */
    public function logAccess(MagicLink $magicLink, string $action, ?array $metadata = null): void
    {
        // This could be extended to create audit log entries
        logger('Magic link access', [
            'magic_link_id' => $magicLink->id,
            'workspace_id' => $magicLink->workspace_id,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}