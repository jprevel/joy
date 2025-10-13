<div class="p-6">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Audit Logs</h1>
        <p class="text-gray-600 mt-2">Monitor system activity and user actions</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <!-- Filter Toggle Button -->
        <div class="flex justify-between items-center mb-4">
            <button wire:click="toggleFilters"
                    class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span class="font-medium">{{ $filtersOpen ? 'Hide Filters' : 'Show Filters' }}</span>
                @if(!$filtersOpen && $this->activeFilterCount > 0)
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                        {{ $this->activeFilterCount }} active
                    </span>
                @endif
            </button>

            <div class="flex items-center space-x-2 text-sm text-gray-600">
                <span>Sort:</span>
                <button wire:click="$set('sortDirection', '{{ $sortDirection === 'desc' ? 'asc' : 'desc' }}')"
                        class="flex items-center space-x-1 text-blue-600 hover:text-blue-800">
                    <span>{{ $sortDirection === 'desc' ? 'Newest First' : 'Oldest First' }}</span>
                    <svg class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-0' : 'rotate-180' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Collapsible Filter Form -->
        @if($filtersOpen)
            <div class="border-t pt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text"
                               wire:model.live="search"
                               placeholder="Search logs..."
                               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Event</label>
                        <select wire:model.live="eventFilter"
                                class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Events</option>
                            @foreach($events as $event)
                                <option value="{{ $event }}">{{ ucfirst(str_replace('.', ' ', $event)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                        <select wire:model.live="userFilter"
                                class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                        <input type="date"
                               wire:model.live="dateFrom"
                               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                        <input type="date"
                               wire:model.live="dateTo"
                               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="mt-4">
                    <button wire:click="clearFilters"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                        Clear Filters
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-lg shadow">
        @if($auditLogs->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Timestamp
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Event
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Resource
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Changes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($auditLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>{{ $log->created_at->format('M j, Y') }}</div>
                                    <div class="text-xs text-gray-400">{{ $log->created_at->format('g:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $log->user->name ?? 'System' }}
                                    </div>
                                    @if($log->user)
                                        <div class="text-xs text-gray-500">{{ $log->user->email }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if(str_contains($log->event, 'created')) bg-green-100 text-green-800
                                        @elseif(str_contains($log->event, 'updated')) bg-blue-100 text-blue-800
                                        @elseif(str_contains($log->event, 'deleted')) bg-red-100 text-red-800
                                        @elseif(str_contains($log->event, 'login')) bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst(str_replace(['.', '_'], ' ', $log->event)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($log->auditable_type)
                                        <div>{{ class_basename($log->auditable_type) }}</div>
                                        <div class="text-xs text-gray-500">#{{ $log->auditable_id }}</div>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                    @if($log->new_values || $log->old_values)
                                        @php
                                            $changes = [];
                                            $allKeys = array_unique(array_merge(
                                                array_keys($log->new_values ?? []),
                                                array_keys($log->old_values ?? [])
                                            ));
                                            foreach ($allKeys as $key) {
                                                $oldVal = $log->old_values[$key] ?? null;
                                                $newVal = $log->new_values[$key] ?? null;
                                                if ($oldVal !== $newVal && is_scalar($newVal) && strlen((string)$newVal) < 50) {
                                                    $changes[] = [
                                                        'key' => $key,
                                                        'old' => $oldVal,
                                                        'new' => $newVal
                                                    ];
                                                }
                                            }
                                            $totalChanges = count($changes);
                                            $shouldTruncate = $totalChanges > 5;
                                        @endphp

                                        @if($shouldTruncate)
                                            <div x-data="{ expanded: false }" class="space-y-1">
                                                @foreach(array_slice($changes, 0, 5) as $change)
                                                    <div class="text-xs">
                                                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $change['key'])) }}:</span>
                                                        <span class="text-green-600">{{ $change['new'] }}</span>
                                                        @if($change['old'] !== null)
                                                            <span class="text-gray-400">(was: {{ $change['old'] }})</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                <template x-if="!expanded">
                                                    <button @click="expanded = true" class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-1">
                                                        Show {{ $totalChanges - 5 }} more changes
                                                    </button>
                                                </template>
                                                <template x-if="expanded">
                                                    <div class="space-y-1 mt-1">
                                                        @foreach(array_slice($changes, 5) as $change)
                                                            <div class="text-xs">
                                                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $change['key'])) }}:</span>
                                                                <span class="text-green-600">{{ $change['new'] }}</span>
                                                                @if($change['old'] !== null)
                                                                    <span class="text-gray-400">(was: {{ $change['old'] }})</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                        <button @click="expanded = false" class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-1">
                                                            Show less
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        @else
                                            <div class="space-y-1">
                                                @foreach($changes as $change)
                                                    <div class="text-xs">
                                                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $change['key'])) }}:</span>
                                                        <span class="text-green-600">{{ $change['new'] }}</span>
                                                        @if($change['old'] !== null)
                                                            <span class="text-gray-400">(was: {{ $change['old'] }})</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">No changes recorded</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $auditLogs->links() }}
            </div>
        @else
            <div class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No audit logs found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    No activity matches your current filter criteria.
                </p>
            </div>
        @endif
    </div>
</div>