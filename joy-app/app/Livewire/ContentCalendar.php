<?php

namespace App\Livewire;

use App\Models\ContentItem;
use App\Models\Client;
use App\Traits\HasRoleManagement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class ContentCalendar extends Component
{
    use HasRoleManagement;
    
    public string $currentView = 'calendar';
    public Carbon $currentMonth;
    public Collection $contentItems;
    public array $calendarData = [];
    public ?Client $client = null;
    public string $currentRole = 'client'; // For testing: client, agency, admin
    public Collection $accessibleClients;
    public ?int $selectedClientId = null;

    public function mount($role = 'client', $clientId = null)
    {
        $this->currentMonth = Carbon::now()->startOfMonth();
        $this->currentRole = $role;
        
        // Load accessible clients based on role
        if ($role === 'agency' || $role === 'admin') {
            $currentUser = $this->getCurrentUserRole();
            $this->accessibleClients = $currentUser ? $currentUser->accessibleClients()->get() : collect();
            
            // If a specific client ID is provided, use that; otherwise use the first client
            if ($clientId) {
                $this->client = $this->accessibleClients->firstWhere('id', $clientId);
                $this->selectedClientId = $clientId;
            } else {
                $this->client = $this->accessibleClients->first();
                $this->selectedClientId = $this->client?->id;
            }
        } else {
            // For client role, show only their own workspace
            $this->accessibleClients = collect([Client::first()]);
            $this->client = Client::first();
            $this->selectedClientId = $this->client?->id;
        }
        
        $this->loadContentItems();
        $this->buildCalendarData();
    }

    public function loadContentItems()
    {
        if ($this->client) {
            $this->contentItems = $this->client->contentItems()->get();
        } else {
            $this->contentItems = collect();
        }
    }

    public function buildCalendarData()
    {
        $dateRange = $this->getCalendarDateRange();
        $this->calendarData = $this->generateWeekRows($dateRange['start']);
    }

    private function getCalendarDateRange(): array
    {
        return [
            'start' => $this->currentMonth->copy()->startOfWeek(),
            'end' => $this->currentMonth->copy()->endOfMonth()->endOfWeek(),
        ];
    }

    private function generateWeekRows(Carbon $startDate): array
    {
        $calendarData = [];
        
        // Build 6 weeks of calendar data
        for ($week = 0; $week < 6; $week++) {
            $calendarData[$week] = $this->generateWeekDays($startDate, $week);
        }
        
        return $calendarData;
    }

    private function generateWeekDays(Carbon $startDate, int $week): array
    {
        $weekData = [];
        
        for ($day = 0; $day < 7; $day++) {
            $currentDate = $startDate->copy()->addDays($week * 7 + $day);
            $weekData[$day] = $this->generateDayData($currentDate);
        }
        
        return $weekData;
    }

    private function generateDayData(Carbon $date): array
    {
        return [
            'date' => $date,
            'isCurrentMonth' => $this->isDateInCurrentMonth($date),
            'isToday' => $date->isToday(),
            'contentItems' => $this->getContentItemsForDate($date)
        ];
    }

    private function isDateInCurrentMonth(Carbon $date): bool
    {
        return $date->month === $this->currentMonth->month;
    }

    private function getContentItemsForDate(Carbon $date): array
    {
        return $this->contentItems
            ->filter(fn($item) => $item->scheduled_at && $item->scheduled_at->isSameDay($date))
            ->values()
            ->all();
    }

    public function switchView(string $view)
    {
        $this->currentView = $view;
    }

    public function previousMonth()
    {
        $this->currentMonth = $this->currentMonth->copy()->subMonth();
        $this->buildCalendarData();
    }

    public function nextMonth()
    {
        $this->currentMonth = $this->currentMonth->copy()->addMonth();
        $this->buildCalendarData();
    }

    public function goToToday()
    {
        $this->currentMonth = Carbon::now()->startOfMonth();
        $this->buildCalendarData();
    }


    public function render()
    {
        return view('livewire.content-calendar')
            ->layout('components.layout');
    }
}