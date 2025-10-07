<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\User;
use App\Services\ContentItemService;
use App\Services\RoleDetectionService;
use Livewire\Component;
use Illuminate\Support\Collection;

class ClientDetail extends Component
{
    public ?Client $client = null;
    public ?User $currentUser = null;
    public Collection $contentItems;
    public Collection $recentComments;
    public array $contentStats = [];

    public function __construct(
        private ContentItemService $contentItemService,
        private RoleDetectionService $roleDetectionService
    ) {
        parent::__construct();
    }

    public function mount(Client $client)
    {
        $this->client = $client;
        $this->currentUser = $this->roleDetectionService->getCurrentUser();

        if (!$this->hasAccess()) {
            abort(403, 'Access denied');
        }

        $this->loadData();
    }

    public function hasAccess(): bool
    {
        if (!$this->currentUser || !$this->client) {
            return false;
        }

        // Admin can access everything
        if ($this->roleDetectionService->isAdmin($this->currentUser)) {
            return true;
        }

        // Agency can access clients they manage
        if ($this->roleDetectionService->isAgency($this->currentUser)) {
            $accessibleClients = $this->roleDetectionService->getAccessibleClients($this->currentUser);
            return $accessibleClients->contains('id', $this->client->id);
        }

        // Clients can only access their own details
        return $this->currentUser->client_id === $this->client->id;
    }

    private function loadData()
    {
        // Load recent content items
        $this->contentItems = $this->contentItemService->getForClient($this->client, [
            'limit' => 10,
            'order_by' => 'created_at',
            'order_direction' => 'desc'
        ]);

        // Load recent comments
        $this->recentComments = collect();
        if ($this->client->contentItems()->exists()) {
            $this->recentComments = $this->client->contentItems()
                ->with(['comments.user'])
                ->get()
                ->pluck('comments')
                ->flatten()
                ->sortByDesc('created_at')
                ->take(5);
        }

        // Calculate content statistics
        $this->calculateContentStats();
    }

    private function calculateContentStats()
    {
        $contentItems = $this->contentItemService->getForClient($this->client);

        $this->contentStats = [
            'total' => $contentItems->count(),
            'draft' => $contentItems->where('status', 'draft')->count(),
            'review' => $contentItems->where('status', 'review')->count(),
            'approved' => $contentItems->where('status', 'approved')->count(),
            'scheduled' => $contentItems->where('status', 'scheduled')->count(),
            'published' => $contentItems->where('status', 'published')->count(),
            'this_month' => $contentItems->filter(function ($item) {
                return isset($item['created_at']) &&
                       \Carbon\Carbon::parse($item['created_at'])->isCurrentMonth();
            })->count(),
        ];
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->currentUser) {
            return false;
        }

        return $this->roleDetectionService->hasPermission($this->currentUser, $permission);
    }

    public function render()
    {
        return view('livewire.client-detail')
            ->layout('components.layouts.app');
    }
}