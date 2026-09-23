<x-guest-layout :title="__('Verifikasi Email')" :description="__('Verifikasi alamat email Anda')">
    <div class="mb-4 text-sm text-base-content/70">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success mb-4 text-sm">
            <span>{{ __('A new verification link has been sent to the email address you provided during registration.') }}</span>
        </div>
    @endif

    <div x-data="{
        timeLeft: 0,
        isSubmitting: false,
        timer: null,
        storageKey: 'dokuflow:email_verify_cooldown_{{ auth()->id() ?? 'user' }}',
        init() {
            const justSent = {{ session('status') == 'verification-link-sent' ? 'true' : 'false' }};
            const savedUntil = parseInt(localStorage.getItem(this.storageKey) || '0', 10);
            const now = Date.now();

            if (justSent && savedUntil <= now) {
                this.startCountdown(60);
            } else if (savedUntil > now) {
                const remaining = Math.ceil((savedUntil - now) / 1000);
                this.startCountdown(remaining);
            }
        },
        startCountdown(seconds) {
            this.timeLeft = seconds;
            const targetTime = Date.now() + (seconds * 1000);
            localStorage.setItem(this.storageKey, targetTime.toString());
            
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                const now = Date.now();
                const remaining = Math.ceil((targetTime - now) / 1000);
                if (remaining <= 0) {
                    this.timeLeft = 0;
                    clearInterval(this.timer);
                    localStorage.removeItem(this.storageKey);
                } else {
                    this.timeLeft = remaining;
                }
            }, 1000);
        },
        handleSubmit(e) {
            if (this.timeLeft > 0 || this.isSubmitting) {
                e.preventDefault();
                return;
            }
            this.isSubmitting = true;
            this.startCountdown(60);
        }
    }" class="space-y-4">
        <div class="mt-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <form method="POST" action="{{ route('verification.send') }}" @submit="handleSubmit($event)">
                @csrf
                <button type="submit" 
                        id="btn-resend-verification"
                        class="btn btn-primary transition-all duration-200 gap-2 shadow-xs"
                        :class="{ 'opacity-60 cursor-not-allowed': timeLeft > 0 || isSubmitting }"
                        :disabled="timeLeft > 0 || isSubmitting">
                    <template x-if="isSubmitting">
                        <span class="loading loading-spinner loading-xs"></span>
                    </template>
                    <template x-if="!isSubmitting && timeLeft === 0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </template>
                    <template x-if="!isSubmitting && timeLeft > 0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin-slow text-base-100/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>

                    <span>{{ __('Resend Verification Email') }}</span>

                    <template x-if="timeLeft > 0">
                        <span class="badge badge-sm badge-neutral font-mono font-bold text-xs" x-text="timeLeft + 's'"></span>
                    </template>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn){ btn.disabled = true; btn.classList.add('loading'); }">
                @csrf
                <button type="submit" class="btn btn-ghost text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>

        <template x-if="timeLeft > 0">
            <div class="p-3 rounded-xl bg-base-200/60 border border-base-300 text-xs text-base-content/70 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ __('Harap tunggu') }} <strong class="font-mono text-primary font-bold" x-text="timeLeft"></strong> {{ __('detik sebelum dapat mengirim ulang link verifikasi email.') }}</span>
            </div>
        </template>
    </div>
</x-guest-layout>
