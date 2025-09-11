<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trello Integrations - Joy Admin</title>
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
                        <a href="/admin" class="text-gray-600 hover:text-gray-900">
                            ← Back to Admin
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">Trello Integrations</h1>
                    </div>
                    <a href="{{ route('admin.trello.create') }}" class="brand-button">
                        + Add Integration
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                
                @if(session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($integrations->isEmpty())
                    <!-- Empty State -->
                    <div class="bg-white rounded-lg shadow p-12 text-center">
                        <div class="mx-auto h-12 w-12 text-gray-400">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No Trello Integrations</h3>
                        <p class="mt-2 text-gray-500">Get started by creating your first Trello integration.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.trello.create') }}" class="brand-button">
                                Create Integration
                            </a>
                        </div>
                    </div>
                @else
                    <!-- Integrations Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($integrations as $integration)
                            <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                                <div class="p-6">
                                    <!-- Header -->
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            {{ $integration->workspace->name }}
                                        </h3>
                                        <div class="flex items-center space-x-2">
                                            @if($integration->is_active)
                                                <span class="w-3 h-3 bg-green-500 rounded-full" title="Active"></span>
                                            @else
                                                <span class="w-3 h-3 bg-red-500 rounded-full" title="Inactive"></span>
                                            @endif
                                            <button class="text-gray-400 hover:text-gray-600" 
                                                    onclick="toggleIntegration({{ $integration->id }}, {{ $integration->is_active ? 'false' : 'true' }})">
                                                @if($integration->is_active)
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-7 6h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                @endif
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Info -->
                                    <div class="space-y-2 mb-4">
                                        <div class="flex items-center text-sm">
                                            <span class="text-gray-500 w-20">Board:</span>
                                            <span class="text-gray-900 font-mono">{{ $integration->board_id }}</span>
                                        </div>
                                        @if($integration->list_id)
                                            <div class="flex items-center text-sm">
                                                <span class="text-gray-500 w-20">List:</span>
                                                <span class="text-gray-900 font-mono">{{ $integration->list_id }}</span>
                                            </div>
                                        @endif
                                        <div class="flex items-center text-sm">
                                            <span class="text-gray-500 w-20">Last Sync:</span>
                                            <span class="text-gray-900">
                                                {{ $integration->last_sync_at ? $integration->last_sync_at->format('M j, Y @ g:i A') : 'Never' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="mb-4">
                                        @php
                                            $status = $integration->sync_status['status'] ?? 'not-configured';
                                        @endphp
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                            {{ $status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $status === 'not-configured' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ ucfirst(str_replace('-', ' ', $status)) }}
                                        </span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.trello.show', $integration) }}" 
                                           class="flex-1 text-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                                            View
                                        </a>
                                        <button onclick="testConnection({{ $integration->id }})"
                                                class="flex-1 px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                            Test
                                        </button>
                                        @if($integration->is_active)
                                            <button onclick="syncIntegration({{ $integration->id }})"
                                                    class="flex-1 px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700">
                                                Sync
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </main>
    </div>

    <script>
        async function testConnection(integrationId) {
            try {
                const response = await fetch(`/admin/trello/${integrationId}/test`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                alert('❌ Connection test failed: ' + error.message);
            }
        }

        async function syncIntegration(integrationId) {
            if (!confirm('This will sync all workspace content to Trello. Continue?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/trello/${integrationId}/sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                alert('❌ Sync failed: ' + error.message);
            }
        }

        async function toggleIntegration(integrationId, enable) {
            const action = enable ? 'enable' : 'disable';
            
            if (!confirm(`Are you sure you want to ${action} this integration?`)) {
                return;
            }

            try {
                const response = await fetch(`/admin/trello/${integrationId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    window.location.reload();
                } else {
                    alert('❌ Failed to toggle integration');
                }
            } catch (error) {
                alert('❌ Toggle failed: ' + error.message);
            }
        }
    </script>
</body>
</html>