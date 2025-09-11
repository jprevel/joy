<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Trello Integration - Joy Admin</title>
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
                        <a href="{{ route('admin.trello.index') }}" class="text-gray-600 hover:text-gray-900">
                            ← Back to Integrations
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">Add Trello Integration</h1>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white rounded-lg shadow-lg">
                    <form method="POST" action="{{ route('admin.trello.store') }}" class="p-6 space-y-6">
                        @csrf

                        <!-- Instructions -->
                        <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
                            <h3 class="font-medium text-blue-900 mb-2">Getting Your Trello API Credentials:</h3>
                            <ol class="list-decimal list-inside text-sm text-blue-800 space-y-1">
                                <li>Visit <a href="https://trello.com/app-key" target="_blank" class="underline">https://trello.com/app-key</a></li>
                                <li>Copy your API Key from the page</li>
                                <li>Click "Generate a Token" and authorize the app</li>
                                <li>Copy the generated token</li>
                                <li>Find your Board ID from your Trello board URL (e.g., trello.com/b/<strong>BOARD_ID</strong>/board-name)</li>
                            </ol>
                        </div>

                        <!-- Client Selection -->
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Client *
                            </label>
                            <select name="client_id" id="client_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select a client...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- API Credentials -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="api_key" class="block text-sm font-medium text-gray-700 mb-1">
                                    Trello API Key *
                                </label>
                                <input type="password" name="api_key" id="api_key" required maxlength="32"
                                       value="{{ old('api_key') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
                                       placeholder="32-character API key">
                                <p class="mt-1 text-xs text-gray-500">Your Trello API Key (32 characters)</p>
                                @error('api_key')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="api_token" class="block text-sm font-medium text-gray-700 mb-1">
                                    Trello API Token *
                                </label>
                                <input type="password" name="api_token" id="api_token" required maxlength="64"
                                       value="{{ old('api_token') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
                                       placeholder="64-character API token">
                                <p class="mt-1 text-xs text-gray-500">Your Trello API Token (64 characters)</p>
                                @error('api_token')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Board Configuration -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="board_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Board ID *
                                </label>
                                <input type="text" name="board_id" id="board_id" required maxlength="24"
                                       value="{{ old('board_id') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
                                       placeholder="Trello Board ID">
                                <p class="mt-1 text-xs text-gray-500">From your Trello board URL</p>
                                @error('board_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="list_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    List ID (Optional)
                                </label>
                                <input type="text" name="list_id" id="list_id" maxlength="24"
                                       value="{{ old('list_id') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
                                       placeholder="Specific Trello List ID">
                                <p class="mt-1 text-xs text-gray-500">If blank, uses the first list in the board</p>
                                @error('list_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Security Note -->
                        <div class="bg-yellow-50 rounded-lg border border-yellow-200 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Security Notice</h3>
                                    <p class="mt-1 text-sm text-yellow-700">
                                        Your API credentials will be encrypted and stored securely. They are only used to communicate with Trello's API for syncing content and comments.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <a href="{{ route('admin.trello.index') }}" 
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Create Integration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Show/hide password fields
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }

        // Add reveal buttons to password fields
        document.addEventListener('DOMContentLoaded', function() {
            const passwordFields = ['api_key', 'api_token'];
            
            passwordFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                const wrapper = field.parentNode;
                
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600';
                button.innerHTML = '👁️';
                button.onclick = () => togglePasswordVisibility(fieldId);
                
                wrapper.style.position = 'relative';
                wrapper.appendChild(button);
                field.style.paddingRight = '3rem';
            });
        });
    </script>
</body>
</html>