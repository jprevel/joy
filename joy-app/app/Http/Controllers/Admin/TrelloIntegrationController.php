<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrelloIntegration;
use App\Models\ClientWorkspace;
use App\Services\TrelloService;
use App\Http\Requests\StoreTrelloIntegrationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrelloIntegrationController extends Controller
{
    public function index()
    {
        $integrations = TrelloIntegration::with('workspace')
            ->latest()
            ->get();

        return view('admin.trello.index', compact('integrations'));
    }

    public function create()
    {
        $workspaces = ClientWorkspace::orderBy('name')->get();
        
        return view('admin.trello.create', compact('workspaces'));
    }

    public function store(StoreTrelloIntegrationRequest $request)
    {
        $integration = TrelloIntegration::create($request->validated());

        return redirect()
            ->route('admin.trello.show', $integration)
            ->with('success', 'Trello integration created successfully!');
    }

    public function show(TrelloIntegration $integration)
    {
        $integration->load('workspace');
        
        return view('admin.trello.show', compact('integration'));
    }

    public function edit(TrelloIntegration $integration)
    {
        $workspaces = ClientWorkspace::orderBy('name')->get();
        
        return view('admin.trello.edit', compact('integration', 'workspaces'));
    }

    public function update(StoreTrelloIntegrationRequest $request, TrelloIntegration $integration)
    {
        $integration->update($request->validated());

        return redirect()
            ->route('admin.trello.show', $integration)
            ->with('success', 'Trello integration updated successfully!');
    }

    public function destroy(TrelloIntegration $integration)
    {
        $integration->delete();

        return redirect()
            ->route('admin.trello.index')
            ->with('success', 'Trello integration deleted successfully!');
    }

    public function testConnection(TrelloIntegration $integration)
    {
        try {
            $trelloService = new TrelloService($integration);
            $result = $trelloService->testConnection();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! Connected as: ' . $result['member'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed: ' . $result['error'],
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Trello connection test failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sync(TrelloIntegration $integration)
    {
        try {
            $trelloService = new TrelloService($integration);
            $results = $trelloService->syncWorkspaceToTrello();

            $message = sprintf(
                'Sync completed! Cards created: %d, Cards updated: %d, Comments synced: %d',
                $results['cards_created'],
                $results['cards_updated'],
                $results['comments_synced']
            );

            if (!empty($results['errors'])) {
                $message .= ' (with some errors - check logs)';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Trello sync failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggle(TrelloIntegration $integration)
    {
        $integration->update(['is_active' => !$integration->is_active]);

        $status = $integration->is_active ? 'enabled' : 'disabled';

        return response()->json([
            'success' => true,
            'message' => "Trello integration {$status} successfully!",
            'is_active' => $integration->is_active,
        ]);
    }
}