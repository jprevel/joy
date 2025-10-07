<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal Access - Joy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .animated-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            animation: gradientShift 8s ease infinite;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full flex">
        <!-- Left side - Branding -->
        <div class="flex-1 animated-bg flex items-center justify-center">
            <div class="text-center text-white">
                <div class="flex justify-center mb-6">
                    <img src="{{ asset('MM_logo_200px.png') }}" alt="MajorMajor" class="h-16 w-auto">
                </div>
                <h1 class="text-4xl font-bold mb-4">Joy</h1>
                <p class="text-xl opacity-90">Content Calendar Management</p>
                <div class="mt-8 flex justify-center">
                    <div class="bg-white/20 backdrop-blur-sm rounded-lg p-6 max-w-md">
                        <h2 class="text-lg font-semibold mb-2">Secure Client Access</h2>
                        <p class="text-sm opacity-80">Review and approve your content with our magic link system</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Access Form -->
        <div class="flex-1 flex items-center justify-center bg-gray-50">
            <div class="max-w-md w-full space-y-8 p-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 text-center">
                        Access Your Content
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Enter your PIN to continue
                    </p>
                </div>

                <form method="POST" action="{{ route('portal.dashboard', $token) }}" class="mt-8 space-y-6">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="pin" class="sr-only">PIN</label>
                        <input id="pin"
                               name="pin"
                               type="password"
                               maxlength="6"
                               class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 placeholder-gray-500 text-gray-900 text-center text-2xl tracking-widest focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10"
                               placeholder="Enter PIN"
                               required>
                    </div>

                    @if ($errors->any())
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Access Denied</h3>
                                    <p class="mt-1 text-sm text-red-700">
                                        {{ $errors->first() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <button type="submit"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Access Portal
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="text-xs text-gray-500">
                            Having trouble? Contact your account manager for assistance.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus PIN input
        document.getElementById('pin').focus();

        // Auto-submit when PIN is 6 digits (if needed)
        document.getElementById('pin').addEventListener('input', function(e) {
            const pin = e.target.value;
            if (pin.length === 6 && /^\d{6}$/.test(pin)) {
                // Optional: auto-submit after short delay
                // setTimeout(() => e.target.form.submit(), 500);
            }
        });
    </script>
</body>
</html>