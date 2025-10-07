<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $workspace->name }} - Content Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $workspace->name }}</h1>
                        <p class="text-sm text-gray-600">Welcome, {{ $magicLink->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Secure access link</p>
                        <p class="text-xs text-gray-500">Expires: {{ $magicLink->expires_at->format('M j, Y g:i A') }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <!-- Welcome Section -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Content Review Dashboard</h2>
                    <p class="text-gray-600 mb-4">
                        Review and approve content concepts and social media content items for {{ $workspace->name }}.
                        Use the navigation below to access your content calendar and detailed reviews.
                    </p>
                    <div class="flex space-x-4">
                        <a href="{{ route('client.calendar', $magicLink->token) }}" 
                           class="brand-button">
                            📅 View Calendar
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    @php
                        $concepts = $workspace->concepts()->with('contentItems')->get();
                        $totalContentItems = $concepts->sum(fn($concept) => $concept->contentItems->count());
                        $pendingApproval = $concepts->sum(fn($concept) => $concept->contentItems->where('status', 'In Review')->count());
                        $scheduled = $concepts->sum(fn($concept) => $concept->contentItems->where('status', 'Scheduled')->count());
                    @endphp
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-md bg-blue-50">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Content</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $totalContentItems }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-md bg-yellow-50">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Pending Review</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $pendingApproval }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-md bg-green-50">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Scheduled</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $scheduled }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Concepts -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Recent Content Concepts</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($concepts->take(5) as $concept)
                            <div class="px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900">
                                            <a href="{{ route('client.concept', [$magicLink->token, $concept->id]) }}" 
                                               class="hover:text-blue-600">
                                                {{ $concept->title }}
                                            </a>
                                        </h4>
                                        <p class="text-sm text-gray-500 mt-1">{{ Str::limit($concept->notes, 100) }}</p>
                                        <div class="mt-2 flex items-center space-x-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $concept->status === 'Draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                                {{ $concept->status === 'In Review' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $concept->status === 'Approved' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $concept->status === 'Scheduled' ? 'bg-blue-100 text-blue-800' : '' }}">
                                                {{ $concept->status }}
                                            </span>
                                            <span class="text-xs text-gray-500">{{ $concept->contentItems->count() }} content items</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @foreach($concept->contentItems->take(3) as $contentItem)
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm 
                                                {{ App\Services\ContentCalendarService::getPlatformColor($contentItem->platform) }}">
                                                {{ App\Services\ContentCalendarService::getPlatformIcon($contentItem->platform) }}
                                            </span>
                                        @endforeach
                                        @if($concept->contentItems->count() > 3)
                                            <span class="text-xs text-gray-400">+{{ $concept->contentItems->count() - 3 }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>