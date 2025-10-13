<?php

namespace App\Contracts;

use App\Models\AuditLog;

/**
 * Contract for Audit Event formatting operations
 *
 * Defines methods for formatting audit log changes into human-readable
 * format for display in admin UI with inline change details.
 *
 * @package App\Contracts
 */
interface AuditEventFormatterContract
{
    /**
     * Format audit log changes into human-readable inline display
     *
     * Converts old_values and new_values JSON into readable format:
     * "name: 'John' → 'Jane', email: 'old@example.com' → 'new@example.com'"
     *
     * @param AuditLog $auditLog Audit log instance
     * @return string Formatted change summary
     */
    public function formatChangesInline(AuditLog $auditLog): string;

    /**
     * Get detailed change breakdown for expandable view
     *
     * Returns array of individual field changes with old/new values
     *
     * @param AuditLog $auditLog Audit log instance
     * @return array [['field' => 'name', 'old' => 'John', 'new' => 'Jane'], ...]
     */
    public function getDetailedChanges(AuditLog $auditLog): array;

    /**
     * Check if audit log has changes (old_values or new_values not empty)
     *
     * @param AuditLog $auditLog Audit log instance
     * @return bool True if changes exist
     */
    public function hasChanges(AuditLog $auditLog): bool;

    /**
     * Get count of changed fields
     *
     * @param AuditLog $auditLog Audit log instance
     * @return int Number of fields changed
     */
    public function getChangeCount(AuditLog $auditLog): int;

    /**
     * Determine if changes should be truncated (>5 fields changed)
     *
     * @param AuditLog $auditLog Audit log instance
     * @return bool True if truncation recommended
     */
    public function shouldTruncateChanges(AuditLog $auditLog): bool;

    /**
     * Format truncated change summary for initial display
     *
     * Shows first 5 changes with "Show all X changes" link
     *
     * @param AuditLog $auditLog Audit log instance
     * @param int $limit Number of changes to show initially
     * @return string Truncated change summary with expand link
     */
    public function formatTruncatedChanges(AuditLog $auditLog, int $limit = 5): string;

    /**
     * Format event name to human-readable format
     *
     * Converts snake_case to Title Case: "user_created" → "User Created"
     *
     * @param string $eventName Raw event name
     * @return string Human-readable event name
     */
    public function formatEventName(string $eventName): string;

    /**
     * Get color class for event severity display
     *
     * Used for badge styling in UI
     *
     * @param string $eventName Event name
     * @return string Tailwind CSS color class
     */
    public function getEventColorClass(string $eventName): string;

    /**
     * Format auditable entity name for display
     *
     * Converts "App\Models\User" → "User #123"
     *
     * @param AuditLog $auditLog Audit log instance
     * @return string Formatted entity name
     */
    public function formatAuditableEntity(AuditLog $auditLog): string;

    /**
     * Format timestamp for human-readable display
     *
     * Returns relative time: "2 hours ago" or absolute for old entries
     *
     * @param \DateTimeInterface $timestamp Timestamp to format
     * @return string Formatted timestamp
     */
    public function formatTimestamp(\DateTimeInterface $timestamp): string;
}
