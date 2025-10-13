<?php

namespace App\Livewire\Admin;

use App\Contracts\ClientManagementContract;
use App\Models\Client;
use App\Models\User;
use App\Services\SlackService;
use Livewire\Component;
use Livewire\WithPagination;

class ClientManagement extends Component
{
    use WithPagination;

    public ?User $currentUser = null;
    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';

    // CRUD Form properties
    public array $form = [
        'name' => '',
        'description' => '',
        'team_id' => '',
        'slack_channel_id' => '',
        'slack_channel_name' => '',
    ];

    public ?int $editingClientId = null;
    public bool $showCreateForm = false;
    public bool $showEditForm = false;
    public bool $noSlackWorkspace = false;
    public array $availableSlackChannels = [];

    protected ClientManagementContract $clientService;
    protected SlackService $slackService;

    public function boot(
        ClientManagementContract $clientService,
        SlackService $slackService
    ) {
        $this->clientService = $clientService;
        $this->slackService = $slackService;
    }

    public function mount()
    {
        $this->currentUser = auth()->user();

        if (!$this->currentUser) {
            abort(403, 'Authentication required');
        }

        if (!$this->currentUser->hasRole('admin')) {
            abort(403, 'Admin access required');
        }

        // Load available Slack channels
        $this->loadSlackChannels();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function sortBy(string $field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function loadSlackChannels()
    {
        $channels = $this->clientService->getAvailableSlackChannels();

        if (empty($channels)) {
            $this->noSlackWorkspace = true;
            $this->availableSlackChannels = [];
        } else {
            $this->noSlackWorkspace = false;
            $this->availableSlackChannels = $channels;
        }
    }

    public function createClient()
    {
        try {
            $this->clientService->createClient($this->form);

            $this->form = [
                'name' => '',
                'description' => '',
                'team_id' => '',
                'slack_channel_id' => '',
                'slack_channel_name' => '',
            ];
            $this->showCreateForm = false;

            session()->flash('message', 'Client created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('form', $e->getMessage());
        }
    }

    public function editClient(int $clientId)
    {
        $client = $this->clientService->findClient($clientId);

        $this->editingClientId = $clientId;

        $this->form = [
            'name' => $client->name,
            'description' => $client->description ?? '',
            'team_id' => $client->team_id ?? '',
            'slack_channel_id' => $client->slack_channel_id ?? '',
            'slack_channel_name' => $client->slack_channel_name ?? '',
        ];

        $this->showEditForm = true;
    }

    public function updateClient()
    {
        if (!$this->editingClientId) {
            return;
        }

        try {
            $this->clientService->updateClient($this->editingClientId, $this->form);

            $this->form = [
                'name' => '',
                'description' => '',
                'team_id' => '',
                'slack_channel_id' => '',
                'slack_channel_name' => '',
            ];
            $this->editingClientId = null;
            $this->showEditForm = false;

            session()->flash('message', 'Client updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('form', $e->getMessage());
        }
    }

    public function cancel()
    {
        $this->form = [
            'name' => '',
            'description' => '',
            'team_id' => '',
            'slack_channel_id' => '',
            'slack_channel_name' => '',
        ];
        $this->editingClientId = null;
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->resetErrorBag();
    }

    public function deleteClient(int $clientId)
    {
        try {
            $this->clientService->deleteClient($clientId);
            session()->flash('message', 'Client deleted successfully');
        } catch (\Exception $e) {
            $this->addError('delete', $e->getMessage());
        }
    }

    public function restoreClient(int $clientId)
    {
        try {
            $this->clientService->restoreClient($clientId);
            session()->flash('message', 'Client restored successfully');
        } catch (\Exception $e) {
            $this->addError('restore', $e->getMessage());
        }
    }

    public function render()
    {
        // Include soft-deleted clients
        $query = Client::withTrashed()->with('team');

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $clients = $query->paginate(15);
        $availableTeams = $this->clientService->getAvailableTeams();

        return view('livewire.admin.client-management', [
            'clients' => $clients,
            'availableTeams' => $availableTeams,
        ])->layout('components.layouts.admin');
    }
}