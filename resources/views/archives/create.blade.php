<x-app-layout>
    <x-slot name="header">{{ __('Pendaftaran Dokumen ke Buku Induk Arsip') }}</x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto w-full space-y-6">

            {{-- Breadcrumb & Back Link --}}
            <div class="flex items-center justify-between">
                <a href="{{ route('documents.show', $document->id) }}" class="btn btn-ghost btn-sm gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Kembali ke Dokumen') }}
                </a>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            onclick="document.getElementById('archive-info-modal').showModal()" 
                            class="btn btn-ghost btn-xs gap-1 text-base-content/70 hover:text-primary border border-base-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-info shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ __('Fungsi Pengarsipan') }}</span>
                    </button>
                    <span class="badge badge-primary badge-outline text-xs font-semibold">
                        {{ __('Wizard Registrasi Arsip') }}
                    </span>
                </div>
            </div>

            {{-- Preflight Eligibility Status Card --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h4 class="font-bold text-base">{{ __('Pemeriksaan Kelayakan Dokumen (Preflight Verification)') }}</h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    @foreach($eligibility['checks'] as $key => $check)
                        <div class="flex items-center gap-2 p-2 rounded-lg bg-base-200/50">
                            @if($check['passed'])
                                <span class="badge badge-success badge-xs shrink-0">✓</span>
                                <span class="text-base-content/80">{{ $check['label'] }}</span>
                            @else
                                <span class="badge badge-error badge-xs shrink-0">✕</span>
                                <span class="text-error font-semibold">{{ $check['label'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Non-Reserving Number Preview Banner --}}
            <div class="alert alert-info shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <div class="text-xs uppercase tracking-wider font-bold opacity-80">{{ __('Format Indikatif Penomoran Arsip') }}</div>
                        <div class="font-mono text-base font-black tracking-wide" id="previewNumberText">
                            {{ $preview['number'] }}
                        </div>
                        <div class="text-[11px] opacity-75 mt-0.5">
                            {{ __('Pratinjau format semata tanpa mengunci antrean sequence. Nomor definitif dialokasikan secara atomik saat pendaftaran disetujui.') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Registration Form --}}
            <form method="POST" action="{{ route('archive-requests.store') }}" class="card bg-base-100 border border-base-200 shadow-sm p-6 space-y-5">
                @csrf
                <input type="hidden" name="document_id" value="{{ $document->id }}">
                <input type="hidden" name="requested_version_id" value="{{ $version->id }}">

                {{-- Document Overview --}}
                <div class="p-4 rounded-xl bg-base-200/60 border border-base-300/60 space-y-2">
                    <div class="text-xs font-bold text-base-content/50 uppercase tracking-wider">{{ __('Dokumen Sumber') }}</div>
                    <div class="font-bold text-lg text-base-content">{{ $document->title }}</div>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs text-base-content/70 pt-2 border-t border-base-300/40">
                        <div><span class="opacity-60">{{ __('Tipe Dokumen:') }}</span> <span class="font-semibold text-primary">{{ $document->documentType?->name ?? '-' }} ({{ $document->documentType?->code ?? '-' }})</span></div>
                        <div><span class="opacity-60">{{ __('Nomor:') }}</span> {{ $document->document_number ?? '-' }}</div>
                        <div><span class="opacity-60">{{ __('Versi:') }}</span> v{{ $version->version_number }} ({{ $version->status }})</div>
                        <div><span class="opacity-60">{{ __('Perusahaan:') }}</span> {{ $document->company?->code ?? 'CMP' }}</div>
                        <div><span class="opacity-60">{{ __('Cabang/Unit:') }}</span> {{ $document->branch?->code ?? 'PST' }} / {{ $document->unitKerja?->kode_unit_kerja ?? 'GEN' }}</div>
                    </div>
                </div>

                {{-- Archive Title --}}
                <div class="form-control w-full">
                    <label class="label font-bold text-sm">
                        <span class="label-text">{{ __('Judul Resmi Arsip') }} <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="archive_title" value="{{ old('archive_title', $document->title) }}" required
                           class="input input-bordered w-full" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Classification Selector --}}
                    <div class="form-control w-full">
                        <div class="flex items-center justify-between pb-1">
                            <label class="label font-bold text-sm p-0">
                                <span class="label-text">{{ __('Klasifikasi Arsip') }} <span class="text-error">*</span></span>
                            </label>
                            @if(!empty($selectedClassification) && $document->documentType)
                                <span class="badge badge-success badge-sm text-white gap-1 font-bold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    {{ __('Otomatis Sesuai Tipe') }}
                                </span>
                            @endif
                        </div>
                        <select name="classification_id" id="classificationSelect" required class="select select-bordered w-full">
                            <option value="" disabled {{ old('classification_id', $defaultClassificationId ?? null) ? '' : 'selected' }}>-- {{ __('Pilih Klasifikasi Arsip') }} --</option>
                            @forelse($classifications as $c)
                                @php
                                    $isSelected = (old('classification_id', $defaultClassificationId ?? null) == $c->id);
                                    $isAutoMatched = (!empty($selectedClassification) && $selectedClassification->id === $c->id);
                                @endphp
                                <option value="{{ $c->id }}"
                                        data-code="{{ $c->code }}"
                                        data-retention-id="{{ $c->default_retention_policy_id }}"
                                        data-confidentiality="{{ $c->confidentiality_level }}"
                                        {{ $isSelected ? 'selected' : '' }}>
                                    {{ $c->code }} — {{ $c->name }} ({{ ucfirst($c->confidentiality_level) }}){{ $isAutoMatched ? ' ★' : '' }}
                                </option>
                            @empty
                                <option value="" disabled>{{ __('Belum ada klasifikasi arsip terdaftar') }}</option>
                            @endforelse
                        </select>
                        @if($classifications->isEmpty())
                            <div class="alert alert-warning text-xs mt-2 py-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                <span>{{ __('Belum ada klasifikasi arsip yang aktif. Administrator dapat mengelolanya di menu Pengaturan Arsip.') }}</span>
                            </div>
                        @endif
                        <label class="label">
                            <span class="label-text-alt text-base-content/60">{{ __('Menentukan token {CLASS} dalam nomor arsip dan jadwal retensi bawaan.') }}</span>
                        </label>
                    </div>

                    {{-- Retention Policy (JRA) Selector --}}
                    <div class="form-control w-full">
                        <label class="label font-bold text-sm">
                            <span class="label-text">{{ __('Jadwal Retensi Arsip (JRA)') }}</span>
                        </label>
                        <select name="retention_policy_id" id="retentionPolicySelect" class="select select-bordered w-full">
                            <option value="">{{ __('Gunakan Retensi Bawaan Klasifikasi') }}</option>
                            @foreach($retentionPolicies as $p)
                                <option value="{{ $p->id }}" {{ old('retention_policy_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->code }} — {{ $p->name }} ({{ $p->summary_label }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Sensitivity Level --}}
                    <div class="form-control w-full">
                        <label class="label font-bold text-sm">
                            <span class="label-text">{{ __('Tingkat Keamanan / Akses') }} <span class="text-error">*</span></span>
                        </label>
                        <select name="sensitivity_level" required class="select select-bordered w-full">
                            <option value="public" {{ old('sensitivity_level') === 'public' ? 'selected' : '' }}>{{ __('Biasa / Terbuka (Public)') }}</option>
                            <option value="internal" {{ old('sensitivity_level', 'internal') === 'internal' ? 'selected' : '' }}>{{ __('Internal (Internal)') }}</option>
                            <option value="confidential" {{ old('sensitivity_level') === 'confidential' ? 'selected' : '' }}>{{ __('Rahasia (Confidential)') }}</option>
                            <option value="restricted" {{ old('sensitivity_level') === 'restricted' ? 'selected' : '' }}>{{ __('Sangat Rahasia (Restricted)') }}</option>
                        </select>
                    </div>

                    {{-- Record Date --}}
                    <div class="form-control w-full">
                        <label class="label font-bold text-sm">
                            <span class="label-text">{{ __('Tanggal Dokumen/Pemberlakuan') }}</span>
                        </label>
                        <input type="date" name="record_date" value="{{ old('record_date', now()->toDateString()) }}" class="input input-bordered w-full" />
                    </div>

                    {{-- Effective Date --}}
                    <div class="form-control w-full">
                        <label class="label font-bold text-sm">
                            <span class="label-text">{{ __('Tanggal Kedaluwarsa Asli') }}</span>
                        </label>
                        <input type="date" name="effective_date" value="{{ old('effective_date', $document->expiration_date?->toDateString()) }}" class="input input-bordered w-full" />
                    </div>
                </div>

                {{-- Archive Reason / Description --}}
                <div class="form-control w-full">
                    <label class="label font-bold text-sm">
                        <span class="label-text">{{ __('Uraian / Alasan Pengarsipan') }}</span>
                    </label>
                    <textarea name="archive_reason" rows="3" class="textarea textarea-bordered w-full"
                              placeholder="{{ __('Contoh: Dokumen SOP telah disahkan oleh Direktur dan diarsipkan sebagai rekaman baku perusahaan...') }}">{{ old('archive_reason') }}</textarea>
                </div>

                {{-- Immutability Notice --}}
                <div class="p-4 rounded-xl bg-warning/10 border border-warning/30 text-xs text-base-content/80 flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-warning shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <strong>{{ __('Peringatan Integritas Imutabel:') }}</strong>
                        {{ __('Saat permohonan ini disetujui dan difinalisasi, sistem akan menyalin berkas ke repositori mandiri, mencatat sidik jari SHA-256, dan menetapkan nomor arsip resmi permanen. Dokumen arsip tidak dapat diubah maupun ditimpa secara sepihak.') }}
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-base-200">
                    <button type="submit" name="submit_now" value="0" class="btn btn-ghost btn-sm">
                        {{ __('Simpan Draf Permohonan') }}
                    </button>
                    <button type="submit" name="submit_now" value="1" class="btn btn-primary btn-sm px-6">
                        {{ __('Ajukan Permohonan Sekarang') }}
                    </button>
                </div>
            </form>

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectEl = document.getElementById('classificationSelect');
            const previewTextEl = document.getElementById('previewNumberText');
            const previewUrl = @json(route('archive-requests.preview-number'));
            const companyId = @json($document->company_id);
            const branchId = @json($document->branch_id);
            const unitKerjaId = @json($document->unit_kerja_id);

            if (selectEl && previewTextEl) {
                selectEl.addEventListener('change', function () {
                    const classId = this.value;
                    if (!classId) return;

                    previewTextEl.classList.add('opacity-40');

                    const url = new URL(previewUrl, window.location.origin);
                    url.searchParams.set('classification_id', classId);
                    if (companyId) url.searchParams.set('company_id', companyId);
                    if (branchId) url.searchParams.set('branch_id', branchId);
                    if (unitKerjaId) url.searchParams.set('unit_kerja_id', unitKerjaId);

                    fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.number) {
                            previewTextEl.textContent = data.number;
                        }
                    })
                    .catch(err => console.error('Error fetching archive preview:', err))
                    .finally(() => {
                        previewTextEl.classList.remove('opacity-40');
                    });
                });
            }
        });
    </script>
    @endpush
</x-app-layout>

