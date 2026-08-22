@if (session('urgent_expiring_count'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => show = false, 7000)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8 sm:translate-x-0 sm:translate-y-4 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-x-0 sm:translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 sm:scale-100 translate-x-0"
         x-transition:leave-end="opacity-0 sm:scale-95 translate-x-8"
         class="toast toast-top toast-end z-[100] mt-16 sm:mt-2 mr-2">
        
        <div class="alert bg-base-100 border border-warning/50 text-base-content shadow-2xl flex flex-row items-start gap-4 w-80 sm:w-96 rounded-xl relative overflow-hidden">
            <!-- Decorative left border -->
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-warning"></div>
            
            <div class="w-10 h-10 rounded-full bg-warning/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="text-warning h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            
            <div class="flex-1 min-w-0 py-1">
                <h3 class="font-bold text-sm text-base-content">{{ __('Perhatian: Dokumen Mendesak') }}</h3>
                <p class="text-xs text-base-content/70 mt-1 leading-relaxed">
                    {{ __('Terdapat :count dokumen yang akan kedaluwarsa dalam 3 hari ke depan.', ['count' => session('urgent_expiring_count')]) }}
                </p>
                <div class="mt-3">
                    <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-warning hover:text-warning/80 transition-colors flex items-center gap-1">
                        {{ __('Lihat Daftar Dokumen') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
            
            <button @click="show = false" class="absolute top-2 right-2 text-base-content/40 hover:text-base-content/80 transition-colors p-1" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif
