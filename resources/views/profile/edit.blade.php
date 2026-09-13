<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2.5 sm:gap-3">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-base-content leading-tight">
                    {{ __('Profil Pengguna') }}
                </h2>
                <p class="text-[11px] sm:text-xs text-base-content/60 mt-0.5">
                    {{ __('Kelola informasi identitas, tanda tangan digital, dan keamanan akun Anda.') }}
                </p>
            </div>
            
            {{-- TTD Badge Status in Header --}}
            <div>
                @if(auth()->user()->hasSignature('original'))
                    <span class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-xl text-[11px] sm:text-xs font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shadow-2xs">
                        <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ __('TTD Original Aktif') }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-xl text-[11px] sm:text-xs font-semibold bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 shadow-2xs">
                        <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-amber-500"></span>
                        <span>{{ __('Wajib Membuat TTD') }}</span>
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $user = $user ?? auth()->user();
        $user->loadMissing(['divisions', 'companies', 'branches', 'signatures.company']);
    @endphp

    <div class="py-6" x-data="{
        activeTab: (window.location.hash === '#signature-section' || window.location.hash === '#ttd') 
            ? 'signatures' 
            : (window.location.hash === '#profile' 
                ? 'profile' 
                : (window.location.hash === '#security' || {{ $errors->updatePassword->isNotEmpty() || $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} 
                    ? 'security' 
                    : 'signatures')),
        setTab(tab) {
            this.activeTab = tab;
            if (tab === 'signatures') {
                setTimeout(() => window.dispatchEvent(new Event('resize')), 80);
            }
            const targetHash = tab === 'signatures' ? 'signature-section' : tab;
            if (history.pushState) {
                history.pushState(null, null, '#' + targetHash);
            } else {
                window.location.hash = '#' + targetHash;
            }
        }
    }" x-init="
        window.addEventListener('hashchange', () => {
            const h = window.location.hash;
            if (h === '#signature-section' || h === '#ttd') activeTab = 'signatures';
            else if (h === '#profile') activeTab = 'profile';
            else if (h === '#security') activeTab = 'security';
        });
    ">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- 1. Modern Hero Profile Card --}}
            <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-base-100 border border-base-300 shadow-sm p-4 sm:p-6 md:p-8">
                {{-- Decorative background glow --}}
                <div class="absolute -top-24 -right-24 w-80 h-80 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex items-center gap-3 sm:gap-4 md:gap-6">
                    {{-- User Identity --}}
                    <div class="relative shrink-0">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-12 h-12 sm:w-16 sm:h-16 md:w-20 md:h-20 rounded-full object-cover border-2 border-base-300 shadow-sm ring-2 sm:ring-4 ring-base-100">
                        @else
                            <div class="w-12 h-12 sm:w-16 sm:h-16 md:w-20 md:h-20 rounded-full bg-gradient-to-br from-primary/20 via-primary/10 to-base-200 text-primary flex items-center justify-center font-extrabold text-sm sm:text-lg md:text-2xl border-2 border-base-300 shadow-sm ring-2 sm:ring-4 ring-base-100">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                        @endif
                        <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 sm:w-4 sm:h-4 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-rose-500' }} border-2 border-base-100" title="{{ $user->is_active ? 'Akun Aktif' : 'Akun Non-Aktif' }}"></span>
                    </div>

                    <div class="space-y-0.5 sm:space-y-1.5 min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <h1 class="text-base sm:text-lg md:text-2xl font-bold text-base-content leading-tight truncate">{{ $user->name }}</h1>
                            <span class="badge {{ $user->system_role === 'admin' ? 'badge-accent' : ($user->system_role === 'direktur' ? 'badge-info' : ($user->system_role === 'head' ? 'badge-warning' : 'badge-ghost')) }} badge-xs sm:badge-sm uppercase font-bold tracking-wider shrink-0">
                                {{ $user->system_role }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-y-0.5 gap-x-2.5 sm:gap-x-4 text-[11px] sm:text-xs md:text-sm text-base-content/70">
                            <span class="flex items-center gap-1 sm:gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="truncate">{{ $user->email }}</span>
                            </span>
                            @if($user->nip)
                                <span class="flex items-center gap-1 sm:gap-1.5 font-mono">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                    </svg>
                                    NIP: {{ $user->nip }}
                                </span>
                            @endif
                            @if($user->phone_number)
                                <span class="flex items-center gap-1 sm:gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    {{ $user->phone_number }}
                                </span>
                            @endif
                        </div>

                        {{-- Division & Company Pills --}}
                        <div class="flex flex-wrap items-center gap-1 pt-0.5 sm:pt-1">
                            @if($user->system_role === 'admin')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md sm:rounded-lg text-[10px] sm:text-xs font-semibold bg-accent/15 text-accent-content border border-accent/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 sm:w-3.5 sm:h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span>{{ __('Semua Perusahaan & Cabang') }}</span>
                                </span>
                            @else
                                @if($user->divisions->isNotEmpty())
                                    @foreach($user->divisions as $div)
                                        <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-md sm:rounded-lg text-[10px] sm:text-xs font-semibold bg-base-200 text-base-content border border-base-300">
                                            📁 {{ $div->code ?: $div->name }}
                                        </span>
                                    @endforeach
                                @elseif($user->division)
                                    <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-md sm:rounded-lg text-[10px] sm:text-xs font-semibold bg-base-200 text-base-content border border-base-300">
                                        📁 {{ $user->division->code ?: $user->division->name }}
                                    </span>
                                @endif

                                @if($user->companies->isNotEmpty())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md sm:rounded-lg text-[10px] sm:text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                                        🏢 {{ $user->companies->count() }} {{ __('Perusahaan') }} &bull; {{ $user->branches->count() }} {{ __('Cabang') }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Sleek Horizontal Scrolling Navigation Bar --}}
            <div class="relative bg-base-200/50 dark:bg-base-200/30 p-1 sm:p-1.5 rounded-2xl border border-base-300 shadow-2xs backdrop-blur-md">
                <div class="flex items-center gap-1 sm:gap-2 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden scroll-smooth overscroll-x-contain py-0.5 px-0.5">
                    
                    {{-- Tab 1: Digital Signature --}}
                    <button type="button"
                            @click="setTab('signatures')"
                            :class="activeTab === 'signatures' ? 'bg-primary text-primary-content font-bold shadow-md shadow-primary/20 scale-[1.01]' : 'text-base-content/70 hover:text-base-content hover:bg-base-100 dark:hover:bg-base-200 font-medium'"
                            class="flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 shrink-0 select-none whitespace-nowrap cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span>{{ __('Tanda Tangan Digital (TTD)') }}</span>
                        @if(auth()->user()->hasSignature('original'))
                            <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                        @else
                            <span class="badge badge-warning badge-xs font-bold shrink-0">!</span>
                        @endif
                    </button>

                    {{-- Tab 2: Informasi Akun --}}
                    <button type="button"
                            @click="setTab('profile')"
                            :class="activeTab === 'profile' ? 'bg-primary text-primary-content font-bold shadow-md shadow-primary/20 scale-[1.01]' : 'text-base-content/70 hover:text-base-content hover:bg-base-100 dark:hover:bg-base-200 font-medium'"
                            class="flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 shrink-0 select-none whitespace-nowrap cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>{{ __('Informasi Akun') }}</span>
                    </button>

                    {{-- Tab 3: Keamanan & Akun --}}
                    <button type="button"
                            @click="setTab('security')"
                            :class="activeTab === 'security' ? 'bg-primary text-primary-content font-bold shadow-md shadow-primary/20 scale-[1.01]' : 'text-base-content/70 hover:text-base-content hover:bg-base-100 dark:hover:bg-base-200 font-medium'"
                            class="flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 shrink-0 select-none whitespace-nowrap cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>{{ __('Keamanan & Akun') }}</span>
                        @if($errors->updatePassword->isNotEmpty() || $errors->userDeletion->isNotEmpty())
                            <span class="badge badge-error badge-xs font-bold shrink-0">!</span>
                        @endif
                    </button>

                </div>
            </div>

            {{-- 3. Tab Contents --}}

            {{-- TAB 1: TTD DIGITAL --}}
            <div x-show="activeTab === 'signatures'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" id="signature-section">
                @include('profile.partials.signature-pad-form')
            </div>

            {{-- TAB 2: INFORMASI AKUN --}}
            <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                <div class="card bg-base-100 border border-base-300 shadow-sm rounded-3xl">
                    <div class="card-body p-6 sm:p-8">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            {{-- TAB 3: KEAMANAN & PASSWORD --}}
            <div x-show="activeTab === 'security'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                {{-- Update Password Card --}}
                <div class="card bg-base-100 border border-base-300 shadow-sm rounded-3xl">
                    <div class="card-body p-6 sm:p-8">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                {{-- Delete User Card (Danger Zone) --}}
                <div class="card bg-base-100 border border-error/30 shadow-sm rounded-3xl">
                    <div class="card-body p-6 sm:p-8">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
