<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\AuditLog;
use App\Services\RoleDetectionService;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class AuditLogs extends Component
{
    use WithPagination;

    public ?User $currentUser = null;
    public string $search = '';
    public string $eventFilter = '';
    public string $userFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortDirection = 'desc';

    public function __construct(
        private RoleDetectionService $roleDetectionService
    ) {
        parent::__construct();
    }

    public function mount()
    {
        $this->currentUser = $this->roleDetectionService->getCurrentUser();

        if (!$this->roleDetectionService->isAdmin($this->currentUser)) {
            abort(403, 'Admin access required');
        }

        // Default to last 30 days
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedEventFilter()
    {
        $this->resetPage();
    }

    public function updatedUserFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->eventFilter = '';
        $this->userFilter = '';
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::with('user');

        // Apply date range filter
        if (!empty($this->dateFrom)) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }
        if (!empty($this->dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('event', 'like', '%' . $this->search . '%')
                  ->orWhere('auditable_type', 'like', '%' . $this->search . '%')
                  ->orWhereJsonContains('new_values', $this->search)
                  ->orWhereJsonContains('old_values', $this->search);
            });
        }

        // Apply event filter
        if (!empty($this->eventFilter)) {
            $query->where('event', $this->eventFilter);
        }

        // Apply user filter
        if (!empty($this->userFilter)) {
            $query->where('user_id', $this->userFilter);
        }

        // Apply sorting
        $query->orderBy('created_at', $this->sortDirection);

        $auditLogs = $query->paginate(20);

        // Get unique events for filter dropdown
        $events = AuditLog::distinct()->pluck('event')->sort();

        // Get users for filter dropdown
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.audit-logs', [
            'auditLogs' => $auditLogs,
            'events' => $events,
            'users' => $users,
        ])->layout('components.layouts.admin');
    }
}