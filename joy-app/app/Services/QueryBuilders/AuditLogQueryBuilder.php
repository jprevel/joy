<?php

namespace App\Services\QueryBuilders;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogQueryBuilder
{
    private Builder $query;

    public function __construct()
    {
        $this->query = AuditLog::with(['user', 'client'])->orderBy('created_at', 'desc');
    }

    /**
     * Apply filters from request.
     */
    public function applyFilters(Request $request): self
    {
        if ($request->has('client_id')) {
            $this->forClient($request->input('client_id'));
        }

        if ($request->has('user_id')) {
            $this->forUser($request->input('user_id'));
        }

        if ($request->has('event')) {
            $this->forEvent($request->input('event'));
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $this->byDateRange($request->input('from_date'), $request->input('to_date'));
        }

        return $this;
    }

    /**
     * Filter by client.
     */
    public function forClient(int $clientId): self
    {
        $this->query->forClient($clientId);
        return $this;
    }

    /**
     * Filter by user.
     */
    public function forUser(int $userId): self
    {
        $this->query->forUser($userId);
        return $this;
    }

    /**
     * Filter by event.
     */
    public function forEvent(string $event): self
    {
        $this->query->forEvent($event);
        return $this;
    }

    /**
     * Filter by date range.
     */
    public function byDateRange(string $fromDate, string $toDate): self
    {
        $this->query->byDateRange($fromDate, $toDate);
        return $this;
    }

    /**
     * Set limit.
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Get the query builder instance.
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Execute the query and get results.
     */
    public function get()
    {
        return $this->query->get();
    }
}
