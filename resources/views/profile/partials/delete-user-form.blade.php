<section class="space-y-5">
    <header class="border-b border-error/20 pb-3 sm:pb-4">
        <div class="flex items-center gap-2 text-error font-bold text-base sm:text-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 sm:w-5 sm:h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h2>{{ __('Zona Berbahaya: Hapus Akun') }}</h2>
        </div>
        <p class="mt-0.5 sm:mt-1 text-[11px] sm:text-xs text-base-content/60">
            {{ __('Setelah akun Anda dihapus, semua sumber daya dan data terkait akan dihapus secara permanen dari sistem.') }}
        </p>
    </header>

    <div class="rounded-2xl bg-error/5 border border-error/20 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h4 class="text-sm font-bold text-base-content">{{ __('Hapus Akun Pengguna Secara Permanen') }}</h4>
            <p class="text-xs text-base-content/70 max-w-xl leading-relaxed">
                {{ __('Tindakan ini tidak dapat dibatalkan. Mohon pastikan seluruh dokumen penting telah diekspor sebelum melanjutkan.') }}
            </p>
        </div>

        <button type="button"
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                class="btn btn-error btn-sm rounded-xl gap-1.5 font-semibold shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>{{ __('Hapus Akun') }}</span>
        </button>
    </div>

    {{-- Modal Konfirmasi Hapus Akun --}}
    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable maxWidth="md">
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-xl bg-error/10 text-error flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-base-content">
                        {{ __('Konfirmasi Hapus Akun') }}
                    </h2>
                    <p class="text-xs text-base-content/60">{{ __('Tindakan ini bersifat permanen.') }}</p>
                </div>
            </div>

            <p class="mt-4 text-sm text-base-content/70 leading-relaxed">
                {{ __('Apakah Anda yakin ingin menghapus akun Anda? Silakan masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun ini secara permanen.') }}
            </p>

            <div class="mt-5 space-y-1">
                <x-input-label for="password" value="{{ __('Kata Sandi') }}" class="sr-only" />

                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="input input-bordered w-full pl-9 rounded-xl"
                        placeholder="{{ __('Masukkan kata sandi Anda') }}"
                    />
                </div>

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                <button type="button" class="btn btn-ghost btn-sm rounded-xl" x-on:click="$dispatch('close')">
                    {{ __('Batal') }}
                </button>

                <button type="submit" class="btn btn-error btn-sm rounded-xl gap-1.5 font-semibold">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>{{ __('Hapus Akun Permanen') }}</span>
                </button>
            </div>
        </form>
    </x-modal>
</section>
