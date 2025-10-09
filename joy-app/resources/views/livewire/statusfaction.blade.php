<div>
<!doctype html>
<html lang="en" class="h-full antialiased">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Statusfaction - Joy</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles

  <style>
    .slider {
      appearance: none;
      width: 100%;
      height: 8px;
      border-radius: 4px;
      background: #e5e7eb;
      outline: none;
      opacity: 0.7;
      transition: opacity 0.2s;
    }

    .slider:hover {
      opacity: 1;
    }

    .slider::-webkit-slider-thumb {
      appearance: none;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #3b82f6;
      cursor: pointer;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .slider::-moz-range-thumb {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #3b82f6;
      cursor: pointer;
      border: none;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .slider-value {
      font-weight: 600;
      font-size: 1.125rem;
    }

    .satisfaction-1 { color: #dc2626; }
    .satisfaction-2 { color: #ea580c; }
    .satisfaction-3 { color: #d97706; }
    .satisfaction-4 { color: #ca8a04; }
    .satisfaction-5 { color: #eab308; }
    .satisfaction-6 { color: #84cc16; }
    .satisfaction-7 { color: #65a30d; }
    .satisfaction-8 { color: #16a34a; }
    .satisfaction-9 { color: #059669; }
    .satisfaction-10 { color: #047857; }
  </style>
</head>
<body class="h-full bg-neutral-50 text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100">

  <!-- Sidebar Layout -->
  <div x-data="{ sidebarOpen: true }" class="flex h-screen bg-neutral-50 dark:bg-neutral-900">
    <!-- Sidebar -->
    <div :class="sidebarOpen ? 'w-64' : 'w-16'" class="bg-white dark:bg-neutral-800 border-r border-neutral-200 dark:border-neutral-700 transition-all duration-300 flex flex-col">
      <!-- Sidebar Header -->
      <div class="p-4 border-b border-neutral-200 dark:border-neutral-700">
        <div class="flex items-center justify-between">
          <div x-show="sidebarOpen" x-transition class="flex items-center gap-3">
            <img src="{{ asset('MM_logo_200px.png') }}" alt="MajorMajor Logo" class="h-8 w-auto">
            <span class="font-semibold text-neutral-900 dark:text-neutral-100">Joy</span>
          </div>
          <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-2">
        <!-- Calendar Link -->
        <a href="{{ route('calendar.role', $currentRole) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition group">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <span x-show="sidebarOpen" x-transition class="font-medium">Calendar</span>
        </a>

        <!-- Statusfaction (active) -->
        <a href="{{ route('statusfaction.role', $currentRole) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 transition group">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
          <span x-show="sidebarOpen" x-transition class="font-medium">Statusfaction</span>
        </a>

        <!-- Admin Panel -->
        @if(auth()->user()->hasRole('admin'))
          <a href="/admin" class="flex items-center gap-3 px-3 py-2 rounded-lg text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition group">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span x-show="sidebarOpen" x-transition class="font-medium">Admin Panel</span>
          </a>
        @endif
      </nav>

      <!-- User Info -->
      <div class="px-4 py-2 border-t border-neutral-200 dark:border-neutral-700">
        <div x-show="sidebarOpen" x-transition class="text-sm">
          <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ auth()->user()->name }}</div>
          @if(auth()->user()->teams->count() > 0)
            <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">{{ auth()->user()->teams->pluck('name')->join(', ') }}</div>
          @endif
        </div>
      </div>

      <!-- Logout Button -->
      <div class="mt-auto p-4 border-t border-neutral-200 dark:border-neutral-700">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="flex items-center gap-3 px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition group w-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span x-show="sidebarOpen" x-transition class="font-medium text-left">Logout</span>
          </button>
        </form>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-hidden">
      <div class="h-full overflow-y-auto">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
          <!-- Page Header -->
          <div class="mb-6">
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">
              Statusfaction
              <span class="text-sm font-normal text-neutral-500 dark:text-neutral-400">
                ({{ ucfirst($currentRole) }} View)
              </span>
            </h1>
          </div>

      <!-- Debug Info -->
      <div class="mb-4 p-2 bg-blue-100 dark:bg-blue-900 text-xs">
        <strong>Debug:</strong>
        showForm: {{ $showForm ? 'true' : 'false' }} |
        showDetail: {{ $showDetail ? 'true' : 'false' }} |
        selectedClient: {{ $selectedClient ? $selectedClient->id : 'null' }} |
        selectedStatus: {{ $selectedStatus ? $selectedStatus->id : 'null' }}
      </div>

      <!-- Success/Error Messages -->
      @if (session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4 dark:bg-green-900/30">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L7.53 10.53a.75.75 0 00-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
          </div>
        </div>
      @endif

      @if (session('error'))
        <div class="mb-6 rounded-md bg-red-50 p-4 dark:bg-red-900/30">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
          </div>
        </div>
      @endif

      @if (!$showForm && !$showDetail)
        <!-- Client List -->
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
            <h2 class="text-lg font-medium text-neutral-900 dark:text-white">
              Giving Statusfaction for: {{ \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::SUNDAY)->format('M j') }} - {{ \Carbon\Carbon::now()->endOfWeek(\Carbon\Carbon::SATURDAY)->format('M j, Y') }}
            </h2>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Choose a client to provide or view weekly status.</p>
          </div>

          @if ($this->clients->count() > 0)
            <ul role="list" class="divide-y divide-neutral-200 dark:divide-neutral-700">
              @foreach ($this->clients as $client)
                <li>
                  <button
                    wire:click="selectClient({{ $client->id }})"
                    class="w-full px-6 py-4 flex items-center justify-between hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-colors text-left"
                  >
                    <div class="flex-1">
                      <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                          <p class="text-sm font-medium text-neutral-900 dark:text-white">{{ $client->name }}</p>

                          <!-- Status Badge -->
                          @if ($client->status_state === 'Needs Status')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                              Needs Status
                            </span>
                          @elseif ($client->status_state === 'Pending Approval')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                              Pending Approval
                            </span>
                          @elseif ($client->status_state === 'Status Approved')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                              Status Approved
                            </span>
                          @endif
                        </div>
                      </div>
                    </div>
                    <div class="ml-4">
                      <svg class="h-5 w-5 text-neutral-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </button>
                </li>
              @endforeach
            </ul>
          @else
            <div class="px-6 py-12 text-center">
              <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-neutral-900 dark:text-white">No clients found</h3>
              <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">You don't have access to any clients yet.</p>
            </div>
          @endif
        </div>
      @endif

      @if ($showForm)
        <!-- Status Update Form -->
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-medium text-neutral-900 dark:text-white">Status Update for {{ $selectedClient->name }}</h2>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Provide your weekly status update for this client.</p>
              </div>
              <button
                wire:click="backToList"
                class="inline-flex items-center px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-white dark:bg-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-600"
              >
                ← Back to List
              </button>
            </div>
          </div>

          <form wire:submit.prevent="saveStatus" class="p-6 space-y-6">
            <!-- Status Notes -->
            <div>
              <label for="status_notes" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                Status Notes <span class="text-red-500">*</span>
              </label>
              <div class="mt-1">
                <textarea
                  wire:model="status_notes"
                  id="status_notes"
                  rows="8"
                  class="block w-full rounded-md border-neutral-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  placeholder="Provide detailed status notes about the client's projects, deliverables, and any important updates..."
                  required
                ></textarea>
              </div>
              @error('status_notes')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            <!-- Client Satisfaction Slider -->
            <div>
              <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                Client Satisfaction
              </label>
              <div class="space-y-3">
                <input
                  type="range"
                  min="1"
                  max="10"
                  wire:model.live="client_satisfaction"
                  class="slider"
                >
                <div class="flex justify-between items-center">
                  <span class="text-xs text-neutral-500 dark:text-neutral-400">Very Dissatisfied</span>
                  <span class="slider-value satisfaction-{{ $client_satisfaction }}">{{ $client_satisfaction }}/10</span>
                  <span class="text-xs text-neutral-500 dark:text-neutral-400">Very Satisfied</span>
                </div>
              </div>
              @error('client_satisfaction')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            <!-- Team Health Slider -->
            <div>
              <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                Team Health
              </label>
              <div class="space-y-3">
                <input
                  type="range"
                  min="1"
                  max="10"
                  wire:model.live="team_health"
                  class="slider"
                >
                <div class="flex justify-between items-center">
                  <span class="text-xs text-neutral-500 dark:text-neutral-400">Poor Health</span>
                  <span class="slider-value satisfaction-{{ $team_health }}">{{ $team_health }}/10</span>
                  <span class="text-xs text-neutral-500 dark:text-neutral-400">Excellent Health</span>
                </div>
              </div>
              @error('team_health')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                wire:click="backToList"
                class="px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-white dark:bg-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-600"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Save Status Update
              </button>
            </div>
          </form>
        </div>

        <!-- 5-Week Trend Graph (Form View) -->
        @if ($selectedClient)
          <div class="mt-6 bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-neutral-900 dark:text-white mb-4">5-Week Trend</h3>
            <div class="bg-white dark:bg-neutral-700 p-4 rounded-lg" wire:ignore>
              <canvas id="trendChartForm" class="w-full" style="max-height: 400px; min-height: 300px;"></canvas>
            </div>
          </div>
        @endif
      @endif

      @if ($showDetail)
        <!-- Status Detail View -->
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-medium text-neutral-900 dark:text-white">Status Details for {{ $selectedClient->name }}</h2>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">View status trends and details.</p>
              </div>
              <button
                wire:click="backToList"
                class="inline-flex items-center px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-white dark:bg-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-600"
              >
                ← Back to List
              </button>
            </div>
          </div>

          <div class="p-6 space-y-6">
            @if ($selectedStatus)
              <!-- Current Status Info -->
              <div class="bg-neutral-50 dark:bg-neutral-700/50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Submitted By</p>
                    <p class="mt-1 text-sm text-neutral-900 dark:text-white">{{ $selectedStatus->user->name }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Submitted On</p>
                    <p class="mt-1 text-sm text-neutral-900 dark:text-white">{{ $selectedStatus->status_date->format('M j, Y g:i A') }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Status</p>
                    <p class="mt-1">
                      @if ($selectedStatus->approval_status === 'pending_approval')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                          Pending Approval
                        </span>
                      @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                          Approved
                        </span>
                      @endif
                    </p>
                  </div>
                </div>

                <div class="mt-4">
                  <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Status Notes</p>
                  <p class="mt-2 text-sm text-neutral-900 dark:text-white whitespace-pre-wrap">{{ $selectedStatus->status_notes }}</p>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Client Satisfaction</p>
                    <p class="mt-1 text-2xl font-bold satisfaction-{{ $selectedStatus->client_satisfaction }}">{{ $selectedStatus->client_satisfaction }}/10</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Team Health</p>
                    <p class="mt-1 text-2xl font-bold satisfaction-{{ $selectedStatus->team_health }}">{{ $selectedStatus->team_health }}/10</p>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex justify-end gap-3">
                <!-- Edit Button (for status owners) -->
                @if ($selectedStatus->approval_status === 'pending_approval' && $selectedStatus->user_id === auth()->id())
                  <button
                    wire:click="editStatus"
                    class="px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md text-sm font-medium text-neutral-700 dark:text-neutral-200 bg-white dark:bg-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    Edit Status
                  </button>
                @endif

                <!-- Approve Button (Admin only) -->
                @if ($selectedStatus->approval_status === 'pending_approval' && auth()->user()->hasRole('admin'))
                  <button
                    wire:click="approveStatus({{ $selectedStatus->id }})"
                    class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                  >
                    Approve Status
                  </button>
                @endif
              </div>
            @endif

            <!-- 5-Week Trend Graph -->
            <div>
              <h3 class="text-lg font-medium text-neutral-900 dark:text-white mb-4">5-Week Trend</h3>

              <!-- Debug info -->
              <div class="mb-2 p-2 bg-yellow-100 dark:bg-yellow-900 text-xs">
                <strong>Debug:</strong>
                Client ID: {{ $selectedClient->id }} |
                Labels: {{ count($this->graphData['labels'] ?? []) }} |
                Datasets: {{ count($this->graphData['datasets'] ?? []) }}
                @if(isset($this->graphData['datasets'][0]['data']))
                  | Data points: {{ count($this->graphData['datasets'][0]['data']) }}
                @endif
              </div>

              <div class="bg-white dark:bg-neutral-700 p-4 rounded-lg" wire:ignore>
                <canvas id="trendChart" class="w-full" style="max-height: 400px; min-height: 300px; border: 1px solid red;"></canvas>
              </div>

              <!-- Raw data dump -->
              <details class="mt-2">
                <summary class="text-xs cursor-pointer">View raw graph data</summary>
                <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-2 overflow-auto">{{ json_encode($this->graphData, JSON_PRETTY_PRINT) }}</pre>
              </details>
            </div>
          </div>
        </div>

      @endif
        </div>
      </div>
    </div>
  </div>

@livewireScripts

<script>
  document.addEventListener('livewire:init', () => {
    @if($showDetail && $selectedClient)
      const graphData = @js($this->graphData);
      console.log('=== DETAIL VIEW DEBUG ===');
      console.log('Graph data:', graphData);

      setTimeout(() => {
        const canvas = document.getElementById('trendChart');
        if (!canvas) {
          console.error('❌ Detail canvas not found');
          return;
        }
        console.log('✓ Canvas found');

        if (typeof Chart === 'undefined') {
          console.error('❌ Chart.js not loaded');
          return;
        }
        console.log('✓ Chart.js loaded');

        console.log('📊 Creating detail chart');
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: graphData.labels,
            datasets: graphData.datasets
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
              y: {
                min: 0,
                max: 10,
                ticks: { stepSize: 1 },
                title: { display: true, text: 'Rating (1-10)', font: { size: 14, weight: 'bold' } },
                grid: { display: true }
              },
              x: {
                title: { display: true, text: 'Week', font: { size: 14, weight: 'bold' } },
                grid: { display: true }
              }
            },
            plugins: {
              legend: {
                display: true,
                position: 'top',
                labels: { font: { size: 14, weight: 'bold' }, padding: 15, usePointStyle: true, pointStyle: 'line' }
              }
            }
          }
        });
        console.log('✓ Chart created');
      }, 300);
    @endif

    @if($showForm && $selectedClient)
      const formGraphData = @js($this->graphData);
      console.log('Form view graph data:', formGraphData);

      setTimeout(() => {
        const canvas = document.getElementById('trendChartForm');
        if (!canvas) {
          console.log('Form canvas not found');
          return;
        }

        if (typeof Chart === 'undefined') {
          console.error('Chart.js not loaded');
          return;
        }

        console.log('Creating form chart...');
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: formGraphData.labels,
            datasets: formGraphData.datasets
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
              y: {
                min: 0,
                max: 10,
                ticks: { stepSize: 1 },
                title: { display: true, text: 'Rating (1-10)', font: { size: 14, weight: 'bold' } },
                grid: { display: true }
              },
              x: {
                title: { display: true, text: 'Week', font: { size: 14, weight: 'bold' } },
                grid: { display: true }
              }
            },
            plugins: {
              legend: {
                display: true,
                position: 'top',
                labels: { font: { size: 14, weight: 'bold' }, padding: 15, usePointStyle: true, pointStyle: 'line' }
              }
            }
          }
        });
      }, 300);
    @endif
  });
</script>
</body>
</html>
