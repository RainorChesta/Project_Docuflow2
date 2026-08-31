<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Page Not Found') }} - {{ config('app.name', 'DokuFlow') }}</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center p-4">
    <div class="card bg-base-100 shadow-xl max-w-md w-full text-center p-6 sm:p-8 space-y-6">
        <div class="inline-flex items-center justify-center relative mx-auto">
            <div class="w-20 h-20 rounded-2xl bg-error/10 border border-error/20 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div class="absolute -bottom-1 -right-1 bg-error text-error-content p-1.5 rounded-lg shadow-md border-2 border-base-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
        </div>
        
        <div class="space-y-4">
            <h1 class="text-5xl font-extrabold text-error opacity-20">
                404
            </h1>
            <h2 class="text-xl font-bold text-base-content">{{ __('Page Not Found') }}</h2>
            
            <div class="bg-base-200/50 p-3 rounded-lg border border-base-300 text-left mx-auto">
                <p class="text-base-content/80 text-sm font-medium">
                    {{ __('The page you are looking for could not be found.') }}
                </p>
                <ul class="list-disc list-inside text-base-content/60 text-xs mt-2 space-y-1">
                    <li>{{ __('The URL might be misspelled or invalid.') }}</li>
                    <li>{{ __('The page may have been deleted or moved.') }}</li>
                    <li>{{ __('If you clicked a link, it may be outdated.') }}</li>
                </ul>
            </div>
        </div>

        <div>
            <a href="{{ url('/') }}" class="btn btn-primary w-full">{{ __('Back to Home') }}</a>
        </div>
    </div>
</body>
</html>
