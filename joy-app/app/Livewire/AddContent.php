<?php

namespace App\Livewire;

use App\Models\Client;
use App\Services\ContentItemService;
use App\Traits\HasRoleManagement;
use App\Helpers\PlatformHelper;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

class AddContent extends Component
{
    use WithFileUploads, HasRoleManagement;

    public string $currentRole = 'agency';
    
    #[Validate('required')]
    public $client_id = '';
    
    public $step = 1; // 1 = client selection, 2 = content items
    
    public $contentItems = [];

    public function mount($role = 'agency')
    {
        $this->currentRole = $role;
        $this->initializeContentItems();
    }
    
    private function initializeContentItems()
    {
        $this->contentItems = [
            [
                'title' => '',
                'copy' => '',
                'platform' => '',
                'scheduled_at' => Carbon::now()->format('Y-m-d'),
                'image' => null
            ]
        ];
    }
    
    public function selectClient()
    {
        $this->validate(['client_id' => 'required']);
        $this->step = 2;
    }
    
    public function updatedClientId($value)
    {
        if ($value) {
            $this->step = 2;
        }
    }
    
    public function addContentItem()
    {
        $this->contentItems[] = [
            'title' => '',
            'copy' => '',
            'platform' => '',
            'scheduled_at' => Carbon::now()->format('Y-m-d'),
            'image' => null
        ];
    }
    
    public function removeContentItem($index)
    {
        if (count($this->contentItems) > 1) {
            unset($this->contentItems[$index]);
            $this->contentItems = array_values($this->contentItems); // Reindex
        }
    }
    
    public function backToClientSelection()
    {
        $this->step = 1;
    }

    public function save()
    {
        // Check permission first
        if (!$this->hasPermission('edit content')) {
            session()->flash('error', 'You do not have permission to create content.');
            return;
        }

        // Validate content items using the service
        $contentItemService = app(ContentItemService::class);
        $rules = $contentItemService->validateContentItems($this->contentItems);
        $this->validate($rules);

        try {
            $createdItems = $contentItemService->createContentItems(
                $this->contentItems,
                $this->client_id
            );

            $createdCount = count($createdItems);
            session()->flash('success', "Successfully created {$createdCount} content item(s)!");
            return redirect()->route('calendar.role', $this->currentRole);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create content items: ' . $e->getMessage());
        }
    }
    

    public function cancel()
    {
        return redirect()->route('calendar.role', $this->currentRole);
    }

    public function render()
    {
        // Get clients accessible to the current user based on their teams
        $currentUser = $this->getCurrentUserRole();
        $clients = $currentUser ? $currentUser->accessibleClients()->get() : Client::all();
        
        $platforms = PlatformHelper::getAllPlatforms();
        
        return view('livewire.add-content', [
            'clients' => $clients,
            'platforms' => $platforms,
        ])->layout('components.layout');
    }
}
