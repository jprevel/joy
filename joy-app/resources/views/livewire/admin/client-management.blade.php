<div class="p-6">
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Client Management</h1>
                <p class="text-gray-600 mt-2">Manage clients and their Slack channel integrations</p>
            </div>

            <button wire:click="$set('showCreateForm', true)"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Create New Client
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Create/Edit Form -->
    @if($showCreateForm || $showEditForm)
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-xl font-bold mb-4">{{ $showCreateForm ? 'Create New Client' : 'Edit Client' }}</h2>

            <form wire:submit.prevent="{{ $showCreateForm ? 'createClient' : 'updateClient' }}">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" wire:model="form.name"
                               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
                        <select wire:model="form.team_id"
                                class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select a team</option>
                            @foreach($availableTeams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea wire:model="form.description"
                                  rows="3"
                                  class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    @if($noSlackWorkspace)
                        <div class="col-span-2">
                            <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded">
                                No Slack workspace connected. Please configure a Slack workspace to enable channel mapping.
                            </div>
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Slack Channel</label>
                            <select wire:model="form.slack_channel_id"
                                    wire:change="$set('form.slack_channel_name', $event.target.selectedOptions[0].text)"
                                    class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Select a channel</option>
                                @foreach($availableSlackChannels as $channel)
                                    <option value="{{ $channel['id'] }}">#{{ $channel['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Selected Channel</label>
                            <input type="text" wire:model="form.slack_channel_name"
                                   class="w-full p-3 border rounded-lg bg-gray-50" readonly>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" wire:click="cancel"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        {{ $showCreateForm ? 'Create Client' : 'Update Client' }}
                    </button>
                </div>

                @error('form')
                    <div class="text-red-600 mt-2">{{ $message }}</div>
                @enderror
            </form>
        </div>
    @endif

    <!-- Clients Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slack Channel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($clients as $client)
                        <tr class="{{ $client->trashed() ? 'bg-gray-100' : 'hover:bg-gray-50' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $client->name }}</div>
                                @if($client->description)
                                    <div class="text-sm text-gray-500">{{ Str::limit($client->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $client->team->name ?? 'No Team' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($client->slack_channel_name)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                        {{ $client->slack_channel_name }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">Not mapped</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($client->trashed())
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                        Deleted
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    @if(!$client->trashed())
                                        <button wire:click="editClient({{ $client->id }})"
                                                class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                        <button wire:click="deleteClient({{ $client->id }})"
                                                wire:confirm="Are you sure you want to delete this client?"
                                                class="text-red-600 hover:text-red-900">Delete</button>
                                    @else
                                        <button wire:click="restoreClient({{ $client->id }})"
                                                class="text-green-600 hover:text-green-900">Restore</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $clients->links() }}
        </div>
    </div>
</div>
