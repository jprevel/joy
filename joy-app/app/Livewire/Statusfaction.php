<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ClientStatusUpdate;
use Livewire\Component;
use Livewire\Attributes\Rule;

class Statusfaction extends Component
{
    public $selectedClient = null;
    public $showForm = false;
    
    #[Rule('required|string')]
    public $status_notes = '';
    
    #[Rule('required|integer|min:1|max:10')]
    public $client_satisfaction = 5;
    
    #[Rule('required|integer|min:1|max:10')]
    public $team_health = 5;

    public function selectClient($clientId)
    {
        $this->selectedClient = Client::find($clientId);
        $this->showForm = true;
        $this->resetForm();
    }

    public function backToList()
    {
        $this->showForm = false;
        $this->selectedClient = null;
        $this->resetForm();
    }

    public function saveStatus()
    {
        $this->validate();

        ClientStatusUpdate::create([
            'user_id' => auth()->id(),
            'client_id' => $this->selectedClient->id,
            'status_notes' => $this->status_notes,
            'client_satisfaction' => $this->client_satisfaction,
            'team_health' => $this->team_health,
            'status_date' => now(),
        ]);

        session()->flash('status', 'Status update saved successfully!');
        $this->backToList();
    }

    private function resetForm()
    {
        $this->status_notes = '';
        $this->client_satisfaction = 5;
        $this->team_health = 5;
    }

    public function render()
    {
        $user = auth()->user();
        
        // Get clients for the user's teams
        $clients = Client::whereIn('team_id', $user->teams->pluck('id'))
            ->with(['statusUpdates' => function ($query) {
                $query->latest('status_date');
            }])->get();

        return view('livewire.statusfaction', [
            'clients' => $clients,
        ]);
    }
}
