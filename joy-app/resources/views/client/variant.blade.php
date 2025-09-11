<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $workspace->name }} - Review Content</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('client.calendar', $magicLink->token) }}" 
                           class="text-gray-600 hover:text-gray-900">
                            ← Back to Calendar
                        </a>
                        <h1 class="text-xl font-bold text-gray-900">Content Review</h1>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-900">{{ $workspace->name }}</p>
                        <p class="text-xs text-gray-500">{{ $magicLink->name }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0" x-data="variantReview()">
                <!-- Content Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                    <!-- Concept Header -->
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h2 class="text-xl font-semibold text-gray-900">{{ $variant->concept->title }}</h2>
                        <p class="text-sm text-gray-600 mt-1">{{ $variant->concept->notes }}</p>
                        <div class="flex items-center space-x-4 mt-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $variant->concept->status === 'Draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $variant->concept->status === 'In Review' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $variant->concept->status === 'Approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $variant->concept->status === 'Scheduled' ? 'bg-blue-100 text-blue-800' : '' }}">
                                Concept: {{ $variant->concept->status }}
                            </span>
                            <span class="text-xs text-gray-500">
                                Due: {{ $variant->concept->due_date ? $variant->concept->due_date->format('M j, Y') : 'Not set' }}
                            </span>
                        </div>
                    </div>

                    <!-- Variant Content -->
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-lg {{ App\Services\ContentCalendarService::getPlatformColor($variant->platform) }}">
                                    {{ App\Services\ContentCalendarService::getPlatformIcon($variant->platform) }}
                                </span>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 capitalize">{{ $variant->platform }} Post</h3>
                                    <p class="text-sm text-gray-500">
                                        Scheduled: {{ \Carbon\Carbon::parse($variant->scheduled_at)->format('M j, Y @ g:i A') }}
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                {{ $variant->status === 'Draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $variant->status === 'In Review' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $variant->status === 'Approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $variant->status === 'Scheduled' ? 'bg-blue-100 text-blue-800' : '' }}">
                                {{ $variant->status }}
                            </span>
                        </div>

                        <!-- Content Preview -->
                        <div class="bg-gray-50 border rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Content Preview</h4>
                            <p class="text-gray-900 whitespace-pre-wrap">{{ $variant->copy }}</p>
                            @if($variant->media_url)
                                <div class="mt-3">
                                    <img src="{{ $variant->media_url }}" alt="Content media" class="max-w-md rounded-lg">
                                </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        @if($variant->status === 'In Review')
                            <div class="flex space-x-3 mb-6">
                                <button @click="approveVariant()" 
                                        class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                    ✓ Approve
                                </button>
                                <button @click="showRejectModal = true" 
                                        class="flex-1 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    ✗ Request Changes
                                </button>
                            </div>
                        @endif

                        <!-- Comments Section -->
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">
                                Comments & Feedback
                                <span class="text-sm font-normal text-gray-500">({{ $variant->comments->count() }})</span>
                            </h4>

                            <!-- Existing Comments -->
                            <div class="space-y-4 mb-6">
                                @foreach($variant->comments as $comment)
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <h5 class="font-medium text-gray-900">{{ $comment->author_name }}</h5>
                                            <span class="text-xs text-gray-500">
                                                {{ $comment->created_at->format('M j, Y @ g:i A') }}
                                            </span>
                                        </div>
                                        <p class="text-gray-700 whitespace-pre-wrap">{{ $comment->content }}</p>
                                    </div>
                                @endforeach

                                @if($variant->comments->isEmpty())
                                    <p class="text-gray-500 italic text-center py-8">No comments yet</p>
                                @endif
                            </div>

                            <!-- Add Comment Form -->
                            <form @submit.prevent="addComment()" class="space-y-4">
                                <div>
                                    <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
                                        Add your feedback
                                    </label>
                                    <textarea x-model="newComment" 
                                              id="comment"
                                              rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="Share your thoughts on this content..."></textarea>
                                </div>
                                <button type="submit" 
                                        :disabled="!newComment.trim()"
                                        class="brand-button disabled:opacity-50 disabled:cursor-not-allowed">
                                    Add Comment
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Reject Modal -->
                <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" 
                     style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
                             @click="showRejectModal = false"></div>

                        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Request Changes</h3>
                            
                            <form @submit.prevent="rejectVariant()">
                                <div class="mb-4">
                                    <label for="reject-reason" class="block text-sm font-medium text-gray-700 mb-1">
                                        Please explain what changes are needed:
                                    </label>
                                    <textarea x-model="rejectReason" 
                                              id="reject-reason"
                                              rows="3" 
                                              required
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                              placeholder="Describe the changes needed..."></textarea>
                                </div>
                                
                                <div class="flex space-x-3">
                                    <button type="submit" 
                                            class="flex-1 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                                        Request Changes
                                    </button>
                                    <button type="button" 
                                            @click="showRejectModal = false"
                                            class="flex-1 bg-gray-100 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-200">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function variantReview() {
            return {
                newComment: '',
                showRejectModal: false,
                rejectReason: '',

                async addComment() {
                    if (!this.newComment.trim()) return;

                    try {
                        // In a real app, this would make an API call
                        console.log('Adding comment:', this.newComment);
                        
                        // For demo purposes, show success
                        alert('Comment added successfully!');
                        this.newComment = '';
                        
                        // In real implementation:
                        // await fetch('/api/comments', { ... })
                        // window.location.reload();
                        
                    } catch (error) {
                        alert('Error adding comment. Please try again.');
                    }
                },

                async approveVariant() {
                    if (confirm('Are you sure you want to approve this content?')) {
                        try {
                            // In a real app, this would make an API call
                            console.log('Approving variant');
                            alert('Content approved successfully!');
                            
                            // In real implementation:
                            // await fetch('/api/variants/approve', { ... })
                            // window.location.reload();
                            
                        } catch (error) {
                            alert('Error approving content. Please try again.');
                        }
                    }
                },

                async rejectVariant() {
                    if (!this.rejectReason.trim()) return;

                    try {
                        // In a real app, this would make an API call
                        console.log('Rejecting variant with reason:', this.rejectReason);
                        alert('Change request sent successfully!');
                        
                        this.showRejectModal = false;
                        this.rejectReason = '';
                        
                        // In real implementation:
                        // await fetch('/api/variants/reject', { ... })
                        // window.location.reload();
                        
                    } catch (error) {
                        alert('Error sending change request. Please try again.');
                    }
                }
            }
        }
    </script>
</body>
</html>