<div>
<!doctype html>
<html lang="en" class="h-full antialiased">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Add Content - Joy Content Calendar</title>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- MajorMajor Brand Styles -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="with-stripe h-full bg-neutral-50 text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 selection:bg-indigo-200/60 selection:text-neutral-900">
  
  <!-- Role Testing Bar -->
  <div class="bg-yellow-100 dark:bg-yellow-900/30 border-b border-yellow-200 dark:border-yellow-800 px-4 py-2">
    <div class="mx-auto max-w-5xl flex items-center justify-between">
      <div class="flex items-center gap-2 text-sm">
        <span class="font-medium text-yellow-800 dark:text-yellow-200">Testing Mode:</span>
        <span class="px-2 py-1 bg-yellow-200 dark:bg-yellow-800 text-yellow-900 dark:text-yellow-100 rounded text-xs font-bold uppercase">
          {{ $currentRole }}
        </span>
        @php $currentUser = $this->getCurrentUserRole(); @endphp
        @if($currentUser)
          <span class="text-xs text-yellow-700 dark:text-yellow-300">
            ({{ $currentUser->name }})
          </span>
        @endif
      </div>
      <div class="flex items-center gap-2">
        <a href="{{ route('calendar.role', $currentRole) }}" 
           class="px-2 py-1 text-xs rounded transition bg-yellow-200 dark:bg-yellow-800 hover:bg-yellow-300 dark:hover:bg-yellow-700 text-yellow-900 dark:text-yellow-100">
          ← Back to Calendar
        </a>
      </div>
    </div>
  </div>
  
  <div class="min-h-full py-10 px-4 md:px-8">
    <div class="mx-auto max-w-3xl">
      <!-- Page Header -->
      <div class="border-b border-neutral-200 dark:border-neutral-800 pb-4 mb-8">
        <div class="flex items-center gap-4">
          <img src="/MM_logo.png" alt="MajorMajor Logo" class="h-16 w-auto">
          <div>
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">
              Add New Content
            </h1>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
              Create content for your clients' social media calendars
            </p>
          </div>
        </div>
      </div>

      <!-- Success/Error Messages -->
      @if(session()->has('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md">
          <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
      @endif

      @if(session()->has('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
          <p class="text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
      @endif

      <!-- Content Creation Form -->
      <form wire:submit="save" class="space-y-6">
        <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
          <!-- Client Selection -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="client_id" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                Client *
              </label>
              <select wire:model="client_id" id="client_id" 
                      class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md shadow-sm bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Select a client...</option>
                @foreach($clients as $client)
                  <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
              </select>
              @error('client_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <!-- Platform Selection -->
            <div>
              <label for="platform" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                Platform *
              </label>
              <select wire:model="platform" id="platform" 
                      class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md shadow-sm bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Select a platform...</option>
                @foreach($platforms as $platform)
                  <option value="{{ $platform }}">{{ $platform }}</option>
                @endforeach
              </select>
              @error('platform') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
          </div>

          <!-- Title -->
          <div class="mt-6">
            <label for="title" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              Title *
            </label>
            <input type="text" wire:model="title" id="title" 
                   class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md shadow-sm bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="Enter content title...">
            @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
          </div>

          <!-- Copy/Content -->
          <div class="mt-6">
            <label for="copy" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              Content/Copy
            </label>
            <textarea wire:model="copy" id="copy" rows="4"
                      class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md shadow-sm bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Enter the content text for this post..."></textarea>
            @error('copy') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
          </div>

          <!-- Notes -->
          <div class="mt-6">
            <label for="notes" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              Internal Notes
            </label>
            <textarea wire:model="notes" id="notes" rows="3"
                      class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md shadow-sm bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Add any internal notes for the team..."></textarea>
            @error('notes') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
          </div>

          <!-- Scheduled Date -->
          <div class="mt-6">
            <label for="scheduled_at" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              Scheduled Date & Time *
            </label>
            <input type="datetime-local" wire:model="scheduled_at" id="scheduled_at" 
                   class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md shadow-sm bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            @error('scheduled_at') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
          </div>

          <!-- Image Upload -->
          <div class="mt-6">
            <label for="image" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              Image Upload
            </label>
            <div id="dropzone" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-neutral-300 dark:border-neutral-600 border-dashed rounded-md hover:border-indigo-400 transition">
              <div class="space-y-1 text-center">
                @if ($image)
                  <div class="mb-4">
                    <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="mx-auto h-32 w-auto rounded-md">
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">{{ $image->getClientOriginalName() }}</p>
                  </div>
                @else
                  <svg class="mx-auto h-12 w-12 text-neutral-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                @endif
                <div class="flex text-sm text-neutral-600 dark:text-neutral-400">
                  <label for="image" class="relative cursor-pointer bg-white dark:bg-neutral-900 rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                    <span>{{ $image ? 'Change image' : 'Upload an image' }}</span>
                    <input wire:model="image" id="image" name="image" type="file" class="sr-only" accept="image/*">
                  </label>
                  @if (!$image)
                    <p class="pl-1">or drag and drop</p>
                  @endif
                </div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                  PNG, JPG, GIF up to 10MB
                </p>
              </div>
            </div>
            @error('image') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-4">
          <button type="button" wire:click="cancel"
                  class="px-4 py-2 text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 rounded-md hover:bg-neutral-50 dark:hover:bg-neutral-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            Cancel
          </button>
          <button type="submit"
                  class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
            Create Content
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Loading States -->
  <div wire:loading.flex class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 flex items-center gap-3">
      <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span class="text-neutral-900 dark:text-neutral-100">Creating content...</span>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const dropzone = document.getElementById('dropzone');
      const fileInput = document.getElementById('image');
      
      if (dropzone && fileInput) {
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
          dropzone.addEventListener(eventName, preventDefaults, false);
          document.body.addEventListener(eventName, preventDefaults, false);
        });
        
        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
          dropzone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
          dropzone.addEventListener(eventName, unhighlight, false);
        });
        
        // Handle dropped files
        dropzone.addEventListener('drop', handleDrop, false);
        
        function preventDefaults(e) {
          e.preventDefault();
          e.stopPropagation();
        }
        
        function highlight(e) {
          dropzone.classList.add('border-indigo-500', 'border-solid', 'bg-indigo-50', 'dark:bg-indigo-900/20');
        }
        
        function unhighlight(e) {
          dropzone.classList.remove('border-indigo-500', 'border-solid', 'bg-indigo-50', 'dark:bg-indigo-900/20');
        }
        
        function handleDrop(e) {
          const dt = e.dataTransfer;
          const files = dt.files;
          
          if (files.length > 0) {
            const file = files[0];
            
            // Check if it's an image
            if (file.type.startsWith('image/')) {
              // Create a new FileList-like object and assign to the input
              const dataTransfer = new DataTransfer();
              dataTransfer.items.add(file);
              fileInput.files = dataTransfer.files;
              
              // Trigger the Livewire file input change event
              fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
              alert('Please drop an image file.');
            }
          }
        }
      }
    });
  </script>
</body>
</html>
</div>