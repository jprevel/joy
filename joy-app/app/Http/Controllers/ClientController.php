<?php

namespace App\Http\Controllers;

use App\Models\MagicLink;
use App\Services\ContentCalendarService;
use App\Repositories\Contracts\VariantRepositoryInterface;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        private ContentCalendarService $contentCalendarService,
        private VariantRepositoryInterface $variantRepository
    ) {}

    public function access(Request $request, string $token)
    {
        $magicLink = $request->attributes->get('magic_link');
        
        if (!$magicLink) {
            return response()->view('errors.401', ['message' => 'Invalid access'], 401);
        }

        return view('client.dashboard', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
        ]);
    }

    public function calendar(Request $request)
    {
        $magicLink = $request->attributes->get('magic_link');
        
        if (!$magicLink) {
            return response()->view('errors.401', ['message' => 'Invalid access'], 401);
        }

        $variants = $this->contentCalendarService->getAllVariantsForWorkspace($magicLink->workspace->id);

        return view('client.calendar', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
            'variants' => $variants,
        ]);
    }

    public function concept(Request $request, string $token, int $conceptId)
    {
        $magicLink = $request->attributes->get('magic_link');
        
        if (!$magicLink) {
            return response()->view('errors.401', ['message' => 'Invalid access'], 401);
        }

        $concept = $magicLink->workspace->concepts()
            ->with(['variants', 'owner'])
            ->findOrFail($conceptId);

        return view('client.concept', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
            'concept' => $concept,
        ]);
    }

    public function variant(Request $request, string $token, int $variantId)
    {
        $magicLink = $request->attributes->get('magic_link');
        
        if (!$magicLink) {
            return response()->view('errors.401', ['message' => 'Invalid access'], 401);
        }

        $variant = $this->variantRepository->find($variantId);

        if (!$variant || $variant->concept->workspace_id !== $magicLink->workspace_id) {
            abort(404, 'Variant not found');
        }

        return view('client.variant', [
            'magicLink' => $magicLink,
            'workspace' => $magicLink->workspace,
            'variant' => $variant,
        ]);
    }
}