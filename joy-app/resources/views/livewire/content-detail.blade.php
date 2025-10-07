<div class="max-w-4xl mx-auto p-6">
    @if($contentItem)
        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- Header -->
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $contentItem->title }}</h1>
                    <div class="flex items-center gap-4 mt-2">
                        <span class="px-3 py-1 text-xs font-medium rounded-full
                            @if($contentItem->status === 'draft') bg-gray-100 text-gray-800
                            @elseif($contentItem->status === 'review') bg-yellow-100 text-yellow-800
                            @elseif($contentItem->status === 'approved') bg-green-100 text-green-800
                            @elseif($contentItem->status === 'scheduled') bg-blue-100 text-blue-800
                            @elseif($contentItem->status === 'published') bg-purple-100 text-purple-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($contentItem->status) }}
                        </span>
                        <span class="text-sm text-gray-500">{{ $contentItem->platform }}</span>
                        @if($contentItem->scheduled_at)
                            <span class="text-sm text-gray-500">
                                Scheduled: {{ $contentItem->scheduled_at->format('M j, Y g:i A') }}
                            </span>
                        @endif
                    </div>
                </div>

                @if($hasPermission('edit content'))
                    <a href="{{ route('content.edit', $contentItem) }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Edit Content
                    </a>
                @endif
            </div>

            <!-- Content -->
            <div class="mb-6">
                @if($contentItem->description)
                    <div class="prose max-w-none">
                        <p>{{ $contentItem->description }}</p>
                    </div>
                @endif

                @if($contentItem->media_path)
                    <div class="mt-4">
                        <img src="{{ Storage::url($contentItem->media_path) }}"
                             alt="Content media"
                             class="max-w-full h-auto rounded-lg">
                    </div>
                @endif
            </div>

            <!-- Status Actions -->
            @if($hasPermission('manage content'))
                <div class="flex gap-2 mb-6">
                    @if($contentItem->status !== 'approved')
                        <button wire:click="updateStatus('approved')"
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            Approve
                        </button>
                    @endif

                    @if($contentItem->status === 'approved')
                        <button wire:click="updateStatus('review')"
                                class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">
                            Return to Review
                        </button>
                    @endif

                    @if($contentItem->status !== 'changes_requested')
                        <button wire:click="updateStatus('changes_requested')"
                                class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700">
                            Request Changes
                        </button>
                    @endif
                </div>
            @endif

            <!-- Comments Section -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold mb-4">Comments</h3>

                <!-- Add Comment Form -->
                @if($hasPermission('comment on content'))
                    <div class="mb-6">
                        <textarea wire:model="newComment"
                                  class="w-full p-3 border rounded-lg"
                                  rows="3"
                                  placeholder="Add a comment..."></textarea>
                        @error('newComment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                        <button wire:click="addComment"
                                class="mt-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Add Comment
                        </button>
                    </div>
                @endif

                <!-- Comments List -->
                @if($contentItem->comments && $contentItem->comments->count() > 0)
                    <div class="space-y-4">
                        @foreach($contentItem->comments as $comment)
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-medium text-gray-900">
                                        {{ $comment->user->name ?? 'Unknown User' }}
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-gray-700">{{ $comment->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">No comments yet.</p>
                @endif
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-gray-500">Content not found.</p>
        </div>
    @endif
</div>