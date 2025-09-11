<?php

namespace App\Services;

use App\Models\ClientWorkspace;
use App\Models\MagicLink;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class MagicLinkService
{
    public function generateMagicLink(
        ClientWorkspace $workspace,
        string $email,
        string $name,
        array $permissions = [],
        int $expiresInHours = 168
    ): MagicLink {
        // Deactivate any existing active links for this email/workspace
        MagicLink::where('workspace_id', $workspace->id)
            ->where('email', $email)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Create new magic link
        return MagicLink::createForWorkspace(
            $workspace,
            $email,
            $name,
            $permissions,
            $expiresInHours
        );
    }

    public function validateToken(string $token): ?MagicLink
    {
        $magicLink = MagicLink::where('token', $token)->first();

        if (!$magicLink || !$magicLink->isValid()) {
            return null;
        }

        return $magicLink;
    }

    public function getAccessUrl(MagicLink $magicLink): string
    {
        return URL::route('client.access', ['token' => $magicLink->token]);
    }

    public function sendMagicLink(MagicLink $magicLink): void
    {
        $accessUrl = $this->getAccessUrl($magicLink);
        
        // In a real implementation, you would send an email here
        // For now, we'll just log the URL
        \Log::info("Magic link for {$magicLink->email}: {$accessUrl}");
        
        // TODO: Implement email sending
        // Mail::to($magicLink->email)->send(new MagicLinkMail($magicLink, $accessUrl));
    }

    public function revokeLink(MagicLink $magicLink): void
    {
        $magicLink->deactivate();
    }

    public function cleanupExpiredLinks(): int
    {
        return MagicLink::where('expires_at', '<', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public function getActiveLinksForWorkspace(ClientWorkspace $workspace)
    {
        return MagicLink::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function hasPermission(MagicLink $magicLink, string $permission): bool
    {
        if (empty($magicLink->permissions)) {
            return true; // Default to full access if no specific permissions set
        }

        return in_array($permission, $magicLink->permissions);
    }

    public function getDefaultPermissions(): array
    {
        return [
            'view_concepts',
            'comment_on_variants',
            'approve_variants',
            'request_changes'
        ];
    }
}