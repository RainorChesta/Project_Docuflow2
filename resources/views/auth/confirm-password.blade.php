<x-guest-layout title="Konfirmasi Password" description="Konfirmasi password Anda untuk melanjutkan">
    <div class="mb-4 text-sm text-base-content/70">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div class="form-control w-full">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="input input-bordered w-full mt-1"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-6">
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                {{ __('Confirm') }}
            </button>
        </div>
    </form>
</x-guest-layout>
