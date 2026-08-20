<section>
    <header>
        <h2 class="text-lg font-medium text-base-content">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-base-content/60">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="{ avatarPreview: '{{ $user->avatar_url }}', removeAvatar: false }">
        @csrf
        @method('patch')

        {{-- Profile Picture Upload --}}
        <div class="form-control w-full">
            <x-input-label :value="__('Profile Picture')" />
            <div class="mt-2 flex items-center gap-4">
                <div class="relative">
                    <template x-if="avatarPreview && !removeAvatar">
                        <img :src="avatarPreview" alt="{{ $user->name }}" class="h-16 w-16 rounded-full object-cover border-2 border-base-300 shadow-sm">
                    </template>
                    <template x-if="!avatarPreview || removeAvatar">
                        <div class="h-16 w-16 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold text-xl border-2 border-base-300 shadow-sm">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    </template>
                </div>

                <div class="space-y-1">
                    <input type="file"
                           name="profile_picture"
                           id="profile_picture"
                           accept="image/png,image/jpeg,image/jpg,image/webp"
                           class="file-input file-input-bordered file-input-sm w-full max-w-xs"
                           @change="const file = $event.target.files[0]; if(file) { removeAvatar = false; avatarPreview = URL.createObjectURL(file); }" />
                    <input type="hidden" name="remove_profile_picture" :value="removeAvatar ? '1' : '0'">
                    
                    @if($user->profile_picture)
                        <div>
                            <button type="button"
                                    class="btn btn-ghost btn-xs text-error"
                                    x-show="!removeAvatar"
                                    @click="removeAvatar = true; avatarPreview = null; document.getElementById('profile_picture').value = ''">
                                {{ __('Hapus Foto') }}
                            </button>
                        </div>
                    @endif
                    <p class="text-xs text-base-content/50">{{ __('Format: JPG, PNG, WEBP. Maks: 2MB.') }}</p>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('profile_picture')" />
        </div>

        <div class="form-control w-full">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="input input-bordered w-full mt-1" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div class="form-control w-full">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="input input-bordered w-full mt-1" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-base-content/70">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="link link-primary text-sm">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-success">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                {{ __('Save') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-success"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
