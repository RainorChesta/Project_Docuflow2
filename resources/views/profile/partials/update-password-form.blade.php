<section class="space-y-6">
    <header class="border-b border-base-200 pb-3 sm:pb-4">
        <h2 class="text-base sm:text-lg font-bold text-base-content flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 sm:w-5 sm:h-5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span>{{ __('Perbarui Kata Sandi') }}</span>
        </h2>
        <p class="mt-0.5 sm:mt-1 text-[11px] sm:text-xs text-base-content/60">
            {{ __('Pastikan akun Anda menggunakan kata sandi yang panjang dan kuat untuk menjaga keamanan data dokumen.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div class="form-control w-full max-w-md space-y-1">
            <x-input-label for="update_password_current_password" :value="__('Kata Sandi Saat Ini')" class="font-semibold text-xs text-base-content/80" />
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </span>
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="input input-bordered w-full pl-9 rounded-xl font-medium" autocomplete="current-password" placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 max-w-2xl">
            <div class="form-control w-full space-y-1">
                <x-input-label for="update_password_password" :value="__('Kata Sandi Baru')" class="font-semibold text-xs text-base-content/80" />
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                    <x-text-input id="update_password_password" name="password" type="password" class="input input-bordered w-full pl-9 rounded-xl font-medium" autocomplete="new-password" placeholder="Minimal 8 karakter" />
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
            </div>

            <div class="form-control w-full space-y-1">
                <x-input-label for="update_password_password_confirmation" :value="__('Konfirmasi Kata Sandi Baru')" class="font-semibold text-xs text-base-content/80" />
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="input input-bordered w-full pl-9 rounded-xl font-medium" autocomplete="new-password" placeholder="Ketik ulang kata sandi" />
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn btn-primary rounded-xl px-5 font-semibold gap-2 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ __('Simpan Kata Sandi') }}</span>
            </button>

            @if (session('status') === 'password-updated')
                <div x-data="{ show: true }"
                     x-show="show"
                     x-transition
                     x-init="setTimeout(() => show = false, 3000)"
                     class="flex items-center gap-1.5 text-xs text-success font-semibold">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ __('Kata sandi berhasil diperbarui.') }}</span>
                </div>
            @endif
        </div>
    </form>
</section>
