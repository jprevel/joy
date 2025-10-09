<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ClientStatusUpdate;
use App\Traits\HasRoleManagement;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;

class Statusfaction extends Component
{
    use HasRoleManagement;

    public $selectedClient = null;
    public $showForm = false;
    public $showDetail = false;
    public $currentRole = 'admin';
    public $selectedStatus = null;

    #[Rule('required|string|min:1')]
    public $status_notes = '';

    #[Rule('required|integer|min:1|max:10')]
    public $client_satisfaction = 5;

    #[Rule('required|integer|min:1|max:10')]
    public $team_health = 5;

    public function mount($role = null)
    {
        // Default to appropriate role based on user's permissions
        if (!$role && auth()->check()) {
            if (auth()->user()->hasRole('admin')) {
                $role = 'admin';
            } elseif (auth()->user()->hasRole('agency')) {
                $role = 'agency';
            } else {
                $role = 'admin'; // fallback
            }
        }

        $this->currentRole = $role ?? 'admin';
    }

    public function selectClient($clientId)
    {
        $this->selectedClient = Client::findOrFail($clientId);
        $currentWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        $this->selectedStatus = ClientStatusUpdate::where('client_id', $clientId)
            ->where('week_start_date', $currentWeek)
            ->first();

        // Determine view based on status and permissions
        if ($this->selectedStatus) {
            $isOwnStatus = $this->selectedStatus->user_id === auth()->id();
            $isPending = $this->selectedStatus->approval_status === 'pending_approval';
            $isApproved = $this->selectedStatus->approval_status === 'approved';

            if ($isPending && $isOwnStatus) {
                // User's own pending status → show form to edit
                $this->showForm = true;
                $this->showDetail = false;
                $this->loadFormData();
            } elseif ($isPending && auth()->user()->hasRole('admin')) {
                // Admin viewing someone else's pending status → show detail with approve button
                $this->showForm = false;
                $this->showDetail = true;
            } elseif ($isApproved) {
                // Approved statuses are always read-only
                $this->showForm = false;
                $this->showDetail = true;
            } else {
                // Fallback: show detail
                $this->showForm = false;
                $this->showDetail = true;
            }
        } else {
            // Needs status - show form
            $this->showForm = true;
            $this->showDetail = false;
            $this->resetForm();
        }
    }

    public function backToList()
    {
        $this->showForm = false;
        $this->showDetail = false;
        $this->selectedClient = null;
        $this->selectedStatus = null;
        $this->resetForm();
    }

    public function editStatus()
    {
        if ($this->selectedStatus && $this->canEdit($this->selectedStatus)) {
            $this->showForm = true;
            $this->showDetail = false;
            $this->loadFormData();
        }
    }

    public function saveStatus()
    {
        $this->validate();

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // Check if can edit
        if ($this->selectedStatus && !$this->canEdit($this->selectedStatus)) {
            session()->flash('error', 'Cannot edit approved status');
            return;
        }

        ClientStatusUpdate::updateOrCreate(
            [
                'client_id' => $this->selectedClient->id,
                'week_start_date' => $weekStart,
            ],
            [
                'user_id' => auth()->id(),
                'status_notes' => $this->status_notes,
                'client_satisfaction' => $this->client_satisfaction,
                'team_health' => $this->team_health,
                'status_date' => now(),
                'approval_status' => 'pending_approval',
            ]
        );

        session()->flash('success', 'Status update saved successfully!');
        $this->backToList();
    }

    public function approveStatus($statusId)
    {
        if (!$this->canApprove()) {
            session()->flash('error', 'You do not have permission to approve statuses');
            return;
        }

        $status = ClientStatusUpdate::findOrFail($statusId);

        if ($status->approval_status !== 'pending_approval') {
            session()->flash('error', 'Status is not pending approval');
            return;
        }

        $status->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', 'Status approved successfully!');
        $this->dispatch('statusApproved');
        $this->backToList();
    }

    #[Computed]
    public function clients()
    {
        $user = auth()->user();
        $currentWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        $query = Client::query();

        // Filter by role
        if ($user && !$user->hasRole('admin')) {
            $query->whereIn('team_id', $user->teams->pluck('id'));
        }

        return $query->with(['statusUpdates' => function ($q) use ($currentWeek) {
                $q->where('week_start_date', $currentWeek);
            }])
            ->get()
            ->map(function ($client) {
                $status = $client->statusUpdates->first();

                $client->status_state = match(true) {
                    $status === null => 'Needs Status',
                    $status->approval_status === 'pending_approval' => 'Pending Approval',
                    $status->approval_status === 'approved' => 'Status Approved',
                    default => 'Needs Status',
                };

                $client->status_badge_color = match($client->status_state) {
                    'Needs Status' => 'red',
                    'Pending Approval' => 'yellow',
                    'Status Approved' => 'green',
                    default => 'gray',
                };

                return $client;
            });
    }

    #[Computed]
    public function graphData()
    {
        if (!$this->selectedClient) {
            return [];
        }

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $fiveWeeksAgo = $weekStart->copy()->subWeeks(4);

        $statusData = ClientStatusUpdate::where('client_id', $this->selectedClient->id)
            ->whereBetween('week_start_date', [$fiveWeeksAgo, $weekStart])
            ->orderBy('week_start_date')
            ->get(['week_start_date', 'client_satisfaction', 'team_health']);

        // Generate 5-week labels
        $weeks = collect(range(0, 4))->map(function ($offset) use ($weekStart) {
            $date = $weekStart->copy()->subWeeks(4 - $offset);
            return [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('M j'),
            ];
        });

        // Map data to weeks (with nulls for gaps)
        $graphData = $weeks->map(function ($week) use ($statusData) {
            $status = $statusData->first(function ($s) use ($week) {
                return $s->week_start_date->format('Y-m-d') === $week['date'];
            });
            return [
                'week' => $week['label'],
                'client_satisfaction' => $status?->client_satisfaction,
                'team_health' => $status?->team_health,
            ];
        });

        return [
            'labels' => $graphData->pluck('week')->toArray(),
            'datasets' => [
                [
                    'label' => 'Client Satisfaction',
                    'data' => $graphData->pluck('client_satisfaction')->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'spanGaps' => false,
                ],
                [
                    'label' => 'Team Health',
                    'data' => $graphData->pluck('team_health')->toArray(),
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'spanGaps' => false,
                ],
            ],
        ];
    }

    private function canEdit(ClientStatusUpdate $status): bool
    {
        // Cannot edit approved statuses
        if ($status->approval_status === 'approved') {
            return false;
        }

        // Admins can edit any pending status
        if (auth()->user()->hasRole('admin')) {
            return true;
        }

        // Agency users can only edit their own pending statuses
        return $status->user_id === auth()->id()
            && $status->approval_status === 'pending_approval';
    }

    private function canApprove(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    private function resetForm(): void
    {
        $this->status_notes = '';
        $this->client_satisfaction = 5;
        $this->team_health = 5;
    }

    private function loadFormData(): void
    {
        if ($this->selectedStatus) {
            $this->status_notes = $this->selectedStatus->status_notes;
            $this->client_satisfaction = $this->selectedStatus->client_satisfaction;
            $this->team_health = $this->selectedStatus->team_health;
        }
    }

    public function render()
    {
        return view('livewire.statusfaction');
    }
}
