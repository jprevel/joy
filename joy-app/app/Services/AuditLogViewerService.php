<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AuditLogViewerService
{
    /**
     * Get the last 100 audit log entries with optional filtering
     */
    public function getRecentAuditLogs(array $filters = []): Collection
    {
        $query = AuditLog::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(100);

        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        // Apply user filter
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Apply event type filter
        if (!empty($filters['event'])) {
            $query->where('event', 'like', '%' . $filters['event'] . '%');
        }

        return $query->get();
    }

    /**
     * Get paginated audit logs for admin interface
     */
    public function getPaginatedAuditLogs(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::with(['user'])
            ->orderBy('created_at', 'desc');

        // Apply same filters as getRecentAuditLogs
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['event'])) {
            $query->where('event', 'like', '%' . $filters['event'] . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get distinct event types for filter dropdown
     */
    public function getEventTypes(): Collection
    {
        return AuditLog::distinct()
            ->orderBy('event')
            ->pluck('event')
            ->filter()
            ->values();
    }

    /**
     * Get users who have audit log entries for filter dropdown
     */
    public function getAuditLogUsers(): Collection
    {
        return AuditLog::with('user')
            ->whereNotNull('user_id')
            ->distinct()
            ->get(['user_id'])
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * Format audit log entry for display
     */
    public function formatAuditLogEntry(AuditLog $auditLog): array
    {
        return [
            'id' => $auditLog->id,
            'timestamp' => $auditLog->created_at->format('Y-m-d H:i:s'),
            'event' => $auditLog->event,
            'user' => $auditLog->user ? $auditLog->user->name : 'System',
            'user_email' => $auditLog->user ? $auditLog->user->email : null,
            'ip_address' => $auditLog->ip_address,
            'user_agent' => $auditLog->user_agent,
            'old_values' => $auditLog->old_values,
            'new_values' => $auditLog->new_values,
            'auditable_type' => $auditLog->auditable_type,
            'auditable_id' => $auditLog->auditable_id,
        ];
    }
}