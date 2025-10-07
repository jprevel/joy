<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\User;
use App\Services\MagicLinkService;
use App\Services\RoleDetectionService;
use Livewire\Component;
use Livewire\Attributes\Validate;

class MagicLinkManager extends Component
{
    public ?Client $client = null;
    public ?User $currentUser = null;
    public bool $showCreateForm = false;

    #[Validate('required|array')]
    public array $scopes = [];

    #[Validate('nullable|date|after:now')]
    public ?string $expires_at = null;

    #[Validate('nullable|string|max:255')]
    public ?string $description = null;

    #[Validate('nullable|boolean')]
    public bool $pin_protected = false;

    public function __construct(
        private MagicLinkService $magicLinkService,
        private RoleDetectionService $roleDetectionService
    ) {
        parent::__construct();
    }

    public function mount(Client $client)
    {
        $this->client = $client;
        $this->currentUser = $this->roleDetectionService->getCurrentUser();

        if (!$this->hasPermission('manage magic links')) {
            abort(403, 'Access denied');
        }

        // Load magic links
        $this->client->load(['magicLinks' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);
    }

    public function showCreateForm()
    {
        $this->showCreateForm = true;
        $this->resetForm();
    }

    public function hideCreateForm()
    {
        $this->showCreateForm = false;
        $this->resetForm();
    }

    public function createMagicLink()
    {
        $this->validate();

        try {
            $this->magicLinkService->create([
                'client_id' => $this->client->id,
                'scopes' => $this->scopes,
                'expires_at' => $this->expires_at ? \Carbon\Carbon::parse($this->expires_at) : null,
                'description' => $this->description,
                'pin_protected' => $this->pin_protected,
            ], $this->currentUser);

            // Reload magic links
            $this->client->load(['magicLinks' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }]);

            $this->hideCreateForm();

            session()->flash('success', 'Magic link created successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create magic link: ' . $e->getMessage());
        }
    }

    public function revokeMagicLink($linkId)
    {
        try {
            $this->magicLinkService->revoke($linkId, $this->currentUser);

            // Reload magic links
            $this->client->load(['magicLinks' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }]);

            session()->flash('success', 'Magic link revoked successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to revoke magic link: ' . $e->getMessage());
        }
    }

    public function regenerateMagicLink($linkId)
    {
        try {
            $this->magicLinkService->regenerate($linkId, $this->currentUser);

            // Reload magic links
            $this->client->load(['magicLinks' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }]);

            session()->flash('success', 'Magic link regenerated successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to regenerate magic link: ' . $e->getMessage());
        }
    }

    private function resetForm()
    {
        $this->scopes = [];
        $this->expires_at = null;
        $this->description = null;
        $this->pin_protected = false;
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->currentUser) {
            return false;
        }

        return $this->roleDetectionService->hasPermission($this->currentUser, $permission);
    }

    public function getAvailableScopes(): array
    {
        return [
            'content.view' => 'View Content',
            'content.comment' => 'Add Comments',
            'content.approve' => 'Approve Content',
            'calendar.view' => 'View Calendar',
            'reports.view' => 'View Reports',
        ];
    }

    public function render()
    {
        return view('livewire.magic-link-manager')
            ->layout('components.layouts.app');
    }
}