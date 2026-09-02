<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'DokuFlow') }} — Document Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">

    <!-- New Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>(function(){var t=sessionStorage.getItem('theme:v2'),m=window.matchMedia('(prefers-color-scheme: dark)'),d=(t==='dark')||(t!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',d?'dark':'light')})()</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
        }
        
        /* Minimalist Grid Pattern */
        .bg-grid-pattern {
            background-image: linear-gradient(to right, rgba(0,0,0,0.12) 1px, transparent 1px), linear-gradient(to bottom, rgba(0,0,0,0.12) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        [data-theme='dark'] .bg-grid-pattern {
            background-image: linear-gradient(to right, rgba(255,255,255,0.08) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.08) 1px, transparent 1px);
        }
        
        /* Typing Animation */
        .typing-wrapper {
            display: inline-grid;
            text-align: left;
        }
        .typing-wrapper::after {
            content: "From draft to distribution.";
            visibility: hidden;
            grid-area: 1 / 1;
            white-space: nowrap;
        }
        .typing-text {
            grid-area: 1 / 1;
            justify-self: start;
            width: fit-content;
            white-space: nowrap;
            border-right: 2px solid currentColor;
            padding-right: 4px;
            animation: blink-caret 0.75s step-end infinite;
        }
        @keyframes blink-caret {
            from, to { border-color: transparent; }
            50% { border-color: currentColor; }
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 text-base-content antialiased flex flex-col selection:bg-primary selection:text-primary-content">

    <!-- Nav -->
    @if (Route::has('login'))
    <nav class="sticky top-0 z-50 w-full border-b border-base-200 bg-base-100/80 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2">
                    <x-application-logo class="h-8 w-8 text-primary" />
                    <span class="text-xl font-bold tracking-tight">{{ config('app.name', 'DokuFlow') }}</span>
                </div>
                <div class="flex items-center gap-3 sm:gap-6">
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <x-language-toggle />
                        <x-theme-toggle />
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-xs sm:text-sm font-semibold hover:text-primary transition-colors">{{ __('Dashboard') }}</a>
                        @else
                            <a href="{{ route('login') }}" class="text-xs sm:text-sm font-semibold hover:text-primary transition-colors">{{ __('Log in') }}</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-xs sm:text-sm font-semibold bg-primary text-primary-content px-3 sm:px-4 py-1.5 sm:py-2 rounded-full hover:bg-primary/90 transition-colors shadow-sm">{{ __('Sign up') }}</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>
    @endif

    <!-- Hero Section -->
    <main class="flex-1 relative flex flex-col items-center justify-center py-16 sm:py-24 px-4 sm:px-6 lg:px-8 overflow-hidden bg-grid-pattern">
        <!-- Radial gradient to highlight the center and fade the grid -->
        <div class="absolute inset-0 bg-base-100 [mask-image:radial-gradient(ellipse_at_center,transparent_20%,black_70%)] pointer-events-none"></div>
        
        <div class="relative z-10 w-full max-w-4xl mx-auto text-center flex flex-col items-center">
            
            <div class="inline-flex items-center gap-2 px-3 py-1 mb-6 sm:mb-8 rounded-full border border-base-300 bg-base-200/50 text-xs font-semibold uppercase tracking-wider text-base-content/80">
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                {{ __('DokuFlow Workflow Engine') }}
            </div>
            
            <h1 class="text-3xl sm:text-5xl md:text-7xl font-extrabold tracking-tighter leading-[1.1] sm:leading-[1.05] mb-6">
                {{ __('Control your documents.') }} <br class="hidden sm:block"/>
                <span class="typing-wrapper text-base-content/60 transition-all duration-300 hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-secondary hover:-translate-y-1 hover:drop-shadow-sm cursor-default">
                    <span class="typing-text" id="typewriter-text"></span>
                </span>
            </h1>
            
            <p class="mt-4 max-w-2xl text-lg sm:text-xl text-base-content/60 font-medium mb-10">
                {{ __('A unified platform to create, review, approve, and distribute your corporate documents with complete audit trails and automated workflows.') }}
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto justify-center">
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex justify-center items-center gap-2 px-8 py-3.5 text-sm font-bold bg-primary text-primary-content rounded-full hover:bg-primary/90 transition-transform hover:scale-105 active:scale-95 shadow-xl shadow-primary/20">
                        {{ __('Go to Workspace') }}
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex justify-center items-center gap-2 px-8 py-3.5 text-sm font-bold bg-primary text-primary-content rounded-full hover:bg-primary/90 transition-transform hover:scale-105 active:scale-95 shadow-xl shadow-primary/20">
                        {{ __('Start for free') }}
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex justify-center items-center gap-2 px-8 py-3.5 text-sm font-bold bg-base-200 text-base-content rounded-full border border-base-300 hover:bg-base-300 transition-colors">
                        {{ __('Sign in') }}
                    </a>
                @endauth
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-8 text-center text-sm font-medium text-base-content/40 border-t border-base-200 bg-base-100 z-10 relative">
        &copy; {{ date('Y') }} {{ config('app.name', 'DokuFlow') }}. {{ __('All rights reserved.') }}
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const text = @json(__('From draft to distribution.'));
            const el = document.getElementById("typewriter-text");
            let i = 0;
            const speed = 75; // milliseconds per character
            
            function type() {
                if (i < text.length) {
                    el.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            
            // Start the typing effect with a slight delay
            setTimeout(type, 500);
        });
    </script>
</body>
</html>