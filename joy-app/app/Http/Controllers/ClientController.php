<?php

namespace App\Http\Controllers;

use App\Models\MagicLink;
use App\Services\ContentCalendarService;
use App\Services\MagicLinkValidator;
use App\Repositories\Contracts\ContentItemRepositoryInterface;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        private ContentCalendarService $contentCalendarService,
        private ContentItemRepositoryInterface $contentItemRepository,
        private MagicLinkValidator $magicLinkValidator
    ) {}

    public function access(Request $request, string $token)
    {
        $magicLink = $this->magicLinkValidator->validateOrFail($request);
        
        $this->magicLinkValidator->logAccess($magicLink, 'dashboard_access');

        return view('client.dashboard', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
        ]);
    }

    public function calendar(Request $request)
    {
        $magicLink = $this->magicLinkValidator->validateOrFail($request);
        
        $this->magicLinkValidator->logAccess($magicLink, 'calendar_access');
        $contentItems = $this->contentCalendarService->getAllContentItemsForWorkspace($magicLink->workspace->id);

        return view('client.calendar', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
            'contentItems' => $contentItems,
        ]);
    }

    public function concept(Request $request, string $token, int $conceptId)
    {
        $magicLink = $this->magicLinkValidator->validateOrFail($request);
        
        $concept = $magicLink->workspace->concepts()
            ->with(['contentItems', 'owner'])
            ->findOrFail($conceptId);
            
        $this->magicLinkValidator->logAccess($magicLink, 'concept_access', ['concept_id' => $conceptId]);

        return view('client.concept', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
            'concept' => $concept,
        ]);
    }

    public function contentItem(Request $request, string $token, int $contentItemId)
    {
        $magicLink = $this->magicLinkValidator->validateOrFail($request);
        
        $contentItem = $this->contentItemRepository->find($contentItemId);

        if (!$contentItem || !$this->magicLinkValidator->canAccessContentItem($magicLink, $contentItem)) {
            abort(404, 'Content item not found');
        }
        
        $this->magicLinkValidator->logAccess($magicLink, 'content_item_access', ['content_item_id' => $contentItemId]);

        return view('client.content-item', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
            'contentItem' => $contentItem,
        ]);
    }
}