<section class="space-y-6">
    <header class="border-b border-base-200 pb-3 sm:pb-4">
        <h2 class="text-base sm:text-lg font-bold text-base-content flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 sm:w-5 sm:h-5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span>{{ __('Informasi Profil') }}</span>
        </h2>
        <p class="mt-0.5 sm:mt-1 text-[11px] sm:text-xs text-base-content/60">
            {{ __('Perbarui data profil pribadi, foto akun, dan alamat email Anda.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6" x-data="profileInfoForm()">
        @csrf
        @method('patch')

        {{-- Profile Picture Upload Section --}}
        <div class="rounded-2xl border border-base-200 bg-base-200/30 p-5 space-y-3">
            <label class="text-xs font-semibold text-base-content/70 uppercase tracking-wider block">
                {{ __('Foto Profil') }}
            </label>
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <div class="relative shrink-0 group">
                    <template x-if="avatarPreview && !removeAvatar">
                        <img :src="avatarPreview" alt="{{ $user->name }}" class="h-20 w-20 rounded-full object-cover border-2 border-base-300 shadow-sm ring-2 ring-primary/20">
                    </template>
                    <template x-if="!avatarPreview || removeAvatar">
                        <div class="h-20 w-20 rounded-full bg-gradient-to-br from-primary/20 via-primary/10 to-base-200 text-primary flex items-center justify-center font-extrabold text-2xl border-2 border-base-300 shadow-sm">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    </template>
                </div>

                <div class="space-y-2 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="file"
                               name="profile_picture"
                               id="profile_picture"
                               accept="image/png,image/jpeg,image/jpg,image/webp"
                               class="file-input file-input-bordered file-input-sm w-full max-w-xs rounded-xl"
                               @change="handleFileChange($event)" />
                        <input type="hidden" name="remove_profile_picture" :value="removeAvatar ? '1' : '0'">

                        {{-- Tombol hapus foto tersimpan di database/storage --}}
                        <template x-if="savedAvatar && !hasFileSelected">
                            <button type="button"
                                    class="btn btn-ghost btn-sm text-error gap-1.5 rounded-xl"
                                    @click="$dispatch('open-modal', 'confirm-delete-avatar')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>{{ __('Hapus Foto') }}</span>
                            </button>
                        </template>

                        {{-- Tombol batalkan pilihan file baru yang belum disimpan --}}
                        <template x-if="hasFileSelected">
                            <button type="button"
                                    class="btn btn-ghost btn-sm text-warning gap-1.5 rounded-xl"
                                    @click="cancelFileSelection()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>{{ __('Batalkan Pilihan') }}</span>
                            </button>
                        </template>
                    </div>

                    <p class="text-xs text-base-content/50">{{ __('Format yang didukung: JPG, PNG, WEBP. Ukuran maksimal: 2MB.') }}</p>
                    <p x-show="deleteSuccessMessage" x-text="deleteSuccessMessage" class="text-xs text-success font-medium" x-transition></p>
                    <p x-show="deleteErrorMessage" x-text="deleteErrorMessage" class="text-xs text-error font-medium" x-transition></p>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('profile_picture')" />
        </div>

        {{-- Modal Konfirmasi Hapus Foto Profil --}}
        <x-modal name="confirm-delete-avatar" :show="false" maxWidth="sm">
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-error/10 text-error flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-base-content">{{ __('Hapus Foto Profil') }}</h3>
                        <p class="text-xs text-base-content/60">{{ __('Tindakan ini tidak dapat dibatalkan.') }}</p>
                    </div>
                </div>

                <p class="mt-4 text-sm text-base-content/70">
                    {{ __('Apakah Anda yakin ingin menghapus foto profil Anda? Foto akan langsung dihapus dari informasi profil dan sistem.') }}
                </p>

                <div x-show="deleteErrorMessage" class="mt-3 alert alert-error text-xs shadow-sm" x-text="deleteErrorMessage"></div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost btn-sm rounded-xl" :disabled="isDeleting" x-on:click="$dispatch('close-modal', 'confirm-delete-avatar')">
                        {{ __('Batal') }}
                    </button>
                    <button type="button" class="btn btn-error btn-sm rounded-xl gap-1.5" :disabled="isDeleting" @click="deleteSavedAvatar()">
                        <span x-show="isDeleting" class="loading loading-spinner loading-xs"></span>
                        <svg x-show="!isDeleting" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span x-text="isDeleting ? '{{ __('Menghapus...') }}' : '{{ __('Hapus Foto') }}'"></span>
                    </button>
                </div>
            </div>
        </x-modal>

        {{-- Form Fields (Name & Email) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-control w-full space-y-1">
                <x-input-label for="name" :value="__('Nama Lengkap')" class="font-semibold text-xs text-base-content/80" />
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>
                    <x-text-input id="name" name="name" type="text" class="input input-bordered w-full pl-9 rounded-xl font-medium" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('name')" />
            </div>

            <div class="form-control w-full space-y-1">
                <x-input-label for="email" :value="__('Alamat Email')" class="font-semibold text-xs text-base-content/80" />
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <x-text-input id="email" name="email" type="email" class="input input-bordered w-full pl-9 rounded-xl font-medium" :value="old('email', $user->email)" required autocomplete="username" />
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-2 p-3 rounded-xl bg-warning/10 border border-warning/20">
                        <p class="text-xs text-warning-content">
                            {{ __('Alamat email Anda belum diverifikasi.') }}
                            <button form="send-verification" class="link link-primary font-semibold text-xs ml-1">
                                {{ __('Kirim ulang email verifikasi') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-1 font-semibold text-xs text-success">
                                {{ __('Tautan verifikasi baru telah dikirimkan ke alamat email Anda.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Organization Info Summary (Read-Only) --}}
        <div class="rounded-2xl border border-base-200 bg-base-200/20 p-5 space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-base-content/70 uppercase tracking-wider">
                    {{ __('Penugasan & Hak Akses Organisasi (Hanya Lihat)') }}
                </span>
                <span class="text-[11px] text-base-content/40 italic">
                    {{ __('Dikelola oleh Administrator') }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div class="bg-base-100 p-3.5 rounded-xl border border-base-200">
                    <span class="text-base-content/50 block mb-1">{{ __('Peran Sistem') }}</span>
                    <span class="badge {{ $user->system_role === 'admin' ? 'badge-accent' : ($user->system_role === 'direktur' ? 'badge-info' : ($user->system_role === 'head' ? 'badge-warning' : 'badge-ghost')) }} badge-sm uppercase font-bold">
                        {{ $user->system_role }}
                    </span>
                </div>

                <div class="bg-base-100 p-3.5 rounded-xl border border-base-200">
                    <span class="text-base-content/50 block mb-1">{{ __('Divisi') }}</span>
                    @if($user->divisions->isNotEmpty())
                        <div class="flex flex-wrap gap-1">
                            @foreach($user->divisions as $div)
                                <span class="font-semibold text-base-content bg-base-200 px-1.5 py-0.5 rounded">{{ $div->code ?: $div->name }}</span>
                            @endforeach
                        </div>
                    @elseif($user->division)
                        <span class="font-semibold text-base-content">{{ $user->division->name }}</span>
                    @else
                        <span class="text-base-content/40 italic">-</span>
                    @endif
                </div>

                <div class="bg-base-100 p-3.5 rounded-xl border border-base-200">
                    <span class="text-base-content/50 block mb-1">{{ __('Perusahaan & Cabang') }}</span>
                    @if($user->system_role === 'admin')
                        <span class="font-semibold text-accent-content">{{ __('Semua Perusahaan') }}</span>
                    @elseif($user->companies->isNotEmpty())
                        <span class="font-semibold text-base-content">
                            {{ $user->companies->count() }} Perusahaan ({{ $user->branches->count() }} Cabang)
                        </span>
                    @else
                        <span class="text-base-content/40 italic">-</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Submit Button & Feedback --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn btn-primary rounded-xl px-5 font-semibold gap-2 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ __('Simpan Perubahan') }}</span>
            </button>

            @if (session('status') === 'profile-updated')
                <div x-data="{ show: true }"
                     x-show="show"
                     x-transition
                     x-init="setTimeout(() => show = false, 3000)"
                     class="flex items-center gap-1.5 text-xs text-success font-semibold">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ __('Perubahan profil berhasil disimpan.') }}</span>
                </div>
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
