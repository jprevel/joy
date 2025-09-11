<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Joy Admin</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="exportLogs()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Export CSV
                        </button>
                        <button onclick="showCleanupModal()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Cleanup Old Logs
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
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Workspace</label>
                            <select name="workspace_id" class="form-select">
                                <option value="">All Workspaces</option>
                                @foreach($workspaces as $workspace)
                                    <option value="{{ $workspace->id }}" {{ ($filters['workspace_id'] ?? '') == $workspace->id ? 'selected' : '' }}>
                                        {{ $workspace->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                @foreach($actions as $action)
                                    <option value="{{ $action }}" {{ ($filters['action'] ?? '') == $action ? 'selected' : '' }}>
                                        {{ ucfirst($action) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                            <select name="severity" class="form-select">
                                <option value="">All Severities</option>
                                @foreach($severities as $severity)
                                    <option value="{{ $severity }}" {{ ($filters['severity'] ?? '') == $severity ? 'selected' : '' }}>
                                        {{ ucfirst($severity) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User Type</label>
                            <select name="user_type" class="form-select">
                                <option value="">All Types</option>
                                @foreach($userTypes as $type)
                                    <option value="{{ $type }}" {{ ($filters['user_type'] ?? '') == $type ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                            <select name="days" class="form-select">
                                <option value="1" {{ $days == 1 ? 'selected' : '' }}>Last 24 Hours</option>
                                <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 Days</option>
                                <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 Days</option>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="brand-button w-full">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Results Summary -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">
                            Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} results
                        </span>
                        <span class="text-sm text-gray-600">
                            Filtered by: {{ $days }} day{{ $days != 1 ? 's' : '' }}
                            @if($filters['workspace_id'])
                                , Workspace: {{ $workspaces->find($filters['workspace_id'])->name ?? 'Unknown' }}
                            @endif
                            @if($filters['action'])
                                , Action: {{ ucfirst($filters['action']) }}
                            @endif
                            @if($filters['severity'])
                                , Severity: {{ ucfirst($filters['severity']) }}
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Model
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Workspace
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Severity
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        IP Address
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($logs as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $log->created_at->format('M j, H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $log->getUserDisplayName() }}</div>
                                            <div class="text-xs text-gray-500">{{ ucfirst($log->user_type) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $log->getActionDisplayName() }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $log->auditable_type ? class_basename($log->auditable_type) : 'N/A' }}
                                            @if($log->auditable_id)
                                                <div class="text-xs text-gray-400">#{{ $log->auditable_id }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $log->workspace?->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $log->getSeverityColor() }}">
                                                {{ ucfirst($log->severity) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                            {{ $log->ip_address ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('admin.audit.show', $log) }}" 
                                               class="text-blue-600 hover:text-blue-900">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            No audit logs found for the selected filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($logs->hasPages())
                    <div class="mt-6">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>

    <!-- Cleanup Modal -->
    <div id="cleanupModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="hideCleanupModal()"></div>

            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Cleanup Old Audit Logs</h3>
                
                <div class="mb-4">
                    <label for="cleanup-days" class="block text-sm font-medium text-gray-700 mb-1">
                        Keep logs for the last (days):
                    </label>
                    <select id="cleanup-days" class="form-select">
                        <option value="90">90 days (recommended)</option>
                        <option value="180">180 days</option>
                        <option value="365">365 days</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Logs older than this will be permanently deleted. Minimum 30 days required.
                    </p>
                </div>
                
                <div class="bg-red-50 rounded-lg p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Warning</h3>
                            <p class="mt-1 text-sm text-red-700">
                                This action cannot be undone. Old audit logs will be permanently deleted.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="performCleanup()" 
                            class="flex-1 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                        Delete Old Logs
                    </button>
                    <button onclick="hideCleanupModal()" 
                            class="flex-1 bg-gray-100 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('format', 'csv');
            window.open(`{{ route('admin.audit.export') }}?${params.toString()}`, '_blank');
        }

        function showCleanupModal() {
            document.getElementById('cleanupModal').classList.remove('hidden');
        }

        function hideCleanupModal() {
            document.getElementById('cleanupModal').classList.add('hidden');
        }

        async function performCleanup() {
            const days = document.getElementById('cleanup-days').value;
            
            try {
                const response = await fetch('{{ route('admin.audit.cleanup') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ days: parseInt(days) })
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                alert('❌ Cleanup failed: ' + error.message);
            }
            
            hideCleanupModal();
        }
    </script>
</body>
</html>