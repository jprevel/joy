<?php

namespace App\Http\Controllers;
use App\Http\Traits\ApiResponse;

use App\Services\CalendarStatsService;
use App\Services\ContentItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ContentItemService $contentItemService,
        private CalendarStatsService $calendarStatsService
    ) {}

    /**
     * Get calendar data for a specific month.
     */
    public function month(Request $request): JsonResponse
    {
        // User and client resolved by middleware
        $client = $request->get('resolved_client');

        try {
            $request->validate([
                'month' => 'sometimes|date_format:Y-m',
                'view' => 'sometimes|in:grid,timeline'
            ]);

            $month = $request->input('month', now()->format('Y-m'));
            $view = $request->input('view', 'grid');

            // Parse month and create date range
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

            // Get content items for the month
            $filters = [
                'from_date' => $startDate->toDateString(),
                'to_date' => $endDate->toDateString(),
                'view' => $view
            ];

            $contentItems = $this->contentItemService->getForClient($client, $filters);

            // Group content by date for grid view
            $calendarData = $view === 'grid'
                ? $this->calendarStatsService->groupContentByDate($contentItems, $startDate, $endDate)
                : $contentItems;

            return $this->success([
                'data' => $calendarData,
                'meta' => [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'month' => $month,
                    'view' => $view,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'total_items' => $contentItems->count(),
                    'calendar_info' => $this->calendarStatsService->getCalendarInfo($startDate)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load calendar data', $e);
        }
    }

    /**
     * Get calendar data for a specific date range.
     */
    public function range(Request $request): JsonResponse
    {
        // User and client resolved by middleware
        $client = $request->get('resolved_client');

        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'view' => 'sometimes|in:grid,timeline'
            ]);

            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
            $view = $request->input('view', 'timeline');

            // Limit range to prevent performance issues
            if ($startDate->diffInDays($endDate) > 365) {
                return $this->validationError(['date_range' => 'Date range too large (max 365 days)']);
            }

            // Get content items for the range
            $filters = [
                'from_date' => $startDate->toDateString(),
                'to_date' => $endDate->toDateString(),
                'view' => $view
            ];

            $contentItems = $this->contentItemService->getForClient($client, $filters);

            return $this->success([
                'data' => $contentItems,
                'meta' => [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'view' => $view,
                    'total_items' => $contentItems->count(),
                    'days_in_range' => $startDate->diffInDays($endDate) + 1
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load calendar data', $e);
        }
    }

    /**
     * Get today's content items.
     */
    public function today(Request $request): JsonResponse
    {
        // User and client resolved by middleware
        $client = $request->get('resolved_client');

        try {
            $today = now()->toDateString();

            // Get content items for today
            $filters = [
                'from_date' => $today,
                'to_date' => $today,
                'view' => 'timeline'
            ];

            $contentItems = $this->contentItemService->getForClient($client, $filters);

            return $this->success([
                'data' => $contentItems,
                'meta' => [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'date' => $today,
                    'total_items' => $contentItems->count()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load today\'s content', $e);
        }
    }

    /**
     * Get upcoming content items.
     */
    public function upcoming(Request $request): JsonResponse
    {
        // User and client resolved by middleware
        $client = $request->get('resolved_client');

        try {
            $request->validate([
                'days' => 'sometimes|integer|min:1|max:30'
            ]);

            $days = $request->input('days', 7);

            $startDate = now()->toDateString();
            $endDate = now()->addDays($days)->toDateString();

            // Get upcoming content items
            $filters = [
                'from_date' => $startDate,
                'to_date' => $endDate,
                'view' => 'timeline'
            ];

            $contentItems = $this->contentItemService->getForClient($client, $filters);

            return $this->success([
                'data' => $contentItems,
                'meta' => [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $days,
                    'total_items' => $contentItems->count()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load upcoming content', $e);
        }
    }

    /**
     * Get calendar statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        // User and client resolved by middleware
        $client = $request->get('resolved_client');

        try {
            $request->validate([
                'month' => 'sometimes|date_format:Y-m'
            ]);

            $month = $request->input('month', now()->format('Y-m'));

            // Parse month
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

            // Get content items for stats
            $filters = [
                'from_date' => $startDate->toDateString(),
                'to_date' => $endDate->toDateString()
            ];

            $contentItems = $this->contentItemService->getForClient($client, $filters);

            // Calculate statistics
            $stats = $this->calendarStatsService->calculateStats($contentItems, $startDate, $endDate);

            return $this->success([
                'data' => $stats,
                'meta' => [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'month' => $month,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load calendar statistics', $e);
        }
    }
}