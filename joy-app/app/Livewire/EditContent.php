<?php

namespace App\Livewire;

use App\Models\ContentItem;
use App\Models\User;
use App\Services\ContentItemService;
use App\Services\RoleDetectionService;
use App\Helpers\PlatformHelper;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

class EditContent extends Component
{
    use WithFileUploads;

    public ?ContentItem $contentItem = null;
    public ?User $currentUser = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    public ?string $description = null;

    #[Validate('required|string')]
    public string $platform = '';

    public ?string $scheduled_at = null;
    public $media_file = null;
    public ?string $existing_media_path = null;

    public function __construct(
        private ContentItemService $contentItemService,
        private RoleDetectionService $roleDetectionService
    ) {
        parent::__construct();
    }

    public function mount(ContentItem $contentItem)
    {
        $this->contentItem = $contentItem;
        $this->currentUser = $this->roleDetectionService->getCurrentUser();

        // Check permissions
        if (!$this->hasPermission('edit content')) {
            abort(403, 'Access denied');
        }

        // Populate form fields
        $this->title = $this->contentItem->title;
        $this->description = $this->contentItem->description;
        $this->platform = $this->contentItem->platform;
        $this->scheduled_at = $this->contentItem->scheduled_at?->format('Y-m-d\TH:i');
        $this->existing_media_path = $this->contentItem->media_path;
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->currentUser || !$this->contentItem) {
            return false;
        }

        // Check role permission
        if (!$this->roleDetectionService->hasPermission($this->currentUser, $permission)) {
            return false;
        }

        // Agency can edit clients they manage
        if ($this->roleDetectionService->isAgency($this->currentUser)) {
            $accessibleClients = $this->roleDetectionService->getAccessibleClients($this->currentUser);
            return $accessibleClients->contains('id', $this->contentItem->client_id);
        }

        // Admin can edit everything
        return $this->roleDetectionService->isAdmin($this->currentUser);
    }

    public function save()
    {
        $this->validate();

        try {
            $data = [
                'title' => $this->title,
                'description' => $this->description,
                'platform' => $this->platform,
                'scheduled_at' => $this->scheduled_at ? Carbon::parse($this->scheduled_at) : null,
            ];

            // Handle file upload
            if ($this->media_file) {
                $data['media_file'] = $this->media_file;
            }

            $this->contentItemService->update($this->contentItem->id, $data, $this->currentUser);

            session()->flash('success', 'Content updated successfully!');

            return redirect()->route('content.detail', $this->contentItem);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update content: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('content.detail', $this->contentItem);
    }

    public function render()
    {
        $platforms = PlatformHelper::getAllPlatforms();

        return view('livewire.edit-content', [
            'platforms' => $platforms,
        ])->layout('components.layouts.app');
    }
}