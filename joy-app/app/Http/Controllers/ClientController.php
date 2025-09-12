<?php

namespace App\Http\Controllers;

use App\Models\MagicLink;
use App\Services\ContentCalendarService;
use App\Services\MagicLinkValidator;
use App\Repositories\Contracts\VariantRepositoryInterface;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        private ContentCalendarService $contentCalendarService,
        private VariantRepositoryInterface $variantRepository,
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
        $variants = $this->contentCalendarService->getAllVariantsForWorkspace($magicLink->workspace->id);

        return view('client.calendar', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
            'variants' => $variants,
        ]);
    }

    public function concept(Request $request, string $token, int $conceptId)
    {
        $magicLink = $this->magicLinkValidator->validateOrFail($request);
        
        $concept = $magicLink->workspace->concepts()
            ->with(['variants', 'owner'])
            ->findOrFail($conceptId);
            
        $this->magicLinkValidator->logAccess($magicLink, 'concept_access', ['concept_id' => $conceptId]);

        return view('client.concept', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
            'concept' => $concept,
        ]);
    }

    public function variant(Request $request, string $token, int $variantId)
    {
        $magicLink = $this->magicLinkValidator->validateOrFail($request);
        
        $variant = $this->variantRepository->find($variantId);

        if (!$variant || !$this->magicLinkValidator->canAccessContentItem($magicLink, $variant)) {
            abort(404, 'Variant not found');
        }
        
        $this->magicLinkValidator->logAccess($magicLink, 'variant_access', ['variant_id' => $variantId]);

        return view('client.variant', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
            'variant' => $variant,
        ]);
    }
}