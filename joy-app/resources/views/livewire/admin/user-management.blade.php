<div class="p-6">
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                <p class="text-gray-600 mt-2">Manage system users and their access</p>
            </div>

            <button wire:click="$set('showCreateForm', true)"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Create New User
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
            <h2 class="text-xl font-bold mb-4">{{ $showCreateForm ? 'Create New User' : 'Edit User' }}</h2>

            @if($editingSelf)
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4">
                    ⚠️ You are modifying your own account. Are you sure?
                </div>
            @endif

            <form wire:submit.prevent="{{ $showCreateForm ? 'createUser' : 'updateUser' }}">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                        <input type="text" wire:model="form.name"
                               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" wire:model="form.email"
                               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Password {{ $showEditForm ? '(Leave blank to keep current)' : '' }}
                        </label>
                        <input type="password" wire:model="form.password"
                               placeholder="{{ $showEditForm ? 'Leave blank to keep current password' : 'Password' }}"
                               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select wire:model="form.role"
                                class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select a role</option>
                            @foreach($availableRoles as $role)
                                <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" wire:click="cancel"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        {{ $showCreateForm ? 'Create User' : 'Update User' }}
                    </button>
                </div>

                @error('form')
                    <div class="text-red-600 mt-2">{{ $message }}</div>
                @enderror
            </form>
        </div>
    @endif

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                        <tr class="{{ $user->trashed() ? 'bg-gray-100' : 'hover:bg-gray-50' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $user->roles->first()?->name === 'admin' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $user->roles->first()?->name === 'agency' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $user->roles->first()?->name === 'client' ? 'bg-green-100 text-green-800' : '' }}">
                                    {{ ucfirst($user->roles->first()?->name ?? 'No Role') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->trashed())
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
                                    @if(!$user->trashed())
                                        <button wire:click="editUser({{ $user->id }})"
                                                class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                        <button wire:click="deleteUser({{ $user->id }})"
                                                wire:confirm="Are you sure you want to delete this user?"
                                                class="text-red-600 hover:text-red-900">Delete</button>
                                    @else
                                        <button wire:click="restoreUser({{ $user->id }})"
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
            {{ $users->links() }}
        </div>
    </div>
</div>
