<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto w-full px-0 space-y-6">
            {{-- Page Header --}}
            <div class="pb-4 border-b border-base-300">
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-base-content flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-warning/10 text-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <span>{{ __('Retensi Berkas Non-Aktif') }}</span>
                </h1>
                <p class="text-xs sm:text-sm text-base-content/60 mt-1">
                    {{ __('Atur kebijakan pembersihan otomatis berkas draft dan versi non-aktif di server penyimpanan.') }}
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success rounded-2xl shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-error rounded-2xl shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Form Retensi Berkas Non-Aktif --}}
            <div class="bg-base-100 border border-base-300 rounded-3xl shadow-xs overflow-hidden">
                <form method="POST" action="{{ route('admin.retention.update') }}" class="p-6 space-y-6">
                    @csrf 
                    @method('PUT')

                    <div class="space-y-3">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-xl bg-warning/10 text-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-bold text-base text-base-content">{{ __('Retensi Berkas Versi Non-Aktif (Draft Lama)') }}</h2>
                                <p class="text-xs text-base-content/60">{{ __('Pembersihan otomatis file versi yang dibuang atau ditolak.') }}</p>
                            </div>
                        </div>

                        <p class="text-xs text-base-content/70 leading-relaxed">
                            {{ __('Berkas versi draft lama atau ditolak yang melewati periode ini akan dibersihkan secara otomatis oleh sistem harian. Versi dokumen yang sedang aktif tidak akan pernah dihapus.') }}
                        </p>

                        <div class="form-control mt-2">
                            <label class="label py-1" for="retention_days">
                                <span class="label-text font-semibold text-xs text-base-content uppercase tracking-wider">{{ __('Batas Simpan Versi Non-Aktif (Hari)') }}</span>
                                <span class="label-text-alt text-[11px] text-base-content/50">{{ __('Maksimal 3650 hari (10 Tahun)') }}</span>
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       name="retention_days" 
                                       id="retention_days"
                                       class="input input-bordered w-full rounded-xl pr-16 text-sm font-semibold font-mono" 
                                       min="1" 
                                       max="3650"
                                       value="{{ old('retention_days', $retentionDays) }}" 
                                       required />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-base-content/50">{{ __('Hari') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-base-200">
                        <button type="submit" class="btn btn-primary rounded-xl px-6 gap-2 font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ __('Simpan Pengaturan Retensi') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
