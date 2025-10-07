<div class="max-w-6xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="p-6 border-b">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
            </div>

            <!-- Search and Filters -->
            <div class="mt-4 flex gap-4">
                <div class="flex-1">
                    <input type="text"
                           wire:model.live="search"
                           placeholder="Search clients..."
                           class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Client List -->
        <div class="p-6">
            @if($clients->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($clients as $client)
                        <div class="border rounded-lg p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <a href="{{ route('clients.show', $client) }}"
                                           class="hover:text-blue-600">
                                            {{ $client->name }}
                                        </a>
                                    </h3>
                                    @if($client->email)
                                        <p class="text-sm text-gray-600 mt-1">{{ $client->email }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-col items-end">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($client->status === 'active') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($client->status ?? 'active') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Client Stats -->
                            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Content Items:</span>
                                    <span>{{ $client->contentItems->count() ?? 0 }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">Magic Links:</span>
                                    <span>{{ $client->magicLinks->where('expires_at', '>', now())->count() ?? 0 }}</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex gap-2">
                                <a href="{{ route('clients.show', $client) }}"
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    View Details
                                </a>

                                @if($hasPermission('manage magic links'))
                                    <a href="{{ route('clients.magic-links', $client) }}"
                                       class="text-green-600 hover:text-green-800 text-sm font-medium">
                                        Magic Links
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No clients found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($search)
                            Try adjusting your search criteria.
                        @else
                            No clients are currently assigned to your teams.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>