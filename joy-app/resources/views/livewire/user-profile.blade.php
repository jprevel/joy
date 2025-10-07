<div class="max-w-3xl mx-auto p-6">
    @if($currentUser)
        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- Header -->
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">User Profile</h1>
                    <p class="text-gray-600 mt-1">Manage your account information</p>
                </div>

                <a href="{{ route('profile.edit') }}"
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Edit Profile
                </a>
            </div>

            <!-- Profile Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Name</label>
                            <p class="text-lg text-gray-900">{{ $currentUser->name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Email</label>
                            <p class="text-lg text-gray-900">{{ $currentUser->email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Role</label>
                            <p class="text-lg text-gray-900">{{ ucfirst($currentUser->role) }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Member Since</label>
                            <p class="text-lg text-gray-900">{{ $currentUser->created_at->format('M j, Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Account Details -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Details</h2>

                    <div class="space-y-4">
                        @if($currentUser->client)
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Associated Client</label>
                                <p class="text-lg text-gray-900">{{ $currentUser->client->name }}</p>
                            </div>
                        @endif

                        @if($currentUser->teams && $currentUser->teams->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Teams</label>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    @foreach($currentUser->teams as $team)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                            {{ $team->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Account Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Active
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Last Login</label>
                            <p class="text-lg text-gray-900">
                                {{ $currentUser->last_login_at ? $currentUser->last_login_at->diffForHumans() : 'Never' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-gray-500">Unable to load profile information.</p>
        </div>
    @endif
</div>