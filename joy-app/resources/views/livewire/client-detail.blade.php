<div class="max-w-6xl mx-auto p-6">
    @if($client)
        <!-- Client Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $client->name }}</h1>
                    @if($client->email)
                        <p class="text-gray-600 mt-2">{{ $client->email }}</p>
                    @endif
                    @if($client->description)
                        <p class="text-gray-700 mt-3">{{ $client->description }}</p>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        @if($client->status === 'active') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($client->status ?? 'active') }}
                    </span>

                    @if($hasPermission('manage magic links'))
                        <a href="{{ route('clients.magic-links', $client) }}"
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Manage Magic Links
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Content</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $contentStats['total'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Approved</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $contentStats['approved'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">In Review</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $contentStats['review'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 4v5a4 4 0 01-8 0v-5a4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">This Month</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $contentStats['this_month'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Content Items -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Content</h2>

                @if($contentItems->count() > 0)
                    <div class="space-y-3">
                        @foreach($contentItems as $item)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900">
                                        <a href="{{ route('content.detail', $item['id']) }}"
                                           class="hover:text-blue-600">
                                            {{ $item['title'] }}
                                        </a>
                                    </h3>
                                    <p class="text-sm text-gray-500">{{ $item['platform'] }}</p>
                                </div>

                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($item['status'] === 'draft') bg-gray-100 text-gray-800
                                    @elseif($item['status'] === 'review') bg-yellow-100 text-yellow-800
                                    @elseif($item['status'] === 'approved') bg-green-100 text-green-800
                                    @elseif($item['status'] === 'scheduled') bg-blue-100 text-blue-800
                                    @elseif($item['status'] === 'published') bg-purple-100 text-purple-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($item['status']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">No content items found.</p>
                @endif
            </div>

            <!-- Recent Comments -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Comments</h2>

                @if($recentComments->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentComments as $comment)
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-medium text-gray-900">
                                        {{ $comment->user->name ?? 'Unknown User' }}
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-gray-700 text-sm">{{ Str::limit($comment->body, 100) }}</p>
                                <a href="{{ route('content.detail', $comment->content_item_id) }}"
                                   class="text-blue-600 hover:text-blue-800 text-sm mt-1 inline-block">
                                    View Content →
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">No recent comments.</p>
                @endif
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-gray-500">Client not found.</p>
        </div>
    @endif
</div>