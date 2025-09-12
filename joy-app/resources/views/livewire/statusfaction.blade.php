<div>
<!doctype html>
<html lang="en" class="h-full antialiased">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Statusfaction - Joy</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
  
  <div class="min-h-screen bg-neutral-50 dark:bg-neutral-900">
    <!-- Header -->
    <header class="bg-white dark:bg-neutral-800 shadow-sm border-b border-neutral-200 dark:border-neutral-700">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <img src="{{ asset('MM_logo_200px.png') }}" alt="MajorMajor" class="h-8 w-auto">
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Statusfaction</h1>
          </div>
          <div class="flex items-center gap-4">
            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ auth()->user()->name }}</span>
            <a href="{{ route('calendar.role', $currentRole) }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
              ← Back to Calendar
            </a>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      
      @if (session('status'))
        <div class="mb-6 rounded-md bg-green-50 p-4 dark:bg-green-900/30">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L7.53 10.53a.75.75 0 00-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('status') }}</p>
            </div>
          </div>
        </div>
      @endif

      @if (!$showForm)
        <!-- Client List -->
        <div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
            <h2 class="text-lg font-medium text-neutral-900 dark:text-white">Select a Client for Status Update</h2>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Choose a client from your teams to provide a weekly status update.</p>
          </div>
          
          @if ($clients->count() > 0)
            <ul role="list" class="divide-y divide-neutral-200 dark:divide-neutral-700">
              @foreach ($clients as $client)
                <li>
                  <button 
                    wire:click="selectClient({{ $client->id }})"
                    class="w-full px-6 py-4 flex items-center justify-between hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-colors text-left"
                  >
                    <div class="flex-1">
                      <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-neutral-900 dark:text-white">{{ $client->name }}</p>
                        @if ($client->statusUpdates->count() > 0)
                          <p class="text-xs text-neutral-500 dark:text-neutral-400">
                            Last updated: {{ $client->statusUpdates->first()->status_date->format('M j, Y') }}
                          </p>
                        @else
                          <p class="text-xs text-neutral-500 dark:text-neutral-400">No updates yet</p>
                        @endif
                      </div>
                      @if ($client->description)
                        <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">{{ $client->description }}</p>
                      @endif
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
      @else
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
            <!-- Status Notes (WYSIWYG) -->
            <div>
              <label for="status_notes" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                Status Notes
              </label>
              <div class="mt-1">
                <textarea 
                  wire:model="status_notes"
                  id="status_notes"
                  rows="8"
                  class="block w-full rounded-md border-neutral-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  placeholder="Provide detailed status notes about the client's projects, deliverables, and any important updates..."
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
      @endif
    </main>
  </div>
  
  @livewireScripts
</body>
</html>
</div>