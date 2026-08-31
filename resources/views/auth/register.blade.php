<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'DokuFlow') }} — {{ __('Daftar') }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>(function(){var t=localStorage.getItem('theme:v2'),m=window.matchMedia('(prefers-color-scheme: dark)'),d=(t==='dark')||(t!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',d?'dark':'light')})()</script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-base-100 text-base-content antialiased selection:bg-primary selection:text-primary-content relative overflow-x-hidden">

    <!-- Decorative background elements -->
    <div class="fixed top-0 left-0 w-full h-96 bg-gradient-to-b from-primary/10 to-transparent pointer-events-none z-0"></div>
    
    <!-- Top-right controls -->
    <div class="absolute top-6 right-6 flex items-center gap-2 z-50">
        <x-language-toggle />
        <div class="bg-base-100/50 backdrop-blur-sm rounded-full">
            <x-theme-toggle />
        </div>
    </div>

    <!-- Centered Scrollable Wrapper -->
    <div class="min-h-screen flex flex-col p-4">
        <div class="w-full max-w-[460px] relative z-10 m-auto pt-16 pb-8">
            
            <!-- Logo & Header -->
            <div class="text-center mb-8">
                <a href="/" class="inline-flex items-center justify-center p-3 bg-base-100 rounded-2xl shadow-sm border border-base-300 mb-6 hover:scale-105 transition-transform">
                    <x-application-logo class="w-10 h-10 text-primary" />
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-base-content mb-2">{{ __('Buat Akun Baru') }}</h1>
                <p class="text-sm text-base-content/60">{{ __('Isi data diri Anda di bawah ini untuk memulai') }}</p>
            </div>

            <!-- Main Card -->
            <div class="bg-base-100 rounded-3xl shadow-2xl shadow-base-content/5 border border-base-300 overflow-hidden">
                <div class="p-8">
                    <form method="POST" action="{{ route('register') }}" class="space-y-5">
                        @csrf

                        <!-- Name Input -->
                        <div class="space-y-1.5">
                            <label for="name" class="text-sm font-semibold text-base-content/80 block">{{ __('Nama Lengkap') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/40">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                                </div>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="John Doe"
                                    class="w-full pl-11 pr-4 py-3 bg-base-200/50 border border-base-300 rounded-xl text-sm focus:bg-base-100 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none" />
                            </div>
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <!-- Email Input -->
                        <div class="space-y-1.5">
                            <label for="email" class="text-sm font-semibold text-base-content/80 block">{{ __('Alamat Email') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/40">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" /><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" /></svg>
                                </div>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@company.com"
                                    class="w-full pl-11 pr-4 py-3 bg-base-200/50 border border-base-300 rounded-xl text-sm focus:bg-base-100 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none" />
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <!-- Password Input -->
                        <div class="space-y-1.5" x-data="{ show: false }">
                            <label for="password" class="text-sm font-semibold text-base-content/80 block">{{ __('Kata Sandi') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/40">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" /></svg>
                                </div>
                                <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="new-password" placeholder="••••••••"
                                    class="w-full pl-11 pr-11 py-3 bg-base-200/50 border border-base-300 rounded-xl text-sm focus:bg-base-100 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none" />
                                
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-base-content/40 hover:text-base-content focus:outline-none">
                                    <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="show" style="display:none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>
                        
                        <!-- Confirm Password Input -->
                        <div class="space-y-1.5" x-data="{ show: false }">
                            <label for="password_confirmation" class="text-sm font-semibold text-base-content/80 block">{{ __('Konfirmasi Kata Sandi') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/40">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" /></svg>
                                </div>
                                <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••"
                                    class="w-full pl-11 pr-11 py-3 bg-base-200/50 border border-base-300 rounded-xl text-sm focus:bg-base-100 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none" />
                                
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-base-content/40 hover:text-base-content focus:outline-none">
                                    <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="show" style="display:none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                        </div>

                        <button type="submit" class="w-full py-3 mt-4 bg-primary hover:bg-primary/90 text-primary-content rounded-xl font-semibold text-sm transition-all hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0">
                            {{ __('Daftar Akun') }}
                        </button>
                    </form>
                </div>
                
                <div class="p-6 border-t border-base-300 text-center">
                    <p class="text-sm text-base-content/60">
                        {{ __('Sudah punya akun?') }} 
                        <a href="{{ route('login') }}" class="font-semibold text-primary hover:underline">{{ __('Masuk di sini') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
