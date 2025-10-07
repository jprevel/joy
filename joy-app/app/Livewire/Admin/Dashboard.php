<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\RoleDetectionService;
use App\Services\ContentItemService;
use Livewire\Component;
use Illuminate\Support\Collection;

class Dashboard extends Component
{
    public ?User $currentUser = null;
    public array $systemStats = [];
    public Collection $recentActivity;

    public function __construct(
        private RoleDetectionService $roleDetectionService,
        private ContentItemService $contentItemService
    ) {
        parent::__construct();
    }

    public function mount()
    {
        $this->currentUser = $this->roleDetectionService->getCurrentUser();

        if (!$this->roleDetectionService->isAdmin($this->currentUser)) {
            abort(403, 'Admin access required');
        }

        $this->loadSystemStats();
        $this->loadRecentActivity();
    }

    private function loadSystemStats()
    {
        $this->systemStats = [
            'total_users' => \App\Models\User::count(),
            'total_clients' => \App\Models\Client::count(),
            'total_content' => \App\Models\ContentItem::count(),
            'active_magic_links' => \App\Models\MagicLink::where('expires_at', '>', now())->count(),
            'content_this_month' => \App\Models\ContentItem::whereMonth('created_at', now()->month)->count(),
            'comments_this_month' => \App\Models\Comment::whereMonth('created_at', now()->month)->count(),
        ];
    }

    private function loadRecentActivity()
    {
        // Load recent audit logs for activity feed
        $this->recentActivity = \App\Models\AuditLog::with('user')
            ->latest()
            ->take(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.dashboard')
            ->layout('components.layouts.admin');
    }
}