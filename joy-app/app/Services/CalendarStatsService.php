<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarStatsService
{
    /**
     * Calculate calendar statistics for content items.
     */
    public function calculateStats(Collection $contentItems, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_items' => $contentItems->count(),
            'by_status' => $contentItems->countBy('status'),
            'by_platform' => $contentItems->countBy('platform'),
            'by_week' => $this->groupByWeek($contentItems, $startDate, $endDate),
            'completion_rate' => $this->calculateCompletionRate($contentItems),
            'busiest_day' => $this->getBusiestDay($contentItems),
        ];
    }

    /**
     * Group content items by week.
     */
    public function groupByWeek(Collection $contentItems, Carbon $startDate, Carbon $endDate): array
    {
        $weeks = [];
        $current = $startDate->copy()->startOfWeek();
        $weekNumber = 1;

        while ($current <= $endDate) {
            $weekStart = $current->copy();
            $weekEnd = $current->copy()->endOfWeek();

            $weekItems = $contentItems->filter(function ($item) use ($weekStart, $weekEnd) {
                if (!isset($item['scheduled_at'])) {
                    return false;
                }
                $itemDate = Carbon::parse($item['scheduled_at']);
                return $itemDate->between($weekStart, $weekEnd);
            });

            $weeks["week_{$weekNumber}"] = [
                'start_date' => $weekStart->toDateString(),
                'end_date' => $weekEnd->toDateString(),
                'item_count' => $weekItems->count()
            ];

            $current->addWeek();
            $weekNumber++;
        }

        return $weeks;
    }

    /**
     * Calculate completion rate (approved + scheduled).
     */
    public function calculateCompletionRate(Collection $contentItems): float
    {
        if ($contentItems->isEmpty()) {
            return 0;
        }

        $completed = $contentItems->filter(function ($item) {
            return in_array($item['status'], ['approved', 'scheduled']);
        })->count();

        return round(($completed / $contentItems->count()) * 100, 2);
    }

    /**
     * Get the busiest day in the collection.
     */
    public function getBusiestDay(Collection $contentItems): ?array
    {
        if ($contentItems->isEmpty()) {
            return null;
        }

        $dayCount = $contentItems
            ->filter(fn($item) => isset($item['scheduled_at']))
            ->groupBy(fn($item) => Carbon::parse($item['scheduled_at'])->toDateString())
            ->map->count()
            ->sortDesc();

        if ($dayCount->isEmpty()) {
            return null;
        }

        $busiestDate = $dayCount->keys()->first();
        $itemCount = $dayCount->first();

        return [
            'date' => $busiestDate,
            'item_count' => $itemCount,
            'day_name' => Carbon::parse($busiestDate)->format('l')
        ];
    }

    /**
     * Group content by date for grid view.
     */
    public function groupContentByDate(Collection $contentItems, Carbon $startDate, Carbon $endDate): array
    {
        $calendarData = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateStr = $current->toDateString();
            $dayItems = $contentItems->filter(function ($item) use ($dateStr) {
                return isset($item['scheduled_at']) &&
                       Carbon::parse($item['scheduled_at'])->toDateString() === $dateStr;
            });

            $calendarData[] = [
                'date' => $dateStr,
                'day_of_week' => $current->dayOfWeek,
                'day_name' => $current->format('l'),
                'is_today' => $current->isToday(),
                'is_weekend' => $current->isWeekend(),
                'items' => $dayItems->values(),
                'item_count' => $dayItems->count()
            ];

            $current->addDay();
        }

        return $calendarData;
    }

    /**
     * Get calendar metadata.
     */
    public function getCalendarInfo(Carbon $startDate): array
    {
        return [
            'month_name' => $startDate->format('F'),
            'year' => $startDate->year,
            'days_in_month' => $startDate->daysInMonth,
            'first_day_of_week' => $startDate->dayOfWeek,
            'weeks_in_month' => ceil(($startDate->daysInMonth + $startDate->dayOfWeek) / 7)
        ];
    }
}
