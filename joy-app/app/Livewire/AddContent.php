<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ContentItem;
use App\Models\Status;
use App\Traits\HasRoleManagement;
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
        // Validate content items
        $this->validateContentItems();

        // Check permission
        if (!$this->hasPermission('edit content')) {
            session()->flash('error', 'You do not have permission to create content.');
            return;
        }

        try {
            $defaultStatus = Status::where('name', 'Draft')->first();
            $createdCount = 0;
            
            foreach ($this->contentItems as $item) {
                $contentItem = ContentItem::create([
                    'client_id' => $this->client_id,
                    'title' => $item['title'],
                    'copy' => $item['copy'] ?? '',
                    'platform' => $item['platform'],
                    'scheduled_at' => Carbon::parse($item['scheduled_at'])->startOfDay(),
                    'status_id' => $defaultStatus?->id,
                    'status' => $defaultStatus?->name ?? 'Draft',
                    'owner_id' => 1,
                ]);

                // Handle image upload if present
                if ($item['image']) {
                    $contentItem->storeImage($item['image']);
                }
                
                $createdCount++;
            }

            session()->flash('success', "Successfully created {$createdCount} content item(s)!");
            return redirect()->route('calendar.role', $this->currentRole);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create content items: ' . $e->getMessage());
        }
    }
    
    private function validateContentItems()
    {
        $rules = [];
        $platforms = ['Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog'];
        
        foreach ($this->contentItems as $index => $item) {
            $rules["contentItems.{$index}.title"] = 'required|string|max:255';
            $rules["contentItems.{$index}.platform"] = 'required|in:' . implode(',', $platforms);
            $rules["contentItems.{$index}.scheduled_at"] = 'required|date_format:Y-m-d';
            $rules["contentItems.{$index}.image"] = 'nullable|image|max:10240';
        }
        
        $this->validate($rules);
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
        
        $platforms = ['Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog'];
        
        return view('livewire.add-content', [
            'clients' => $clients,
            'platforms' => $platforms,
        ])->layout('components.layout');
    }
}
