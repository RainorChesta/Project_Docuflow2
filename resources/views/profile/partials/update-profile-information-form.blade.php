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

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="profileInfoForm()">
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
                           @change="handleFileChange($event)" />
                    <input type="hidden" name="remove_profile_picture" :value="removeAvatar ? '1' : '0'">
                    
                    <div class="flex items-center gap-2 pt-0.5">
                        {{-- Tombol hapus foto tersimpan di database/storage --}}
                        <template x-if="savedAvatar && !hasFileSelected">
                            <button type="button"
                                    class="btn btn-ghost btn-xs text-error gap-1"
                                    @click="$dispatch('open-modal', 'confirm-delete-avatar')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ __('Hapus Foto') }}
                            </button>
                        </template>

                        {{-- Tombol batalkan pilihan file baru yang belum disimpan --}}
                        <template x-if="hasFileSelected">
                            <button type="button"
                                    class="btn btn-ghost btn-xs text-warning gap-1"
                                    @click="cancelFileSelection()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                {{ __('Batalkan Pilihan') }}
                            </button>
                        </template>
                    </div>

                    <p class="text-xs text-base-content/50">{{ __('Format: JPG, PNG, WEBP. Maks: 2MB.') }}</p>
                    <p x-show="deleteSuccessMessage" x-text="deleteSuccessMessage" class="text-xs text-success font-medium" x-transition></p>
                    <p x-show="deleteErrorMessage" x-text="deleteErrorMessage" class="text-xs text-error font-medium" x-transition></p>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('profile_picture')" />
        </div>

        {{-- Modal Konfirmasi Hapus Foto Profil --}}
        <x-modal name="confirm-delete-avatar" :show="false" maxWidth="sm">
            <div class="p-4 sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-error/10 text-error flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-base-content">{{ __('Hapus Foto Profil') }}</h3>
                        <p class="text-xs text-base-content/60">{{ __('Tindakan ini tidak dapat dibatalkan.') }}</p>
                    </div>
                </div>

                <p class="mt-3 text-sm text-base-content/70">
                    {{ __('Apakah Anda yakin ingin menghapus foto profil Anda? Foto akan langsung dihapus dari informasi profil dan sistem.') }}
                </p>

                <div x-show="deleteErrorMessage" class="mt-3 alert alert-error text-xs shadow-sm" x-text="deleteErrorMessage"></div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" :disabled="isDeleting" x-on:click="$dispatch('close-modal', 'confirm-delete-avatar')">
                        {{ __('Batal') }}
                    </button>
                    <button type="button" class="btn btn-error btn-sm gap-1.5" :disabled="isDeleting" @click="deleteSavedAvatar()">
                        <span x-show="isDeleting" class="loading loading-spinner loading-xs"></span>
                        <svg x-show="!isDeleting" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span x-text="isDeleting ? '{{ __('Menghapus...') }}' : '{{ __('Hapus Foto') }}'"></span>
                    </button>
                </div>
            </div>
        </x-modal>

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

<script>
function profileInfoForm() {
    return {
        avatarPreview: @json($user->avatar_url),
        savedAvatar: @json($user->avatar_url),
        removeAvatar: false,
        hasFileSelected: false,
        isDeleting: false,
        deleteSuccessMessage: '',
        deleteErrorMessage: '',

        handleFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.removeAvatar = false;
                this.hasFileSelected = true;
                this.avatarPreview = URL.createObjectURL(file);
            } else {
                this.hasFileSelected = false;
                this.avatarPreview = this.removeAvatar ? null : this.savedAvatar;
            }
        },

        cancelFileSelection() {
            const input = document.getElementById('profile_picture');
            if (input) input.value = '';
            this.hasFileSelected = false;
            this.avatarPreview = this.removeAvatar ? null : this.savedAvatar;
        },

        async deleteSavedAvatar() {
            if (this.isDeleting) return;

            this.isDeleting = true;
            this.deleteErrorMessage = '';
            this.deleteSuccessMessage = '';

            try {
                const res = await fetch('{{ route('profile.avatar.destroy') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ _method: 'DELETE' })
                });

                const data = await res.json();

                if (data.success) {
                    this.savedAvatar = null;
                    this.avatarPreview = null;
                    this.removeAvatar = true;
                    this.hasFileSelected = false;
                    const input = document.getElementById('profile_picture');
                    if (input) input.value = '';

                    this.deleteSuccessMessage = data.message || @json(__('Foto profil berhasil dihapus.'));
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-delete-avatar' }));

                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.deleteErrorMessage = data.message || @json(__('Gagal menghapus foto profil.'));
                }
            } catch (err) {
                console.error('Delete avatar error:', err);
                this.deleteErrorMessage = @json(__('Terjadi kesalahan jaringan saat menghapus foto profil.'));
            } finally {
                this.isDeleting = false;
            }
        }
    };
}
</script>
