<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ContentItem;
use App\Models\Status;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

class AddContent extends Component
{
    use WithFileUploads;

    public string $currentRole = 'agency';
    
    #[Validate('required')]
    public $client_id = '';
    
    #[Validate('required|string|max:255')]
    public $title = '';
    
    #[Validate('nullable|string')]
    public $copy = '';
    
    #[Validate('nullable|string')]
    public $notes = '';
    
    #[Validate('required|in:Facebook,Instagram,LinkedIn,Twitter,Blog')]
    public $platform = '';
    
    #[Validate('required|date')]
    public $scheduled_at = '';
    
    #[Validate('nullable|image|max:10240')] // 10MB max
    public $image;

    public function mount($role = 'agency')
    {
        $this->currentRole = $role;
        $this->scheduled_at = Carbon::now()->format('Y-m-d\TH:i');
    }

    public function getCurrentUserRole()
    {
        $demoUsers = [
            'client' => \App\Models\User::whereHas('roles', function($q) { 
                $q->where('name', 'client'); 
            })->first(),
            'agency' => \App\Models\User::whereHas('roles', function($q) { 
                $q->where('name', 'agency'); 
            })->first(),
            'admin' => \App\Models\User::whereHas('roles', function($q) { 
                $q->where('name', 'admin'); 
            })->first(),
        ];
        
        return $demoUsers[$this->currentRole] ?? null;
    }

    public function hasPermission($permission)
    {
        $user = $this->getCurrentUserRole();
        return $user ? $user->can($permission) : false;
    }

    public function save()
    {
        $this->validate();

        // Check permission
        if (!$this->hasPermission('edit content')) {
            session()->flash('error', 'You do not have permission to create content.');
            return;
        }

        try {
            // Get default status (Draft)
            $defaultStatus = Status::where('name', 'Draft')->first();
            
            $contentItem = ContentItem::create([
                'client_id' => $this->client_id,
                'title' => $this->title,
                'copy' => $this->copy,
                'notes' => $this->notes,
                'platform' => $this->platform,
                'scheduled_at' => Carbon::parse($this->scheduled_at),
                'status_id' => $defaultStatus?->id,
                'status' => $defaultStatus?->name ?? 'Draft', // Fallback for backward compatibility
                'owner_id' => 1, // This would be the authenticated agency user in production
            ]);

            // Handle image upload
            if ($this->image) {
                $contentItem->storeImage($this->image);
            }

            session()->flash('success', 'Content item created successfully!');
            
            // Redirect back to calendar
            return redirect()->route('calendar.role', $this->currentRole);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create content item: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('calendar.role', $this->currentRole);
    }

    public function render()
    {
        $clients = Client::all();
        $platforms = ['Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog'];
        
        return view('livewire.add-content', [
            'clients' => $clients,
            'platforms' => $platforms,
        ])->layout('components.layout');
    }
}
