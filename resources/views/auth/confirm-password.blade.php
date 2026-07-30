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
                {{ __('Confirm') }}
            </button>
        </div>
    </form>
</x-guest-layout>
