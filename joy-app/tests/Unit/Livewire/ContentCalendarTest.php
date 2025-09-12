<?php

namespace Tests\Unit\Livewire;

use Tests\TestCase;
use App\Livewire\ContentCalendar;
use App\Models\Client;
use App\Models\ContentItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

class ContentCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles for testing
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'agency']);
        Role::create(['name' => 'client']);
    }

    /** @test */
    public function it_mounts_with_default_values()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        // Act
        $component = Livewire::test(ContentCalendar::class);
        
        // Assert
        $component->assertSet('currentView', 'month');
        $component->assertSet('currentRole', 'agency');
        $this->assertInstanceOf(Carbon::class, $component->get('currentMonth'));
    }

    /** @test */
    public function it_mounts_with_specified_role()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        // Act
        $component = Livewire::test(ContentCalendar::class, ['role' => 'admin']);
        
        // Assert
        $component->assertSet('currentRole', 'admin');
    }

    /** @test */
    public function it_loads_content_items_for_selected_client()
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $contentItems = ContentItem::factory()->count(3)->create(['client_id' => $client->id]);
        Auth::login($user);
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('client', $client)
            ->call('loadContentItems');
        
        // Assert
        $this->assertCount(3, $component->get('contentItems'));
    }

    /** @test */
    public function it_loads_empty_collection_when_no_client_selected()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('client', null)
            ->call('loadContentItems');
        
        // Assert
        $this->assertTrue($component->get('contentItems')->isEmpty());
    }

    /** @test */
    public function it_builds_calendar_data_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->create();
        
        // Create content items for specific dates
        $testDate = Carbon::create(2024, 3, 15); // March 15, 2024
        ContentItem::factory()->create([
            'client_id' => $client->id,
            'scheduled_at' => $testDate,
            'title' => 'Test Content'
        ]);
        
        Auth::login($user);
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('client', $client)
            ->set('currentMonth', Carbon::create(2024, 3, 1))
            ->call('loadContentItems')
            ->call('buildCalendarData');
        
        // Assert
        $calendarData = $component->get('calendarData');
        $this->assertIsArray($calendarData);
        $this->assertCount(6, $calendarData); // 6 weeks
        
        // Find the week and day containing our test date
        $foundContent = false;
        foreach ($calendarData as $week) {
            foreach ($week as $day) {
                if ($day['date']->isSameDay($testDate)) {
                    $this->assertCount(1, $day['contentItems']);
                    $this->assertEquals('Test Content', $day['contentItems'][0]['title']);
                    $foundContent = true;
                    break 2;
                }
            }
        }
        
        $this->assertTrue($foundContent, 'Test content not found in calendar data');
    }

    /** @test */
    public function it_identifies_current_month_dates_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        $marchFirst = Carbon::create(2024, 3, 1);
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('currentMonth', $marchFirst)
            ->call('buildCalendarData');
        
        // Assert
        $calendarData = $component->get('calendarData');
        
        // Check that March dates are marked as current month
        foreach ($calendarData as $week) {
            foreach ($week as $day) {
                if ($day['date']->month === 3) {
                    $this->assertTrue($day['isCurrentMonth'], 
                        "March date should be marked as current month: " . $day['date']->format('Y-m-d'));
                } elseif ($day['date']->month !== 3) {
                    $this->assertFalse($day['isCurrentMonth'], 
                        "Non-March date should not be marked as current month: " . $day['date']->format('Y-m-d'));
                }
            }
        }
    }

    /** @test */
    public function it_identifies_today_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        $today = Carbon::today();
        $currentMonth = $today->copy()->startOfMonth();
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('currentMonth', $currentMonth)
            ->call('buildCalendarData');
        
        // Assert
        $calendarData = $component->get('calendarData');
        
        $foundToday = false;
        foreach ($calendarData as $week) {
            foreach ($week as $day) {
                if ($day['date']->isToday()) {
                    $this->assertTrue($day['isToday']);
                    $foundToday = true;
                } else {
                    $this->assertFalse($day['isToday']);
                }
            }
        }
        
        $this->assertTrue($foundToday, 'Today should be found in calendar data');
    }

    /** @test */
    public function it_switches_views()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        // Act & Assert
        Livewire::test(ContentCalendar::class)
            ->assertSet('currentView', 'month')
            ->call('switchView', 'timeline')
            ->assertSet('currentView', 'timeline')
            ->call('switchView', 'month')
            ->assertSet('currentView', 'month');
    }

    /** @test */
    public function it_navigates_to_previous_month()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        $initialMonth = Carbon::create(2024, 3, 1);
        $expectedPreviousMonth = Carbon::create(2024, 2, 1);
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('currentMonth', $initialMonth)
            ->call('previousMonth');
        
        // Assert
        $this->assertTrue($component->get('currentMonth')->isSameMonth($expectedPreviousMonth));
    }

    /** @test */
    public function it_navigates_to_next_month()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        $initialMonth = Carbon::create(2024, 3, 1);
        $expectedNextMonth = Carbon::create(2024, 4, 1);
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('currentMonth', $initialMonth)
            ->call('nextMonth');
        
        // Assert
        $this->assertTrue($component->get('currentMonth')->isSameMonth($expectedNextMonth));
    }

    /** @test */
    public function it_goes_to_today()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        $pastMonth = Carbon::create(2023, 6, 1);
        $expectedCurrentMonth = Carbon::now()->startOfMonth();
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('currentMonth', $pastMonth)
            ->call('goToToday');
        
        // Assert
        $this->assertTrue($component->get('currentMonth')->isSameMonth($expectedCurrentMonth));
    }

    /** @test */
    public function it_rebuilds_calendar_data_when_month_changes()
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->create();
        
        // Create content items in different months
        ContentItem::factory()->create([
            'client_id' => $client->id,
            'scheduled_at' => Carbon::create(2024, 3, 15),
            'title' => 'March Content'
        ]);
        
        ContentItem::factory()->create([
            'client_id' => $client->id,
            'scheduled_at' => Carbon::create(2024, 4, 15),
            'title' => 'April Content'
        ]);
        
        Auth::login($user);
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('client', $client)
            ->set('currentMonth', Carbon::create(2024, 3, 1))
            ->call('loadContentItems');
        
        // Check March content is visible
        $marchCalendarData = $component->get('calendarData');
        $marchContentFound = $this->findContentInCalendarData($marchCalendarData, 'March Content');
        $this->assertTrue($marchContentFound);
        
        // Navigate to next month
        $component->call('nextMonth');
        
        // Check April content is visible
        $aprilCalendarData = $component->get('calendarData');
        $aprilContentFound = $this->findContentInCalendarData($aprilCalendarData, 'April Content');
        $this->assertTrue($aprilContentFound);
    }

    /** @test */
    public function it_filters_content_items_by_date_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->create();
        
        $targetDate = Carbon::create(2024, 3, 15);
        $differentDate = Carbon::create(2024, 3, 20);
        
        ContentItem::factory()->create([
            'client_id' => $client->id,
            'scheduled_at' => $targetDate,
            'title' => 'Target Date Content'
        ]);
        
        ContentItem::factory()->create([
            'client_id' => $client->id,
            'scheduled_at' => $differentDate,
            'title' => 'Different Date Content'
        ]);
        
        Auth::login($user);
        
        // Act
        $component = Livewire::test(ContentCalendar::class)
            ->set('client', $client)
            ->set('currentMonth', Carbon::create(2024, 3, 1))
            ->call('loadContentItems')
            ->call('buildCalendarData');
        
        // Assert
        $calendarData = $component->get('calendarData');
        
        // Find the target date and verify only correct content is present
        foreach ($calendarData as $week) {
            foreach ($week as $day) {
                if ($day['date']->isSameDay($targetDate)) {
                    $this->assertCount(1, $day['contentItems']);
                    $this->assertEquals('Target Date Content', $day['contentItems'][0]['title']);
                } elseif ($day['date']->isSameDay($differentDate)) {
                    $this->assertCount(1, $day['contentItems']);
                    $this->assertEquals('Different Date Content', $day['contentItems'][0]['title']);
                } else {
                    $this->assertCount(0, $day['contentItems']);
                }
            }
        }
    }

    /**
     * Helper method to find content in calendar data
     */
    private function findContentInCalendarData(array $calendarData, string $title): bool
    {
        foreach ($calendarData as $week) {
            foreach ($week as $day) {
                foreach ($day['contentItems'] as $item) {
                    if ($item['title'] === $title) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}