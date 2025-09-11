<?php

namespace App\Services\Audit;

use App\Constants\AuditConstants;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuditExportService
{
    private const CSV_HEADERS = [
        'Date/Time', 'Workspace', 'User', 'Action', 'Model', 
        'Severity', 'IP Address', 'User Agent', 'Changes'
    ];
    
    public function exportLogs(array $filters, string $format = 'csv'): StreamedResponse|JsonResponse
    {
        $logs = $this->buildExportQuery($filters)->get();
        
        $this->logExportAction($filters, $logs->count());
        
        return $this->generateExportResponse($logs, $format);
    }
    
    private function buildExportQuery(array $filters): Builder
    {
        $days = (int) ($filters['days'] ?? 30);
        
        $query = AuditLog::with(['workspace'])
            ->recent($days)
            ->latest()
            ->limit(AuditConstants::EXPORT_LIMIT);
        
        $this->applyFilters($query, $filters);
        
        return $query;
    }
    
    private function applyFilters(Builder $query, array $filters): void
    {
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
    }
    
    private function logExportAction(array $filters, int $recordCount): void
    {
        AuditService::logExport('audit_logs', $filters, $recordCount, ['admin_export']);
    }
    
    private function generateExportResponse(Collection $logs, string $format): StreamedResponse|JsonResponse
    {
        return match ($format) {
            'json' => $this->generateJsonResponse($logs),
            'csv' => $this->generateCsvResponse($logs),
            default => $this->generateCsvResponse($logs),
        };
    }
    
    private function generateJsonResponse(Collection $logs): JsonResponse
    {
        return Response::json($logs->toArray());
    }
    
    private function generateCsvResponse(Collection $logs): StreamedResponse
    {
        $filename = $this->generateFilename();
        $headers = $this->getCsvHeaders($filename);
        
        return Response::stream(
            function() use ($logs) {
                $this->streamCsvData($logs);
            }, 
            200, 
            $headers
        );
    }
    
    private function streamCsvData(Collection $logs): void
    {
        $handle = fopen('php://output', 'w');
        
        fputcsv($handle, self::CSV_HEADERS);
        
        foreach ($logs as $log) {
            fputcsv($handle, $this->buildCsvRow($log));
        }
        
        fclose($handle);
    }
    
    private function buildCsvRow(AuditLog $log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->workspace?->name ?? 'N/A',
            $log->getUserDisplayName(),
            $log->getActionDisplayName(),
            $log->auditable_type ? class_basename($log->auditable_type) : 'N/A',
            $log->severity,
            $log->ip_address ?? 'N/A',
            $log->user_agent ?? 'N/A',
            $this->formatChanges($log),
        ];
    }
    
    private function formatChanges(AuditLog $log): string
    {
        if (!$log->hasChanges()) {
            return '';
        }
        
        return json_encode([
            'old' => $log->old_values,
            'new' => $log->new_values
        ]);
    }
    
    private function generateFilename(): string
    {
        return 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
    }
    
    private function getCsvHeaders(string $filename): array
    {
        return [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
    }
}