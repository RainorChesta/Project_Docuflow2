<x-app-layout>
    <x-slot name="header">{{ __('Konfigurasi Kearsipan & Jadwal Retensi') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- Top Header --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-bold text-base-content flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ __('Pengaturan Modul Arsip & Rekaman') }}
                    </h3>
                    <p class="text-sm text-base-content/60">
                        {{ __('Konfigurasi katalog klasifikasi arsip, skema format penomoran resmi, dan aturan Jadwal Retensi Arsip (JRA).') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('archives.index') }}" class="btn btn-outline btn-sm gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        {{ __('Buku Induk Arsip') }}
                    </a>
                </div>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Tabs --}}
            <div class="tabs tabs-boxed bg-base-200/80 p-1 w-fit">
                <a href="{{ route('admin.archive-settings.index', ['tab' => 'classifications']) }}"
                   class="tab tab-sm font-semibold {{ $tab === 'classifications' ? 'tab-active' : '' }}">
                    {{ __('Katalog Klasifikasi') }}
                </a>

                <a href="{{ route('admin.archive-settings.index', ['tab' => 'numbering']) }}"
                   class="tab tab-sm font-semibold {{ $tab === 'numbering' ? 'tab-active' : '' }}">
                    {{ __('Skema Penomoran') }}
                </a>

                <a href="{{ route('admin.archive-settings.index', ['tab' => 'retention']) }}"
                   class="tab tab-sm font-semibold {{ $tab === 'retention' ? 'tab-active' : '' }}">
                    {{ __('Jadwal Retensi Arsip (JRA)') }}
                </a>
            </div>

            @if($tab === 'classifications')
                {{-- Classifications Tab --}}
                <div class="card bg-base-100 border border-base-200 shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-base">{{ __('Katalog Klasifikasi Kearsipan') }}</h4>
                            <p class="text-xs text-base-content/60">{{ __('Klasifikasi menentukan kategori arsip, tingkat akses awal, dan retensi standar.') }}</p>
                        </div>
                        <button type="button" onclick="create_class_modal.showModal()" class="btn btn-primary btn-sm gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ __('Tambah Klasifikasi') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full text-sm">
                            <thead class="bg-base-200/60 text-base-content/70">
                                <tr>
                                    <th>{{ __('Kode') }}</th>
                                    <th>{{ __('Nama Klasifikasi') }}</th>
                                    <th>{{ __('Perusahaan') }}</th>
                                    <th>{{ __('Tingkat Akses') }}</th>
                                    <th>{{ __('JRA Bawaan') }}</th>
                                    <th>{{ __('Persetujuan') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($classifications as $c)
                                    <tr class="hover">
                                        <td class="font-mono font-bold text-xs text-primary">{{ $c->code }}</td>
                                        <td class="font-semibold text-base-content">{{ $c->name }}</td>
                                        <td class="text-xs">{{ $c->company?->name ?? __('Semua Perusahaan (Global)') }}</td>
                                        <td>
                                            <span class="badge badge-outline badge-xs uppercase font-bold">{{ $c->confidentiality_level }}</span>
                                        </td>
                                        <td class="text-xs text-base-content/70">
                                            {{ $c->defaultRetentionPolicy?->code ?? '-' }}
                                        </td>
                                        <td>
                                            @if($c->requires_approval)
                                                <span class="badge badge-warning badge-xs">{{ __('Wajib') }}</span>
                                            @else
                                                <span class="badge badge-ghost badge-xs">{{ __('Auto') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-8 text-base-content/50">
                                            {{ __('Belum ada data klasifikasi arsip.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            @elseif($tab === 'numbering')
                {{-- Numbering Schemes Tab --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Form Configuration --}}
                    <div class="md:col-span-1 card bg-base-100 border border-base-200 shadow-sm p-6 space-y-4">
                        <h4 class="font-bold text-base">{{ __('Atur Skema Penomoran') }}</h4>
                        <p class="text-xs text-base-content/60">{{ __('Tetapkan format template kode arsip resmi dengan token dinamis.') }}</p>

                        <form method="POST" action="{{ route('admin.archive-settings.numbering.store') }}" class="space-y-4 text-xs">
                            @csrf
                            <div class="form-control">
                                <label class="label font-bold py-1"><span class="label-text">{{ __('Perusahaan') }}</span></label>
                                <select name="company_id" class="select select-sm select-bordered w-full">
                                    <option value="">{{ __('Semua Perusahaan (Global Default)') }}</option>
                                    @foreach($companies as $comp)
                                        <option value="{{ $comp->id }}">{{ $comp->name }} ({{ $comp->code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label font-bold py-1"><span class="label-text">{{ __('Nama Skema') }}</span></label>
                                <input type="text" name="name" value="Skema Penomoran CV MMC (Approach A)" required class="input input-sm input-bordered w-full" />
                            </div>

                            <div class="form-control">
                                <label class="label font-bold py-1"><span class="label-text">{{ __('Prefix / Kode Pusat') }}</span></label>
                                <input type="text" name="prefix" value="JBM" required class="input input-sm input-bordered w-full font-mono uppercase" />
                            </div>

                            <div class="form-control">
                                <label class="label font-bold py-1"><span class="label-text">{{ __('Template Format') }}</span></label>
                                <input type="text" name="template" id="templateInput" value="{PUSAT}/{CLASS}/{ROMAN_MONTH}/{YYYY}/{SEQ:03}" required class="input input-sm input-bordered w-full font-mono" />
                                <label class="label py-0.5">
                                    <span class="label-text-alt text-[10px] text-base-content/50">Token: {PUSAT}, {CLASS}, {ROMAN_MONTH}, {YYYY}, {SEQ:03} | Kode Kolom: {D}/{B}/{E}/{F}/{A}</span>
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="form-control">
                                    <label class="label font-bold py-1"><span class="label-text">{{ __('Cakupan Urutan') }}</span></label>
                                    <select name="sequence_scope" class="select select-sm select-bordered w-full">
                                        <option value="company_year">{{ __('Per Perusahaan/Tahun') }}</option>
                                        <option value="branch_year">{{ __('Per Cabang/Tahun') }}</option>
                                        <option value="company_never">{{ __('Sepanjang Masa') }}</option>
                                    </select>
                                </div>

                                <div class="form-control">
                                    <label class="label font-bold py-1"><span class="label-text">{{ __('Reset Sequence') }}</span></label>
                                    <select name="reset_period" class="select select-sm select-bordered w-full">
                                        <option value="monthly" selected>{{ __('Bulanan (Roman Month)') }}</option>
                                        <option value="yearly">{{ __('Tahunan') }}</option>
                                        <option value="never">{{ __('Tidak Pernah') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="form-control">
                                    <label class="label font-bold py-1"><span class="label-text">{{ __('Padding Digit') }}</span></label>
                                    <input type="number" name="padding" value="3" min="2" max="10" required class="input input-sm input-bordered w-full font-mono" />
                                </div>

                                <div class="form-control">
                                    <label class="label font-bold py-1"><span class="label-text">{{ __('Nilai Awal') }}</span></label>
                                    <input type="number" name="start_value" value="1" min="1" required class="input input-sm input-bordered w-full font-mono" />
                                </div>
                            </div>

                            <input type="hidden" name="timezone" value="Asia/Jakarta">

                            <button type="submit" class="btn btn-primary btn-sm w-full mt-2">
                                {{ __('Simpan & Aktifkan Skema') }}
                            </button>
                        </form>
                    </div>

                    {{-- Schemes Table & Live Preview --}}
                    <div class="md:col-span-2 space-y-4">
                        <div class="card bg-base-100 border border-base-200 shadow-sm p-6 space-y-3">
                            <h4 class="font-bold text-base">{{ __('Daftar Skema Penomoran Aktif') }}</h4>
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full text-xs">
                                    <thead class="bg-base-200/60">
                                        <tr>
                                            <th>{{ __('Nama Skema') }}</th>
                                            <th>{{ __('Template Format') }}</th>
                                            <th>{{ __('Cakupan') }}</th>
                                            <th>{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($numberingSchemes as $s)
                                            <tr class="hover">
                                                <td class="font-bold">{{ $s->name }}</td>
                                                <td class="font-mono text-primary">{{ $s->template }}</td>
                                                <td>{{ $s->sequence_scope }} ({{ $s->reset_period }})</td>
                                                <td>
                                                    @if($s->is_active)
                                                        <span class="badge badge-success badge-xs text-white font-bold">{{ __('AKTIF') }}</span>
                                                    @else
                                                        <span class="badge badge-ghost badge-xs">{{ __('Inaktif') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-6 text-base-content/50">
                                                    {{ __('Belum ada skema khusus. Sistem menggunakan default template.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                {{-- Retention Policies (JRA) Tab --}}
                <div class="card bg-base-100 border border-base-200 shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-base">{{ __('Jadwal Retensi Arsip (JRA)') }}</h4>
                            <p class="text-xs text-base-content/60">{{ __('Aturan masa simpan aktif, inaktif, dan tindakan akhir pemusnahan atau penetapan permanen.') }}</p>
                        </div>
                        <button type="button" onclick="create_ret_modal.showModal()" class="btn btn-primary btn-sm gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ __('Tambah Kebijakan JRA') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full text-sm">
                            <thead class="bg-base-200/60 text-base-content/70">
                                <tr>
                                    <th>{{ __('Kode') }}</th>
                                    <th>{{ __('Nama Kebijakan') }}</th>
                                    <th>{{ __('Pemicu') }}</th>
                                    <th>{{ __('Retensi Aktif') }}</th>
                                    <th>{{ __('Retensi Inaktif') }}</th>
                                    <th>{{ __('Tindakan Akhir') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($retentionPolicies as $p)
                                    <tr class="hover">
                                        <td class="font-mono font-bold text-xs text-primary">{{ $p->code }}</td>
                                        <td class="font-semibold text-base-content">{{ $p->name }}</td>
                                        <td class="text-xs">{{ $p->trigger_event }}</td>
                                        <td class="text-xs">{{ $p->active_retention_years }} {{ __('Tahun') }}</td>
                                        <td class="text-xs">{{ $p->inactive_retention_years }} {{ __('Tahun') }}</td>
                                        <td>
                                            <span class="badge badge-outline badge-sm uppercase font-bold">{{ $p->final_disposition }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-8 text-base-content/50">
                                            {{ __('Belum ada aturan JRA.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Create Classification Modal --}}
    <dialog id="create_class_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ __('Tambah Klasifikasi Arsip Baru') }}</h3>
            <form method="POST" action="{{ route('admin.archive-settings.classifications.store') }}" class="space-y-4 mt-3">
                @csrf
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Kode Klasifikasi') }} <span class="text-error">*</span></span></label>
                    <input type="text" name="code" required placeholder="Contoh: SOP-01, KEU-02, SDM-01" class="input input-sm input-bordered w-full font-mono uppercase" />
                </div>
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Nama Klasifikasi') }} <span class="text-error">*</span></span></label>
                    <input type="text" name="name" required placeholder="Contoh: Prosedur Operasional Standar Pelayanan" class="input input-sm input-bordered w-full" />
                </div>
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Tingkat Keamanan Bawaan') }}</span></label>
                    <select name="confidentiality_level" class="select select-sm select-bordered w-full">
                        <option value="public">Public</option>
                        <option value="internal" selected>Internal</option>
                        <option value="confidential">Confidential</option>
                        <option value="restricted">Restricted</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('JRA Bawaan') }}</span></label>
                    <select name="default_retention_policy_id" class="select select-sm select-bordered w-full">
                        <option value="">{{ __('Pilih JRA (Opsional)') }}</option>
                        @foreach($retentionPolicies as $rp)
                            <option value="{{ $rp->id }}">{{ $rp->code }} - {{ $rp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-action">
                    <button type="button" onclick="create_class_modal.close()" class="btn btn-sm btn-ghost">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Simpan Klasifikasi') }}</button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Create Retention Policy Modal --}}
    <dialog id="create_ret_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ __('Tambah Kebijakan JRA Baru') }}</h3>
            <form method="POST" action="{{ route('admin.archive-settings.retention.store') }}" class="space-y-4 mt-3">
                @csrf
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Kode JRA') }} <span class="text-error">*</span></span></label>
                    <input type="text" name="code" required placeholder="Contoh: JRA-SOP-5TH, JRA-KEU-10TH" class="input input-sm input-bordered w-full font-mono uppercase" />
                </div>
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Nama Kebijakan JRA') }} <span class="text-error">*</span></span></label>
                    <input type="text" name="name" required placeholder="Contoh: Retensi Dokumen SOP Pelayanan 5 Tahun" class="input input-sm input-bordered w-full" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label font-bold text-xs"><span class="label-text">{{ __('Pemicu Waktu') }}</span></label>
                        <select name="trigger_event" class="select select-sm select-bordered w-full">
                            <option value="registration_date">{{ __('Tgl Registrasi') }}</option>
                            <option value="document_date">{{ __('Tgl Dokumen') }}</option>
                            <option value="expiration_date">{{ __('Tgl Kedaluwarsa') }}</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label font-bold text-xs"><span class="label-text">{{ __('Tindakan Akhir') }}</span></label>
                        <select name="final_disposition" class="select select-sm select-bordered w-full">
                            <option value="review">{{ __('Review Ulang') }}</option>
                            <option value="destroy">{{ __('Pemusnahan') }}</option>
                            <option value="permanent">{{ __('Arsip Permanen') }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label font-bold text-xs"><span class="label-text">{{ __('Retensi Aktif (Tahun)') }}</span></label>
                        <input type="number" name="active_retention_years" value="2" min="0" required class="input input-sm input-bordered w-full" />
                    </div>
                    <div class="form-control">
                        <label class="label font-bold text-xs"><span class="label-text">{{ __('Retensi Inaktif (Tahun)') }}</span></label>
                        <input type="number" name="inactive_retention_years" value="3" min="0" required class="input input-sm input-bordered w-full" />
                    </div>
                </div>
                <div class="modal-action">
                    <button type="button" onclick="create_ret_modal.close()" class="btn btn-sm btn-ghost">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Simpan Kebijakan JRA') }}</button>
                </div>
            </form>
        </div>
    </dialog>
</x-app-layout>

