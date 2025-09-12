
<div>

<div class="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100">
  <style>
@keyframes slideInFromTop {
  0% {
    opacity: 0;
    transform: translateY(-20px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-slide-in {
  animation: slideInFromTop 0.5s ease-out;
}

/* Highlight effect for newly added comments */
.comment-item:first-child {
  animation: slideInFromTop 0.5s ease-out, highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
  0%, 100% {
    background-color: transparent;
  }
  50% {
    background-color: rgba(59, 130, 246, 0.1);
  }
}

/* Dark mode highlight */
.dark .comment-item:first-child {
  animation: slideInFromTop 0.5s ease-out, highlightPulseDark 2s ease-in-out;
}

@keyframes highlightPulseDark {
  0%, 100% {
    background-color: transparent;
  }
  50% {
    background-color: rgba(59, 130, 246, 0.2);
  }
}
  </style>
  <div class="min-h-full py-10 px-4 md:px-8">
    <div class="mx-auto max-w-4xl space-y-6">
      
      <!-- Page Header -->
      <div class="border-b border-neutral-200 dark:border-neutral-800 pb-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <img src="/MM_logo.png" alt="MajorMajor Logo" class="h-16 w-auto">
            <div>
              <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">
                Content Review
                <span class="text-sm font-normal text-neutral-500 dark:text-neutral-400">
                  ({{ ucfirst($currentRole) }} View)
                </span>
              </h1>
              <p class="mt-1 text-lg text-neutral-600 dark:text-neutral-400">
                {{ $reviewDate->format('l, F j, Y') }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <a href="{{ route('calendar') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-200 dark:bg-neutral-700 text-neutral-800 dark:text-neutral-200 rounded-lg hover:bg-neutral-300 dark:hover:bg-neutral-600 transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
              Back to Calendar
            </a>
          </div>
        </div>
      </div>

      <!-- Flash Messages -->
      @if (session()->has('success'))
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-lg">
          {{ session('success') }}
        </div>
      @endif

      @if (session()->has('info'))
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg">
          {{ session('info') }}
        </div>
      @endif

      @if (session()->has('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
          {{ session('error') }}
        </div>
      @endif

      <!-- Content Items -->
      @if($contentItems->count() > 0)
        <div class="space-y-6">
          @foreach($contentItems as $item)
            <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg shadow-sm overflow-hidden
              @if($item->status === 'Approved') ring-2 ring-emerald-200 dark:ring-emerald-800
              @elseif($item->status === 'Scheduled') ring-2 ring-indigo-200 dark:ring-indigo-800
              @elseif($item->status === 'Changes Requested') ring-2 ring-red-200 dark:ring-red-800 @endif">
              
              <!-- Item Header -->
              <div class="bg-neutral-50 dark:bg-neutral-900 px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-4">
                    <!-- Platform Icon -->
                    <div class="flex-shrink-0">
                      @if($item->platform === 'facebook')
                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                          f
                        </div>
                      @elseif($item->platform === 'instagram')
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 via-pink-600 to-orange-400 flex items-center justify-center text-white">
                          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.646.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                          </svg>
                        </div>
                      @elseif($item->platform === 'linkedin')
                        <div class="w-10 h-10 rounded-full bg-blue-700 flex items-center justify-center text-white font-bold text-sm">
                          in
                        </div>
                      @elseif($item->platform === 'blog')
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                          B
                        </div>
                      @else
                        <div class="w-10 h-10 rounded-full bg-neutral-500 flex items-center justify-center text-white font-bold">
                          {{ strtoupper(substr($item->platform, 0, 1)) }}
                        </div>
                      @endif
                    </div>
                    
                    <!-- Content Info -->
                    <div>
                      <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                        {{ $item->title }}
                      </h3>
                      <p class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ ucfirst($item->platform) }} post
                      </p>
                    </div>
                  </div>
                  
                  <!-- Status Badge & Action Buttons -->
                  <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                      @if($item->status === 'In Review') bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200
                      @elseif($item->status === 'Ready for Review') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200
                      @elseif($item->status === 'Approved') bg-emerald-100 text-emerald-800 dark:bg-emerald-800/80 dark:text-emerald-100
                      @elseif($item->status === 'Scheduled') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-200
                      @elseif($item->status === 'Changes Requested') bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200
                      @else bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200 @endif">
                      {{ $item->status }}
                    </span>
                    
                    <!-- Action Buttons (conditional based on status) -->
                    <div class="flex gap-2">
                      @if(in_array($item->status, ['In Review', 'Ready for Review', 'Changes Requested']))
                        <!-- Content that can be approved -->
                        <button wire:click="approveContent({{ $item->id }})" 
                                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition font-medium text-sm">
                          ✓ Approve
                        </button>
                      @elseif(in_array($item->status, ['Approved', 'Scheduled']))
                        <!-- Content that can be unapproved -->
                        <button wire:click="unapproveContent({{ $item->id }})" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium text-sm">
                          ✗ Unapprove
                        </button>
                      @else
                        <!-- Other statuses - show informational text -->
                        <span class="px-4 py-2 text-neutral-500 dark:text-neutral-400 text-sm italic">
                          No actions available
                        </span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Item Content -->
              <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  
                  <!-- Content Preview -->
                  <div>
                    <h4 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-3">Content Preview</h4>
                    <div class="bg-neutral-50 dark:bg-neutral-900 rounded-lg p-4">
                      @if($item->copy)
                        <p class="text-neutral-700 dark:text-neutral-300 whitespace-pre-line leading-relaxed">{{ $item->copy }}</p>
                      @else
                        <p class="text-neutral-500 dark:text-neutral-400 italic">No content text provided</p>
                      @endif
                      
                      @if($item->hasImage())
                        <div class="mt-4">
                          <img src="{{ $item->image_url }}" alt="Content media" class="rounded-lg max-w-full h-48 object-cover border border-neutral-200 dark:border-neutral-700" />
                        </div>
                      @endif
                    </div>
                  </div>
                  
                  <!-- Comments -->
                  <div>
                    <h4 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-3">Comments & Feedback</h4>
                    
                    <!-- Comment Input -->
                    @php
                      $isApproved = in_array($item->status, ['Approved', 'Scheduled']);
                    @endphp
                    <div class="mb-4">
                      <textarea wire:model="commentText.{{ $item->id }}" 
                                class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-lg dark:bg-neutral-800 dark:text-neutral-100 text-sm
                                       {{ $isApproved ? 'bg-neutral-100 dark:bg-neutral-700 text-neutral-500 dark:text-neutral-400 cursor-not-allowed' : '' }}" 
                                rows="3" 
                                placeholder="{{ $isApproved ? 'Content is approved - commenting disabled' : 'Add a comment or feedback...' }}"
                                {{ $isApproved ? 'disabled' : '' }}></textarea>
                      <div class="mt-2">
                        <button wire:click="addComment({{ $item->id }})" 
                                class="px-4 py-2 rounded-lg transition font-medium text-sm
                                       {{ $isApproved ? 'bg-neutral-300 dark:bg-neutral-600 text-neutral-500 dark:text-neutral-400 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700' }}"
                                {{ $isApproved ? 'disabled' : '' }}>
                          Add Comment
                        </button>
                      </div>
                    </div>
                    
                    <!-- Existing Comments -->
                    <div class="bg-neutral-50 dark:bg-neutral-900 rounded-lg p-4 min-h-[120px] max-h-[200px] overflow-y-auto" id="comments-{{ $item->id }}">
                      @if($item->comments && $item->comments->count() > 0)
                        <div class="space-y-3">
                          @foreach($item->comments->sortByDesc('created_at') as $comment)
                            <div class="comment-item border-l-4 border-blue-400 pl-3 py-2 animate-slide-in" 
                                 data-comment-id="{{ $comment->id }}">
                              <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 mb-1">
                                <span class="font-medium">{{ $comment->author_display_name }}</span>
                                <span>{{ $comment->created_at->diffForHumans() }}</span>
                              </div>
                              <p class="text-sm text-neutral-700 dark:text-neutral-300">{{ $comment->body }}</p>
                            </div>
                          @endforeach
                        </div>
                      @else
                        <div class="text-sm text-neutral-500 dark:text-neutral-400 italic">
                          No comments yet. Add your first comment above.
                        </div>
                      @endif
                    </div>
                  </div>
                  
                </div>
              </div>
              
            </div>
          @endforeach
        </div>
      @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg p-12 text-center">
          <svg class="w-16 h-16 mx-auto mb-4 text-neutral-300 dark:text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-2">No content to review</h3>
          <p class="text-neutral-500 dark:text-neutral-400">
            There are no content items ready for review on {{ $reviewDate->format('F j, Y') }}.
          </p>
          <p class="text-sm text-neutral-400 dark:text-neutral-500 mt-2">
            Check other dates or contact your marketing team.
          </p>
        </div>
      @endif
      
    </div>
  </div>
</div>
</div>