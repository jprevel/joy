<?php

namespace App\Http\Controllers;
use App\Http\Traits\ApiResponse;

use App\Models\Client;
use App\Models\User;
use App\Models\ContentItem;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\CleanupService;
use App\Services\QueryBuilders\AuditLogQueryBuilder;
use App\Services\RoleDetectionService;
use App\Services\TrelloService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuditService $auditService,
        private RoleDetectionService $roleDetectionService,
        private TrelloService $trelloService,
        private CleanupService $cleanupService
    ) {}

    /**
     * Display the admin dashboard web view.
     */
    public function index()
    {
        return view('admin.index');
    }

    /**
     * Display users management page.
     */
    public function usersList()
    {
        $users = User::with(['teams', 'roles'])->orderBy('created_at', 'desc')->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Display clients management page.
     */
    public function clientsList()
    {
        $clients = Client::with(['team'])->withCount(['contentItems'])->orderBy('created_at', 'desc')->get();
        return view('admin.clients.index', compact('clients'));
    }

    /**
     * Get admin dashboard overview.
     */
    public function dashboard(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$user || !$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $stats = [
                'users' => $this->roleDetectionService->getRoleStats(),
                'clients' => [
                    'total' => Client::count(),
                    'active' => Client::whereHas('contentItems', function ($q) {
                        $q->where('created_at', '>=', now()->subDays(30));
                    })->count(),
                    'with_trello' => Client::whereNotNull('trello_board_id')->count(),
                ],
                'content' => [
                    'total' => ContentItem::count(),
                    'this_month' => ContentItem::whereMonth('created_at', now()->month)->count(),
                    'scheduled' => ContentItem::where('status', 'scheduled')->count(),
                    'pending_review' => ContentItem::where('status', 'review')->count(),
                ],
                'activity' => [
                    'total_events' => AuditLog::count(),
                    'today' => AuditLog::whereDate('created_at', today())->count(),
                    'this_week' => AuditLog::where('created_at', '>=', now()->startOfWeek())->count(),
                ],
                'magic_links' => [
                    'total' => \App\Models\MagicLink::count(),
                    'active' => \App\Models\MagicLink::where('expires_at', '>', now())->count(),
                    'accessed_today' => \App\Models\MagicLink::whereDate('accessed_at', today())->count(),
                ]
            ];

            return $this->success($stats);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load dashboard', $e);
        }
    }

    /**
     * Get system statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$user || !$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'period' => 'sometimes|in:7,30,90,365'
            ]);

            $days = $request->input('period', 30);
            $systemStats = $this->auditService->getSystemStats($days);

            // Add additional system metrics
            $systemStats['storage'] = $this->getStorageStats();
            $systemStats['performance'] = $this->getPerformanceStats();
            $systemStats['integrations'] = $this->getIntegrationStats();

            return $this->success([
                'data' => $systemStats,
                'period_days' => $days
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load system statistics', $e);
        }
    }

    /**
     * Get audit logs.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$user || !$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'client_id' => 'sometimes|exists:clients,id',
                'user_id' => 'sometimes|exists:users,id',
                'event' => 'sometimes|string',
                'from_date' => 'sometimes|date',
                'to_date' => 'sometimes|date|after_or_equal:from_date',
                'limit' => 'sometimes|integer|min:1|max:1000'
            ]);

            $limit = $request->input('limit', 100);

            $queryBuilder = new AuditLogQueryBuilder();
            $logs = $queryBuilder
                ->applyFilters($request)
                ->limit($limit)
                ->get();

            return $this->success([
                'data' => $logs->map(fn($log) => $this->auditService->formatForApi($log)),
                'meta' => [
                    'total_shown' => $logs->count(),
                    'limit' => $limit,
                    'filters_applied' => $request->only(['client_id', 'user_id', 'event', 'from_date', 'to_date'])
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load audit logs', $e);
        }
    }

    /**
     * Get users management data.
     */
    public function users(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$user || !$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'role' => 'sometimes|in:admin,agency,client',
                'client_id' => 'sometimes|exists:clients,id'
            ]);

            $query = User::with('client');

            if ($request->has('role')) {
                $query->where('role', $request->input('role'));
            }

            if ($request->has('client_id')) {
                $query->where('client_id', $request->input('client_id'));
            }

            $users = $query->orderBy('created_at', 'desc')->get();

            return $this->success([
                'data' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'client_id' => $user->client_id,
                        'client_name' => $user->client?->name,
                        'created_at' => $user->created_at->toISOString(),
                        'last_login' => $user->last_login_at?->toISOString(),
                    ];
                }),
                'meta' => [
                    'total_users' => $users->count(),
                    'role_stats' => $this->roleDetectionService->getRoleStats()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load users', $e);
        }
    }

    /**
     * Get clients management data.
     */
    public function clients(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$user || !$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $clients = Client::withCount(['contentItems', 'users', 'magicLinks'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success([
                'data' => $clients->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'created_at' => $client->created_at->toISOString(),
                        'content_items_count' => $client->content_items_count,
                        'users_count' => $client->users_count,
                        'magic_links_count' => $client->magic_links_count,
                        'trello_configured' => $client->hasTrelloIntegration(),
                        'trello_board_id' => $client->trello_board_id,
                        'trello_list_id' => $client->trello_list_id,
                    ];
                }),
                'meta' => [
                    'total_clients' => $clients->count(),
                    'with_trello' => $clients->filter(fn($c) => $c->hasTrelloIntegration())->count()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load clients', $e);
        }
    }

    /**
     * System health check.
     */
    public function health(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$user || !$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $health = [
                'database' => $this->checkDatabaseHealth(),
                'storage' => $this->checkStorageHealth(),
                'integrations' => $this->checkIntegrationsHealth(),
                'performance' => $this->checkPerformanceHealth(),
                'overall_status' => 'healthy'
            ];

            // Determine overall status
            $issues = array_filter($health, fn($check) => is_array($check) && ($check['status'] ?? 'ok') !== 'ok');
            if (!empty($issues)) {
                $health['overall_status'] = 'degraded';
            }

            return $this->success([
                'data' => $health,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return $this->serverError('System health check failed', $e);
        }
    }

    /**
     * Cleanup operations.
     */
    public function cleanup(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$user || !$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'operation' => 'required|in:audit_logs,expired_magic_links,failed_syncs',
                'days' => 'sometimes|integer|min:1|max:365'
            ]);

            $operation = $request->input('operation');
            $days = $request->input('days', 90);

            $results = $this->cleanupService->execute($operation, $days);

            return $this->success(
                $results,
                'Cleanup operation completed successfully'
            );

        } catch (\Exception $e) {
            return $this->serverError('Cleanup operation failed', $e);
        }
    }

    /**
     * Get storage statistics.
     */
    private function getStorageStats(): array
    {
        try {
            $mediaPath = storage_path('app/public/content-media');
            $totalSize = 0;
            $fileCount = 0;

            if (is_dir($mediaPath)) {
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($mediaPath));
                foreach ($files as $file) {
                    if ($file->isFile()) {
                        $totalSize += $file->getSize();
                        $fileCount++;
                    }
                }
            }

            return [
                'total_files' => $fileCount,
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'average_file_size_kb' => $fileCount > 0 ? round($totalSize / $fileCount / 1024, 2) : 0
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to calculate storage stats'];
        }
    }

    /**
     * Get performance statistics.
     */
    private function getPerformanceStats(): array
    {
        return [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];
    }

    /**
     * Get integration statistics.
     */
    private function getIntegrationStats(): array
    {
        return [
            'trello_configured_clients' => Client::whereNotNull('trello_board_id')->count(),
            'total_trello_cards' => \App\Models\TrelloCard::count(),
            'pending_trello_syncs' => \App\Models\TrelloCard::where('sync_status', 'pending')->count(),
            'failed_trello_syncs' => \App\Models\TrelloCard::where('sync_status', 'failed')->count()
        ];
    }

    /**
     * Check database health.
     */
    private function checkDatabaseHealth(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check storage health.
     */
    private function checkStorageHealth(): array
    {
        try {
            $path = storage_path('app/public');
            if (!is_writable($path)) {
                return ['status' => 'error', 'message' => 'Storage directory not writable'];
            }
            return ['status' => 'ok', 'message' => 'Storage directory accessible'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Storage check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check integrations health.
     */
    private function checkIntegrationsHealth(): array
    {
        $trelloConfigured = config('services.trello.key') && config('services.trello.token');
        return [
            'trello' => [
                'configured' => $trelloConfigured,
                'status' => $trelloConfigured ? 'ok' : 'warning',
                'message' => $trelloConfigured ? 'Trello API configured' : 'Trello API not configured'
            ]
        ];
    }

    /**
     * Check performance health.
     */
    private function checkPerformanceHealth(): array
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
        $memoryLimit = ini_get('memory_limit');

        if ($memoryUsage > 128) {
            return ['status' => 'warning', 'message' => "High memory usage: {$memoryUsage}MB"];
        }

        return ['status' => 'ok', 'message' => "Memory usage normal: {$memoryUsage}MB"];
    }
}