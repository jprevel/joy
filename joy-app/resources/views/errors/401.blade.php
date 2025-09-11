<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Joy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    Access Denied
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $message ?? 'You do not have permission to access this resource.' }}
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        Invalid Access Link
                    </h3>
                    <p class="text-sm text-gray-600 mb-6">
                        The access link you're using may be expired, invalid, or you may not have the necessary permissions.
                    </p>
                    
                    <div class="space-y-3">
                        <p class="text-xs text-gray-500">
                            If you believe this is an error, please:
                        </p>
                        <ul class="text-xs text-gray-600 text-left space-y-1">
                            <li>• Check that you're using the most recent link</li>
                            <li>• Verify the link hasn't expired</li>
                            <li>• Contact your project manager for a new access link</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <p class="text-xs text-gray-400">
                    Joy Content Calendar System
                </p>
            </div>
        </div>
    </div>
</body>
</html>