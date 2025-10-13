<?php

namespace App\Services;

use App\Contracts\AuditEventFormatterContract;
use App\Models\AuditLog;
use Illuminate\Support\Str;

class AuditEventFormatterService implements AuditEventFormatterContract
{
    /**
     * Format audit log changes into human-readable inline display
     */
    public function formatChangesInline(AuditLog $auditLog): string
    {
        if (!$this->hasChanges($auditLog)) {
            return 'No changes recorded';
        }

        $changes = $this->getDetailedChanges($auditLog);

        if ($this->shouldTruncateChanges($auditLog)) {
            return $this->formatTruncatedChanges($auditLog);
        }

        $formatted = [];
        foreach ($changes as $change) {
            if ($change['old'] === null) {
                $formatted[] = "{$change['field']}: added '{$change['new']}'";
            } elseif ($change['new'] === null) {
                $formatted[] = "{$change['field']}: removed '{$change['old']}'";
            } else {
                $formatted[] = "{$change['field']}: '{$change['old']}' → '{$change['new']}'";
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * Get detailed change breakdown for expandable view
     */
    public function getDetailedChanges(AuditLog $auditLog): array
    {
        $oldValues = $auditLog->old_values ?? [];
        $newValues = $auditLog->new_values ?? [];

        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            // Skip if values are identical
            if ($oldValue === $newValue) {
                continue;
            }

            $changes[] = [
                'field' => $this->formatFieldName($key),
                'old' => $this->formatValue($oldValue),
                'new' => $this->formatValue($newValue),
            ];
        }

        return $changes;
    }

    /**
     * Check if audit log has changes
     */
    public function hasChanges(AuditLog $auditLog): bool
    {
        return !empty($auditLog->old_values) || !empty($auditLog->new_values);
    }

    /**
     * Get count of changed fields
     */
    public function getChangeCount(AuditLog $auditLog): int
    {
        return count($this->getDetailedChanges($auditLog));
    }

    /**
     * Determine if changes should be truncated (>5 fields changed)
     */
    public function shouldTruncateChanges(AuditLog $auditLog): bool
    {
        return $this->getChangeCount($auditLog) > 5;
    }

    /**
     * Format truncated change summary for initial display
     */
    public function formatTruncatedChanges(AuditLog $auditLog, int $limit = 5): string
    {
        $changes = $this->getDetailedChanges($auditLog);
        $total = count($changes);

        $formatted = [];
        $limitedChanges = array_slice($changes, 0, $limit);

        foreach ($limitedChanges as $change) {
            if ($change['old'] === null) {
                $formatted[] = "{$change['field']}: added";
            } elseif ($change['new'] === null) {
                $formatted[] = "{$change['field']}: removed";
            } else {
                $formatted[] = "{$change['field']}: changed";
            }
        }

        $summary = implode(', ', $formatted);
        $remaining = $total - $limit;

        if ($remaining > 0) {
            $summary .= " (+" . $remaining . " more)";
        }

        return $summary;
    }

    /**
     * Format event name to human-readable format
     */
    public function formatEventName(string $eventName): string
    {
        // Convert snake_case to Title Case
        return Str::title(str_replace('_', ' ', $eventName));
    }

    /**
     * Get color class for event severity display
     */
    public function getEventColorClass(string $eventName): string
    {
        $eventLower = strtolower($eventName);

        // Deletion events - red
        if (str_contains($eventLower, 'delete') || str_contains($eventLower, 'removed')) {
            return 'text-red-600 bg-red-50';
        }

        // Creation events - green
        if (str_contains($eventLower, 'create') || str_contains($eventLower, 'added')) {
            return 'text-green-600 bg-green-50';
        }

        // Update/approval events - blue
        if (str_contains($eventLower, 'update') || str_contains($eventLower, 'approve')) {
            return 'text-blue-600 bg-blue-50';
        }

        // Rejection events - orange
        if (str_contains($eventLower, 'reject')) {
            return 'text-orange-600 bg-orange-50';
        }

        // Access events - gray
        if (str_contains($eventLower, 'access') || str_contains($eventLower, 'login')) {
            return 'text-gray-600 bg-gray-50';
        }

        // Default - neutral
        return 'text-gray-600 bg-gray-100';
    }

    /**
     * Format auditable entity name for display
     */
    public function formatAuditableEntity(AuditLog $auditLog): string
    {
        if (!$auditLog->auditable_type) {
            return 'Unknown';
        }

        $modelClass = class_basename($auditLog->auditable_type);
        $modelName = Str::title(Str::snake($modelClass, ' '));

        if ($auditLog->auditable_id) {
            return "{$modelName} #{$auditLog->auditable_id}";
        }

        return $modelName;
    }

    /**
     * Format timestamp for human-readable display
     */
    public function formatTimestamp(\DateTimeInterface $timestamp): string
    {
        $carbon = \Carbon\Carbon::parse($timestamp);

        // If within last 7 days, show relative time
        if ($carbon->isToday()) {
            return $carbon->diffForHumans();
        } elseif ($carbon->greaterThan(now()->subDays(7))) {
            return $carbon->diffForHumans() . ' (' . $carbon->format('M d, h:i A') . ')';
        }

        // Otherwise show absolute time
        return $carbon->format('M d, Y h:i A');
    }

    /**
     * Format field name to human-readable
     */
    protected function formatFieldName(string $field): string
    {
        return Str::title(str_replace('_', ' ', $field));
    }

    /**
     * Format value for display
     */
    protected function formatValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
