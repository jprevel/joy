<?php

namespace App\Livewire;

use App\Models\ContentItem;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class ContentCalendar extends Component
{
    public string $currentView = 'calendar';
    public Carbon $currentMonth;
    public Collection $contentItems;
    public array $calendarData = [];
    public ?Client $client = null;
    public string $currentRole = 'client'; // For testing: client, agency, admin

    public function mount($role = 'client')
    {
        $this->currentMonth = Carbon::now()->startOfMonth();
        $this->currentRole = $role;
        
        // For demo purposes, get the first client
        // In production, this would be determined by magic link token
        $this->client = Client::first();
        
        $this->loadContentItems();
        $this->buildCalendarData();
    }
    
    public function getCurrentUserRole()
    {
        // In demo mode, we simulate different user roles
        // This could be expanded to check actual authenticated user roles
        $demoUsers = [
            'client' => \App\Models\User::whereHas('roles', function($q) { 
                $q->where('name', 'client'); 
            })->first(),
            'agency' => \App\Models\User::whereHas('roles', function($q) { 
                $q->where('name', 'agency'); 
            })->first(),
            'admin' => \App\Models\User::whereHas('roles', function($q) { 
                $q->where('name', 'admin'); 
            })->first(),
        ];
        
        return $demoUsers[$this->currentRole] ?? null;
    }
    
    public function hasPermission($permission)
    {
        $user = $this->getCurrentUserRole();
        return $user ? $user->can($permission) : false;
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
        $startDate = $this->currentMonth->copy()->startOfWeek();
        $endDate = $this->currentMonth->copy()->endOfMonth()->endOfWeek();
        
        $this->calendarData = [];
        
        // Build 6 weeks of calendar data
        for ($week = 0; $week < 6; $week++) {
            $this->calendarData[$week] = [];
            
            for ($day = 0; $day < 7; $day++) {
                $currentDate = $startDate->copy()->addDays($week * 7 + $day);
                
                $dayContentItems = $this->contentItems->filter(function ($contentItem) use ($currentDate) {
                    return $contentItem->scheduled_at && $contentItem->scheduled_at->isSameDay($currentDate);
                });
                
                $this->calendarData[$week][$day] = [
                    'date' => $currentDate,
                    'isCurrentMonth' => $currentDate->month === $this->currentMonth->month,
                    'isToday' => $currentDate->isToday(),
                    'contentItems' => $dayContentItems->values()->all()
                ];
            }
        }
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