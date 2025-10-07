<?php

namespace Tests\Unit\Services;

use App\Services\CalendarStatsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CalendarStatsServiceTest extends TestCase
{
    private CalendarStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalendarStatsService();
    }

    /** @test */
    public function it_calculates_statistics_for_content_items()
    {
        $items = collect([
            ['status' => 'draft', 'platform' => 'facebook', 'scheduled_at' => '2025-01-15'],
            ['status' => 'approved', 'platform' => 'instagram', 'scheduled_at' => '2025-01-16'],
            ['status' => 'scheduled', 'platform' => 'facebook', 'scheduled_at' => '2025-01-20'],
        ]);

        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-31');

        $stats = $this->service->calculateStats($items, $startDate, $endDate);

        $this->assertArrayHasKey('total_items', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertArrayHasKey('by_platform', $stats);
        $this->assertArrayHasKey('by_week', $stats);
        $this->assertArrayHasKey('completion_rate', $stats);
        $this->assertArrayHasKey('busiest_day', $stats);
        $this->assertEquals(3, $stats['total_items']);
    }

    /** @test */
    public function it_groups_content_items_by_week()
    {
        $items = collect([
            ['scheduled_at' => '2025-01-06'], // Week 1
            ['scheduled_at' => '2025-01-07'], // Week 1
            ['scheduled_at' => '2025-01-13'], // Week 2
        ]);

        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-31');

        $weeks = $this->service->groupByWeek($items, $startDate, $endDate);

        $this->assertIsArray($weeks);
        $this->assertArrayHasKey('week_1', $weeks);
        $this->assertArrayHasKey('start_date', $weeks['week_1']);
        $this->assertArrayHasKey('end_date', $weeks['week_1']);
        $this->assertArrayHasKey('item_count', $weeks['week_1']);
    }

    /** @test */
    public function it_handles_empty_collection_when_grouping_by_week()
    {
        $items = collect([]);
        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-31');

        $weeks = $this->service->groupByWeek($items, $startDate, $endDate);

        $this->assertIsArray($weeks);
        foreach ($weeks as $week) {
            $this->assertEquals(0, $week['item_count']);
        }
    }

    /** @test */
    public function it_calculates_completion_rate()
    {
        $items = collect([
            ['status' => 'approved'],
            ['status' => 'scheduled'],
            ['status' => 'draft'],
            ['status' => 'draft'],
        ]);

        $rate = $this->service->calculateCompletionRate($items);

        $this->assertEquals(50.0, $rate);
    }

    /** @test */
    public function it_returns_zero_completion_rate_for_empty_collection()
    {
        $items = collect([]);

        $rate = $this->service->calculateCompletionRate($items);

        $this->assertEquals(0, $rate);
    }

    /** @test */
    public function it_identifies_busiest_day()
    {
        $items = collect([
            ['scheduled_at' => '2025-01-15'],
            ['scheduled_at' => '2025-01-15'],
            ['scheduled_at' => '2025-01-15'],
            ['scheduled_at' => '2025-01-16'],
        ]);

        $busiest = $this->service->getBusiestDay($items);

        $this->assertIsArray($busiest);
        $this->assertArrayHasKey('date', $busiest);
        $this->assertArrayHasKey('item_count', $busiest);
        $this->assertArrayHasKey('day_name', $busiest);
        $this->assertEquals('2025-01-15', $busiest['date']);
        $this->assertEquals(3, $busiest['item_count']);
    }

    /** @test */
    public function it_returns_null_for_busiest_day_when_collection_empty()
    {
        $items = collect([]);

        $busiest = $this->service->getBusiestDay($items);

        $this->assertNull($busiest);
    }

    /** @test */
    public function it_groups_content_by_date_for_grid_view()
    {
        $items = collect([
            ['scheduled_at' => '2025-01-15'],
            ['scheduled_at' => '2025-01-15'],
            ['scheduled_at' => '2025-01-16'],
        ]);

        $startDate = Carbon::parse('2025-01-15');
        $endDate = Carbon::parse('2025-01-16');

        $grid = $this->service->groupContentByDate($items, $startDate, $endDate);

        $this->assertIsArray($grid);
        $this->assertCount(2, $grid);
        $this->assertEquals('2025-01-15', $grid[0]['date']);
        $this->assertEquals(2, $grid[0]['item_count']);
        $this->assertArrayHasKey('day_name', $grid[0]);
        $this->assertArrayHasKey('items', $grid[0]);
    }

    /** @test */
    public function it_includes_empty_dates_in_grid_view()
    {
        $items = collect([
            ['scheduled_at' => '2025-01-15'],
        ]);

        $startDate = Carbon::parse('2025-01-15');
        $endDate = Carbon::parse('2025-01-17');

        $grid = $this->service->groupContentByDate($items, $startDate, $endDate);

        $this->assertCount(3, $grid);
        $this->assertEquals(1, $grid[0]['item_count']);
        $this->assertEquals(0, $grid[1]['item_count']);
        $this->assertEquals(0, $grid[2]['item_count']);
    }

    /** @test */
    public function it_provides_calendar_metadata()
    {
        $date = Carbon::parse('2025-01-15');

        $info = $this->service->getCalendarInfo($date);

        $this->assertArrayHasKey('month_name', $info);
        $this->assertArrayHasKey('year', $info);
        $this->assertArrayHasKey('days_in_month', $info);
        $this->assertArrayHasKey('first_day_of_week', $info);
        $this->assertArrayHasKey('weeks_in_month', $info);
        $this->assertEquals('January', $info['month_name']);
        $this->assertEquals(2025, $info['year']);
        $this->assertEquals(31, $info['days_in_month']);
    }

    /** @test */
    public function it_marks_weekends_in_grid_view()
    {
        $items = collect([]);

        // Jan 18, 2025 is a Saturday, Jan 19 is Sunday
        $startDate = Carbon::parse('2025-01-18');
        $endDate = Carbon::parse('2025-01-20');

        $grid = $this->service->groupContentByDate($items, $startDate, $endDate);

        $this->assertTrue($grid[0]['is_weekend']); // Saturday
        $this->assertTrue($grid[1]['is_weekend']); // Sunday
        $this->assertFalse($grid[2]['is_weekend']); // Monday
    }

    /** @test */
    public function it_marks_today_in_grid_view()
    {
        $items = collect([]);
        $today = Carbon::now();
        $startDate = $today->copy()->subDay();
        $endDate = $today->copy()->addDay();

        $grid = $this->service->groupContentByDate($items, $startDate, $endDate);

        // Middle item should be today
        $this->assertFalse($grid[0]['is_today']);
        $this->assertTrue($grid[1]['is_today']);
        $this->assertFalse($grid[2]['is_today']);
    }
}
