@if(auth()->check())
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-base-content leading-tight">
            {{ __('Page Not Found') }}
        </h2>
    </x-slot>

    <div class="py-12 px-4">
        <div class="max-w-xl mx-auto text-center space-y-6">
            <div class="inline-flex items-center justify-center relative mb-2">
                <!-- Document Base -->
                <div class="w-24 h-24 rounded-2xl bg-error/10 border border-error/20 flex items-center justify-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <!-- Cross Badge Overlay -->
                <div class="absolute -bottom-1 -right-1 bg-error text-error-content p-2 rounded-xl shadow-md border-2 border-base-100 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
            
            <div class="space-y-2">
                <h1 class="text-2xl sm:text-3xl font-bold text-base-content">
                    {{ __('Page Not Found') }}
                </h1>
                <p class="text-base-content/70 text-sm sm:text-base leading-relaxed">
                    {{ __('Sorry, the document you are looking for could not be found or the document URL is invalid.') }}
                </p>
                <p class="text-xs text-base-content/50">
                    {{ __('Please check the URL or scan the correct document QR Code.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 pt-4">
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm sm:btn-md gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    {{ __('Back to Home') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
@else
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Page Not Found') }} - {{ config('app.name', 'DokuFlow') }}</title>
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
        <div class="space-y-2">
            <h1 class="text-xl font-bold text-base-content">{{ __('Page Not Found') }}</h1>
            <p class="text-base-content/70 text-sm">
                {{ __('Sorry, the document you are looking for could not be found or the document URL is invalid.') }}
            </p>
            <p class="text-xs text-base-content/50 mt-1">
                {{ __('Please check the URL or scan the correct document QR Code.') }}
            </p>
        </div>
        <div>
            <a href="{{ url('/') }}" class="btn btn-primary w-full">{{ __('Back to Home') }}</a>
        </div>
    </div>
</body>
</html>
@endif
