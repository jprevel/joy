<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Audit Logs - Joy Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('admin.audit.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                            ← Back to Dashboard
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">Recent Audit Logs</h1>
                        <span class="text-sm text-gray-500">(Last 100 entries)</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('admin.audit.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            View All Logs
                        </a>
                        <button onclick="exportRecentLogs()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Export Recent
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                                   class="form-input w-full rounded-md border-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                                   class="form-input w-full rounded-md border-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                            <select name="user_id" class="form-select w-full rounded-md border-gray-300">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                            <select name="event" class="form-select w-full rounded-md border-gray-300">
                                <option value="">All Events</option>
                                @foreach($eventTypes as $eventType)
                                    <option value="{{ $eventType }}" {{ ($filters['event'] ?? '') == $eventType ? 'selected' : '' }}>
                                        {{ $eventType }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Apply Filters
                            </button>
                        </div>
                    </form>

                    @if(!empty(array_filter($filters)))
                        <div class="mt-4">
                            <a href="{{ route('admin.audit.recent') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                Clear all filters
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Results Summary -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            @if($auditLogs->isEmpty())
                                <span class="text-gray-500">No audit log entries found matching the criteria.</span>
                            @else
                                Showing <span class="font-semibold">{{ $auditLogs->count() }}</span>
                                of the most recent audit log entries
                                @if(!empty(array_filter($filters)))
                                    <span class="text-blue-600">(filtered)</span>
                                @endif
                            @endif
                        </div>
                        <div class="text-sm text-gray-500">
                            Last updated: {{ now()->format('M j, Y g:i A') }}
                        </div>
                    </div>
                </div>

                <!-- Audit Logs Table -->
                @if($auditLogs->isNotEmpty())
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Timestamp
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Event
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            User
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            IP Address
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Details
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($auditLogs as $auditLog)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="font-medium">
                                                    {{ $auditLog->created_at->format('M j, Y') }}
                                                </div>
                                                <div class="text-gray-500">
                                                    {{ $auditLog->created_at->format('g:i A') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ str_contains($auditLog->event, 'error') ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ str_contains($auditLog->event, 'login') ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ str_contains($auditLog->event, 'create') ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ !str_contains($auditLog->event, 'error') && !str_contains($auditLog->event, 'login') && !str_contains($auditLog->event, 'create') ? 'bg-gray-100 text-gray-800' : '' }}">
                                                    {{ $auditLog->event }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if($auditLog->user)
                                                    <div class="font-medium">{{ $auditLog->user->name }}</div>
                                                    <div class="text-gray-500">{{ $auditLog->user->email }}</div>
                                                @else
                                                    <span class="text-gray-400 italic">System</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $auditLog->ip_address ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <div class="max-w-xs truncate">
                                                    @if($auditLog->auditable_type)
                                                        <span class="text-gray-600">{{ class_basename($auditLog->auditable_type) }}</span>
                                                        @if($auditLog->auditable_id)
                                                            <span class="text-gray-400">#{{ $auditLog->auditable_id }}</span>
                                                        @endif
                                                    @endif

                                                    @if($auditLog->new_values && count($auditLog->new_values) > 0)
                                                        <div class="text-xs mt-1">
                                                            <span class="text-green-600">+{{ count($auditLog->new_values) }} changes</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="bg-white rounded-lg shadow p-12 text-center">
                        <div class="text-gray-400 text-6xl mb-4">📋</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No audit logs found</h3>
                        <p class="text-gray-500 mb-6">
                            @if(!empty(array_filter($filters)))
                                No audit log entries match your current filter criteria. Try adjusting your filters or clearing them entirely.
                            @else
                                There are no audit log entries in the system yet. Activity will appear here as users interact with the application.
                            @endif
                        </p>
                        @if(!empty(array_filter($filters)))
                            <a href="{{ route('admin.audit.recent') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Clear Filters
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </main>
    </div>

    <script>
        function exportRecentLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('format', 'csv');
            window.location.href = '{{ route("admin.audit.export") }}?' + params.toString();
        }
    </script>
</body>
</html>