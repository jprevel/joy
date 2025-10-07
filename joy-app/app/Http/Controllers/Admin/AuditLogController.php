<?php

namespace App\Http\Controllers\Admin;

use App\Constants\AuditConstants;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClientWorkspace;
use App\Services\Audit\AuditExportService;
use App\Services\AuditService;
use App\Services\AuditLogViewerService;
use App\DTOs\AuditLogRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AuditLogController extends Controller
{
    public function __construct(
        private AuditLogViewerService $auditLogViewerService
    ) {}
    public function index(Request $request)
    {
        $filters = $request->only(['workspace_id', 'action', 'severity', 'user_type', 'days']);
        $days = (int) ($filters['days'] ?? 7);

        $query = AuditLog::with(['workspace'])
            ->recent($days)
            ->latest();

        // Apply filters
        if (!empty($filters['workspace_id'])) {
            $query->forWorkspace($filters['workspace_id']);
        }

        if (!empty($filters['action'])) {
            $query->forAction($filters['action']);
        }

        if (!empty($filters['severity'])) {
            $query->bySeverity($filters['severity']);
        }

        if (!empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        $logs = $query->paginate(AuditConstants::PAGINATION_LIMIT)->withQueryString();
        
        $workspaces = ClientWorkspace::orderBy('name')->get();
        
        // Get filter options
        $actions = AuditLog::distinct()->pluck('action')->filter()->sort()->values();
        $severities = ['debug', 'info', 'warning', 'error', 'critical'];
        $userTypes = ['system', 'user', 'magic_link'];

        return view('admin.audit.index', compact(
            'logs', 'workspaces', 'actions', 'severities', 'userTypes', 'filters', 'days'
        ));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load(['workspace']);
        
        return view('admin.audit.show', compact('auditLog'));
    }

    public function dashboard(Request $request)
    {
        $days = (int) ($request->get('days', 7));
        $workspaceId = $request->get('workspace_id');

        // Generate comprehensive report
        $report = AuditService::generateReport($workspaceId, $days);
        
        // Get recent activity
        $recentActivity = AuditService::getRecentActivity($workspaceId, 20, $days);
        
        // Get security events
        $securityEvents = AuditService::getSecurityEvents($days);
        
        // Get top actions
        $topActions = AuditLog::recent($days)
            ->when($workspaceId, fn($q) => $q->forWorkspace($workspaceId))
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Get activity by hour (last 24 hours)
        $hourlyActivity = AuditLog::where('created_at', '>=', now()->subDay())
            ->when($workspaceId, fn($q) => $q->forWorkspace($workspaceId))
            ->selectRaw('EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour');

        $workspaces = ClientWorkspace::orderBy('name')->get();

        return view('admin.audit.dashboard', compact(
            'report', 'recentActivity', 'securityEvents', 'topActions', 
            'hourlyActivity', 'workspaces', 'days', 'workspaceId'
        ));
    }

    public function export(Request $request, AuditExportService $exportService)
    {
        $filters = $request->only(['workspace_id', 'action', 'severity', 'user_type', 'days']);
        $format = $request->get('format', 'csv');
        
        return $exportService->exportLogs($filters, $format);
    }

    public function cleanup(Request $request)
    {
        $daysToKeep = (int) $request->get('days', AuditConstants::DEFAULT_RETENTION_DAYS);
        
        if ($daysToKeep < AuditConstants::MIN_CLEANUP_DAYS) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete logs newer than {$this->getMinCleanupDays()} days for compliance reasons.",
            ], 422);
        }

        $deletedCount = AuditService::cleanupOldLogs($daysToKeep);
        
        // Log the cleanup action
        AuditService::log(
            AuditLogRequest::create('audit_cleanup')
                ->withNewValues([
                    'days_kept' => $daysToKeep,
                    'records_deleted' => $deletedCount,
                ])
                ->withSeverity('info')
                ->withTags(['admin_action', 'cleanup'])
        );

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} old audit log records.",
            'deleted_count' => $deletedCount,
        ]);
    }

    public function stats(Request $request)
    {
        $days = (int) ($request->get('days', 7));
        $workspaceId = $request->get('workspace_id');

        $query = AuditLog::recent($days);
        
        if ($workspaceId) {
            $query->forWorkspace($workspaceId);
        }

        $stats = [
            'total_events' => $query->count(),
            'events_by_day' => $query->selectRaw('created_at::date as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date'),
            'events_by_severity' => $query->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->get()
                ->pluck('count', 'severity'),
            'events_by_action' => $query->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'action'),
        ];

        return response()->json($stats);
    }
    
    /**
     * Display the last 100 audit log entries
     */
    public function recent(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'user_id', 'event']);

        if ($request->expectsJson()) {
            $auditLogs = $this->auditLogViewerService->getRecentAuditLogs($filters);

            $formattedLogs = $auditLogs->map(function ($auditLog) {
                return $this->auditLogViewerService->formatAuditLogEntry($auditLog);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedLogs,
                'count' => $formattedLogs->count(),
                'message' => $formattedLogs->isEmpty()
                    ? 'No audit log entries found matching the criteria.'
                    : "Showing {$formattedLogs->count()} most recent audit log entries."
            ]);
        }

        // For web requests, show the viewer page
        $auditLogs = $this->auditLogViewerService->getRecentAuditLogs($filters);
        $eventTypes = $this->auditLogViewerService->getEventTypes();
        $users = $this->auditLogViewerService->getAuditLogUsers();

        return view('admin.audit.recent', [
            'auditLogs' => $auditLogs,
            'eventTypes' => $eventTypes,
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    private function getMinCleanupDays(): int
    {
        return AuditConstants::MIN_CLEANUP_DAYS;
    }
}