<div class="max-w-2xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Content</h1>

        <form wire:submit="save" class="space-y-6">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                <input type="text"
                       id="title"
                       wire:model="title"
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>
                @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea id="description"
                          wire:model="description"
                          class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          rows="4"
                          placeholder="Optional description..."></textarea>
                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Platform -->
            <div>
                <label for="platform" class="block text-sm font-medium text-gray-700 mb-2">Platform</label>
                <select id="platform"
                        wire:model="platform"
                        class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                    <option value="">Select a platform...</option>
                    @foreach($platforms as $platform)
                        <option value="{{ $platform['id'] }}">{{ $platform['name'] }}</option>
                    @endforeach
                </select>
                @error('platform') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Scheduled Date -->
            <div>
                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-2">Scheduled Date & Time</label>
                <input type="datetime-local"
                       id="scheduled_at"
                       wire:model="scheduled_at"
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('scheduled_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Media Upload -->
            <div>
                <label for="media_file" class="block text-sm font-medium text-gray-700 mb-2">Media File</label>

                @if($existing_media_path)
                    <div class="mb-3">
                        <p class="text-sm text-gray-600">Current media:</p>
                        <img src="{{ Storage::url($existing_media_path) }}"
                             alt="Current media"
                             class="max-w-xs h-auto rounded-lg mt-2">
                    </div>
                @endif

                <input type="file"
                       id="media_file"
                       wire:model="media_file"
                       accept="image/*,video/*"
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('media_file') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                @if($media_file)
                    <div class="mt-3">
                        <p class="text-sm text-gray-600">New file selected: {{ $media_file->getClientOriginalName() }}</p>
                    </div>
                @endif
            </div>

            <!-- Form Actions -->
            <div class="flex gap-3 pt-4">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">
                    Update Content
                </button>

                <button type="button"
                        wire:click="cancel"
                        class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 font-medium">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>