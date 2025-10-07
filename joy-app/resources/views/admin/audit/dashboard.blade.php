<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Dashboard - Joy Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <a href="/admin" class="text-gray-600 hover:text-gray-900">
                            ← Back to Admin
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">Audit Dashboard</h1>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('admin.audit.recent') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Recent Logs
                        </a>
                        <a href="{{ route('admin.audit.index') }}" class="brand-button">
                            View All Logs
                        </a>
                        <button onclick="exportLogs()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Export CSV
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
                    <form method="GET" class="flex flex-wrap items-end gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Workspace</label>
                            <select name="workspace_id" class="form-select">
                                <option value="">All Workspaces</option>
                                @foreach($workspaces as $workspace)
                                    <option value="{{ $workspace->id }}" {{ $workspaceId == $workspace->id ? 'selected' : '' }}>
                                        {{ $workspace->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                            <select name="days" class="form-select">
                                <option value="1" {{ $days == 1 ? 'selected' : '' }}>Last 24 Hours</option>
                                <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 Days</option>
                                <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 Days</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="brand-button">
                            Update
                        </button>
                    </form>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-md bg-blue-50">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Events</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format($report['total_events']) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-md bg-green-50">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Unique Users</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $report['unique_users'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-md bg-yellow-50">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Security Events</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $report['security_events'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-md bg-red-50">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Errors</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $report['errors'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Activity by Action -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Top Actions ({{ $days }} days)</h3>
                        <div class="space-y-3">
                            @foreach($topActions->take(8) as $action)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">{{ ucfirst($action->action) }}</span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" 
                                                 style="width: {{ ($action->count / $topActions->first()->count) * 100 }}%"></div>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900 w-8 text-right">{{ $action->count }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Activity by Severity -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Events by Severity</h3>
                        <div class="space-y-3">
                            @foreach($report['events_by_severity'] as $severity => $count)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">{{ ucfirst($severity) }}</span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            @php
                                                $maxCount = collect($report['events_by_severity'])->max();
                                                $percentage = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                                                $color = match($severity) {
                                                    'error', 'critical' => 'bg-red-600',
                                                    'warning' => 'bg-yellow-600',
                                                    'info' => 'bg-blue-600',
                                                    default => 'bg-gray-600'
                                                };
                                            @endphp
                                            <div class="{{ $color }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900 w-8 text-right">{{ $count }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Recent Activity and Security Events -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Activity -->
                    <div class="lg:col-span-2 bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                        </div>
                        <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                            @forelse($recentActivity as $log)
                                <div class="px-6 py-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $log->getSeverityColor() }}">
                                                    {{ $log->severity }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ $log->getActionDisplayName() }}
                                                </span>
                                                @if($log->auditable_type)
                                                    <span class="text-xs text-gray-500">
                                                        ({{ class_basename($log->auditable_type) }})
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500">
                                                <span>{{ $log->getUserDisplayName() }}</span>
                                                <span>{{ $log->workspace?->name ?? 'N/A' }}</span>
                                                <span>{{ $log->created_at->format('M j, H:i') }}</span>
                                            </div>
                                            @if($log->hasChanges())
                                                <div class="mt-2 text-xs text-gray-600">
                                                    Changed: {{ implode(', ', $log->getChangedFields()) }}
                                                </div>
                                            @endif
                                        </div>
                                        <a href="{{ route('admin.audit.show', $log) }}" 
                                           class="text-xs text-blue-600 hover:text-blue-800">
                                            View
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="px-6 py-8 text-center text-gray-500">
                                    No recent activity
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Security Events -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Security Events</h3>
                        </div>
                        <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                            @forelse($securityEvents->take(10) as $event)
                                <div class="px-6 py-4">
                                    <div class="flex items-start space-x-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                            {{ $event->severity }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $event->new_values['event'] ?? 'Security Event' }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ $event->created_at->format('M j, H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-6 py-8 text-center text-gray-500">
                                    No security events
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('format', 'csv');
            window.open(`{{ route('admin.audit.export') }}?${params.toString()}`, '_blank');
        }
    </script>
</body>
</html>