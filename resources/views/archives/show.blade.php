<x-app-layout>
    <x-slot name="header">{{ __('Detail Buku Induk Arsip') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- Breadcrumb & Top Bar --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <a href="{{ route('archives.index') }}" class="btn btn-ghost btn-sm gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Kembali ke Buku Induk Arsip') }}
                </a>

                <div class="flex flex-wrap items-center gap-2">
                    @if(!$archive->isDisposed())
                        <a href="{{ route('archives.download', $archive->id) }}" class="btn btn-primary btn-sm gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            {{ __('Unduh Snapshot Asli') }}
                        </a>

                        <a href="{{ route('archives.preview', $archive->id) }}" target="_blank" class="btn btn-outline btn-sm gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ __('Pratinjau') }}
                        </a>
                    @endif

                    <form method="POST" action="{{ route('archives.verify-integrity', $archive->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-success btn-sm gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            {{ __('Verifikasi SHA-256') }}
                        </button>
                    </form>

                    {{-- Actions Dropdown --}}
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-sm btn-square border border-base-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </label>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-xl bg-base-100 rounded-box w-56 text-xs border border-base-200">
                            <li>
                                <a onclick="qr_modal.showModal()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                    {{ __('Kode QR & Tautan Verifikasi') }}
                                </a>
                            </li>

                            @if($canCorrect)
                                <li>
                                    <a onclick="metadata_modal.showModal()">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        {{ __('Koreksi Metadata (Diaudit)') }}
                                    </a>
                                </li>
                            @endif

                            @if($canReactivate)
                                <li>
                                    <a onclick="reactivate_modal.showModal()">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                                        {{ __('Buat Salinan Kerja Baru') }}
                                    </a>
                                </li>
                            @endif

                            @if($canPlaceHold)
                                <li>
                                    <a onclick="hold_modal.showModal()">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        {{ __('Berlakukan Legal Hold') }}
                                    </a>
                                </li>
                            @endif

                            @if($canRequestDisposition)
                                <li>
                                    <a onclick="disposition_modal.showModal()">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        {{ __('Ajukan Penyusutan / Pemusnahan') }}
                                    </a>
                                </li>
                            @endif

                            @if($canVoid)
                                <li>
                                    <a onclick="void_modal.showModal()" class="text-error">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        {{ __('Batalkan (Void) Arsip') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Legal Hold Active Banner --}}
            @if($archive->is_under_legal_hold)
                <div class="alert alert-error shadow-sm text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <div class="flex-1">
                        <div class="font-bold text-sm uppercase tracking-wider">{{ __('PERINGATAN: DOKUMEN DALAM PEMBEKUAN HUKUM (LEGAL HOLD)') }}</div>
                        <div class="text-xs opacity-90 mt-0.5">
                            {{ __('Arsip ini sedang dalam pembekuan hukum atau pemeriksaan audit aktif. Segala proses penyusutan dan pemusnahan diblokir secara mutlak.') }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- Official Certificate Card --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-base-200 bg-base-200/20 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-primary badge-sm font-semibold uppercase">{{ __('Nomor Arsip Resmi') }}</span>
                            <span class="badge badge-outline badge-sm uppercase">{{ $archive->sensitivity_level }}</span>
                            @if($archive->status === 'registered')
                                <span class="badge badge-success badge-sm font-semibold">{{ __('Terdaftar') }}</span>
                            @elseif($archive->status === 'under_review')
                                <span class="badge badge-warning badge-sm font-semibold">{{ __('Under Review') }}</span>
                            @elseif($archive->status === 'eligible_for_disposition')
                                <span class="badge badge-error badge-sm font-semibold">{{ __('Siap Penyusutan') }}</span>
                            @elseif($archive->status === 'disposed')
                                <span class="badge badge-neutral badge-sm font-semibold">{{ __('Telah Dimusnahkan') }}</span>
                            @elseif($archive->status === 'permanent')
                                <span class="badge badge-info badge-sm font-semibold">{{ __('Permanen') }}</span>
                            @elseif($archive->status === 'voided')
                                <span class="badge badge-neutral badge-sm line-through">{{ __('Voided') }}</span>
                            @endif
                        </div>
                        <div class="font-mono text-2xl font-black text-primary mt-2 flex items-center gap-2">
                            <span>{{ $archive->archive_number }}</span>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $archive->archive_number }}')" class="btn btn-ghost btn-xs btn-square" title="{{ __('Salin Nomor') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                            </button>
                        </div>
                        <h1 class="text-xl font-bold text-base-content mt-1">{{ $archive->title }}</h1>
                    </div>

                    <div class="text-left md:text-right text-xs text-base-content/60 space-y-1">
                        <div>{{ __('UUID Rekaman:') }} <span class="font-mono">{{ $archive->uuid }}</span></div>
                        <div>{{ __('Tanggal Registrasi:') }} <span class="font-bold">{{ $archive->registered_at?->format('d M Y H:i:s T') }}</span></div>
                        <div>{{ __('Pendaftar:') }} <span class="font-medium">{{ $archive->registeredBy?->name ?? '-' }}</span></div>
                    </div>
                </div>

                {{-- Provenance & Cryptographic Verification Grid --}}
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Cryptographic Integrity Box --}}
                    <div class="p-4 rounded-xl bg-base-200/50 border border-base-300/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-base-content/60">{{ __('Bukti Integritas Kriptografis') }}</span>
                            @if($archive->snapshot?->integrity_status === 'valid')
                                <span class="badge badge-success badge-xs font-bold text-white">{{ __('SHA-256 VALID') }}</span>
                            @elseif($archive->snapshot?->integrity_status === 'disposed')
                                <span class="badge badge-neutral badge-xs">{{ __('BERKAS DIMUSNAHKAN') }}</span>
                            @else
                                <span class="badge badge-error badge-xs">{{ $archive->snapshot?->integrity_status }}</span>
                            @endif
                        </div>

                        <div class="space-y-2 text-xs">
                            <div>
                                <span class="text-base-content/50 block">{{ __('SHA-256 Checksum Berkas:') }}</span>
                                <span class="font-mono text-[11px] font-bold text-base-content break-all select-all bg-base-100 p-1.5 rounded border border-base-200 block">
                                    {{ $archive->snapshot?->content_hash_sha256 ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="text-base-content/50 block">{{ __('SHA-256 Hash Manifest Rekaman:') }}</span>
                                <span class="font-mono text-[11px] text-base-content/70 break-all select-all bg-base-100 p-1.5 rounded border border-base-200 block">
                                    {{ $archive->snapshot?->manifest_hash_sha256 ?? '-' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between pt-1 text-[11px] text-base-content/60">
                                <span>{{ __('Ukuran:') }} {{ number_format(($archive->snapshot?->file_size ?? 0) / 1024, 1) }} KB</span>
                                <span>{{ __('Tipe:') }} {{ $archive->snapshot?->mime_type ?? '-' }}</span>
                                <span>{{ __('Verifikasi Terakhir:') }} {{ $archive->snapshot?->last_verified_at?->diffForHumans() ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Provenance & Classification Box --}}
                    <div class="p-4 rounded-xl bg-base-200/50 border border-base-300/60 space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-base-content/60">{{ __('Asal-Usul & Struktur Organisasi') }}</span>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-base-content/50 block">{{ __('Klasifikasi Arsip') }}</span>
                                <span class="font-bold text-base-content">{{ $archive->classification_code_snapshot }}</span>
                                <span class="text-[11px] text-base-content/60 block">{{ $archive->classification_name_snapshot }}</span>
                            </div>

                            <div>
                                <span class="text-base-content/50 block">{{ __('Dokumen Sumber') }}</span>
                                @if($archive->document)
                                    <a href="{{ route('documents.show', $archive->document->id) }}" class="link link-primary font-bold">
                                        {{ $archive->original_document_number ?: $archive->document->title }}
                                    </a>
                                @else
                                    <span class="font-bold text-base-content">{{ $archive->original_document_number ?: '-' }}</span>
                                @endif
                                <span class="text-[11px] text-base-content/60 block">{{ __('Versi:') }} v{{ $archive->version_number_snapshot }}</span>
                            </div>

                            <div>
                                <span class="text-base-content/50 block">{{ __('Perusahaan / Cabang') }}</span>
                                <span class="font-bold text-base-content">{{ $archive->company_code_snapshot }} / {{ $archive->branch_code_snapshot }}</span>
                                <span class="text-[11px] text-base-content/60 block">{{ $archive->company_name_snapshot }}</span>
                            </div>

                            <div>
                                <span class="text-base-content/50 block">{{ __('Unit Kerja & Pemilik') }}</span>
                                <span class="font-bold text-base-content">{{ $archive->unit_code_snapshot }}</span>
                                <span class="text-[11px] text-base-content/60 block">{{ $archive->owner_name_snapshot }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Retention & Disposition Schedule Panel --}}
                <div class="p-6 border-t border-base-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-base-content/60">{{ __('Jadwal Retensi Arsip (JRA) & Siklus Hidup') }}</span>
                        <span class="text-xs font-semibold text-primary">
                            {{ $archive->retentionPolicy?->name ?? __('Kebijakan Bawaan') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-xl bg-base-200/30 text-xs">
                        <div>
                            <span class="text-base-content/50 block">{{ __('Tgl Pemicu Retensi') }}</span>
                            <span class="font-bold text-base-content">{{ $archive->retention_trigger_date?->format('d M Y') ?? '-' }}</span>
                        </div>

                        <div>
                            <span class="text-base-content/50 block">{{ __('Masa Retensi Aktif') }}</span>
                            <span class="font-bold text-base-content">{{ $archive->retention_active_until?->format('d M Y') ?? '-' }}</span>
                        </div>

                        <div>
                            <span class="text-base-content/50 block">{{ __('Jatuh Tempo Total (Inaktif)') }}</span>
                            @if($archive->isPermanent())
                                <span class="font-bold text-info">{{ __('Permanen (Tanpa Batas)') }}</span>
                            @else
                                <span class="font-bold text-base-content">{{ $archive->retention_due_at?->format('d M Y') ?? '-' }}</span>
                            @endif
                        </div>

                        <div>
                            <span class="text-base-content/50 block">{{ __('Tindakan Akhir Retensi') }}</span>
                            <span class="badge badge-outline badge-sm uppercase">{{ $archive->final_disposition_action }}</span>
                        </div>
                    </div>

                    @if($archive->isDisposed())
                        <div class="alert alert-neutral shadow-sm mt-3 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-xs">{{ __('Rekaman Telah Dimusnahkan') }}</div>
                                <div class="text-[11px] opacity-80">{{ __('Nomor Sertifikat Pemusnahan:') }} {{ $archive->disposition_certificate_number }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Active Legal Holds Panel --}}
                @if($archive->activeLegalHolds->isNotEmpty())
                    <div class="p-6 border-t border-base-200 space-y-3 bg-error/5">
                        <span class="text-xs font-bold uppercase tracking-wider text-error">{{ __('Daftar Pembekuan Hukum Aktif (Active Legal Holds)') }}</span>
                        <div class="space-y-2">
                            @foreach($archive->activeLegalHolds as $hold)
                                <div class="p-3 rounded-lg bg-base-100 border border-error/30 flex items-start justify-between gap-3 text-xs">
                                    <div>
                                        <div class="font-bold text-error">[{{ $hold->case_reference }}] {{ $hold->title }}</div>
                                        <div class="text-base-content/80 mt-0.5">{{ $hold->reason }}</div>
                                        <div class="text-[11px] text-base-content/50 mt-1">
                                            {{ __('Diberlakukan oleh:') }} {{ $hold->appliedBy?->name }} • {{ $hold->applied_at?->format('d M Y H:i') }}
                                        </div>
                                    </div>
                                    @can('releaseHold', [$archive, $hold])
                                        <button type="button" onclick="openReleaseModal({{ $hold->id }}, '{{ $hold->case_reference }}')" class="btn btn-xs btn-outline btn-error">
                                            {{ __('Cabut Hold') }}
                                        </button>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Audit Trail Events Timeline --}}
                <div class="p-6 border-t border-base-200 space-y-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-base-content/60">{{ __('Riwayat Audit Rekaman Imutabel (Audit Trail)') }}</span>
                    <div class="space-y-3">
                        @forelse($archive->events as $event)
                            <div class="flex items-start gap-3 p-3 rounded-xl bg-base-200/40 text-xs border border-base-200">
                                <div class="badge badge-ghost badge-sm shrink-0 font-mono text-[10px]">
                                    {{ $event->occurred_at?->format('d/m/y H:i:s') }}
                                </div>
                                <div class="flex-1 space-y-1">
                                    <div class="font-semibold text-base-content flex items-center gap-2">
                                        <span class="badge badge-primary badge-outline badge-xs uppercase">{{ $event->event_type }}</span>
                                        <span>{{ $event->actor_name }} ({{ $event->actor_role }})</span>
                                    </div>
                                    @if($event->reason)
                                        <div class="text-base-content/70">{{ $event->reason }}</div>
                                    @endif
                                    <div class="font-mono text-[10px] text-base-content/40 break-all select-all">
                                        Hash: {{ $event->event_hash }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-xs text-base-content/50">{{ __('Belum ada riwayat audit.') }}</div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>
    </div>

    {{-- QR Code Verification Modal --}}
    <dialog id="qr_modal" class="modal">
        <div class="modal-box text-center space-y-4">
            <h3 class="font-bold text-lg">{{ __('Verifikasi Keaslian Rekaman Arsip') }}</h3>
            <div class="p-4 bg-white rounded-xl inline-block shadow-inner">
                <img src="{{ app(\App\Services\QrCodeService::class)->dataUri(route('archives.verify', $archive->qr_token)) }}" alt="QR Code" class="w-44 h-44 mx-auto" />
            </div>
            <div class="font-mono text-xs font-bold text-primary">{{ $archive->archive_number }}</div>
            <div class="text-xs text-base-content/60">
                {{ __('Pindai kode QR di atas untuk memeriksa status registrasi dan validitas rekaman arsip secara publik.') }}
            </div>
            <div class="modal-action justify-center">
                <button type="button" onclick="qr_modal.close()" class="btn btn-sm btn-ghost">{{ __('Tutup') }}</button>
            </div>
        </div>
    </dialog>

    {{-- Metadata Correction Modal --}}
    <dialog id="metadata_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ __('Koreksi Metadata Deskriptif') }}</h3>
            <p class="py-2 text-xs text-base-content/60">
                {{ __('Hanya atribut deskriptif yang dapat dikoreksi. Nomor arsip, sidik jari hash, dan berkas snapshot tetap imutabel.') }}
            </p>
            <form method="POST" action="{{ route('archives.correct-metadata', $archive->id) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Judul Arsip') }}</span></label>
                    <input type="text" name="title" value="{{ $archive->title }}" required class="input input-sm input-bordered w-full" />
                </div>

                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Tingkat Keamanan') }}</span></label>
                    <select name="sensitivity_level" class="select select-sm select-bordered w-full">
                        <option value="public" {{ $archive->sensitivity_level === 'public' ? 'selected' : '' }}>Public</option>
                        <option value="internal" {{ $archive->sensitivity_level === 'internal' ? 'selected' : '' }}>Internal</option>
                        <option value="confidential" {{ $archive->sensitivity_level === 'confidential' ? 'selected' : '' }}>Confidential</option>
                        <option value="restricted" {{ $archive->sensitivity_level === 'restricted' ? 'selected' : '' }}>Restricted</option>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Alasan Koreksi (Wajib Diisi untuk Audit Trail)') }} <span class="text-error">*</span></span></label>
                    <textarea name="correction_reason" rows="3" required class="textarea textarea-bordered w-full text-xs"
                              placeholder="{{ __('Jelaskan alasan pembaruan metadata...') }}"></textarea>
                </div>

                <div class="modal-action">
                    <button type="button" onclick="metadata_modal.close()" class="btn btn-sm btn-ghost">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Simpan Pembaruan') }}</button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Reactivate as Working Copy Modal --}}
    <dialog id="reactivate_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ __('Reaktivasi Salinan Kerja Baru') }}</h3>
            <p class="py-2 text-xs text-base-content/60">
                {{ __('Sistem akan membuat dokumen kerja baru dari snapshot arsip ini. Rekaman arsip resmi aslinya tetap aman, utuh, dan tidak berubah.') }}
            </p>
            <form method="POST" action="{{ route('archives.reactivate', $archive->id) }}" class="space-y-4">
                @csrf
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Alasan Reaktivasi / Kebutuhan Salinan Kerja') }} <span class="text-error">*</span></span></label>
                    <textarea name="reactivation_reason" rows="3" required class="textarea textarea-bordered w-full text-xs"
                              placeholder="{{ __('Contoh: Dokumen SOP perlu direvisi untuk penyesuaian regulasi tahun 2027...') }}"></textarea>
                </div>
                <div class="modal-action">
                    <button type="button" onclick="reactivate_modal.close()" class="btn btn-sm btn-ghost">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Buat Salinan Kerja') }}</button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Place Legal Hold Modal --}}
    <dialog id="hold_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-error">{{ __('Berlakukan Legal / Audit Hold') }}</h3>
            <p class="py-2 text-xs text-base-content/60">
                {{ __('Menahan arsip dari proses pemusnahan atau penyusutan selama kasus/audit berlangsung.') }}
            </p>
            <form method="POST" action="{{ route('archives.holds.store', $archive->id) }}" class="space-y-4">
                @csrf
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Nomor Kasus / Referensi Hukum') }} <span class="text-error">*</span></span></label>
                    <input type="text" name="case_reference" required placeholder="Contoh: AUDIT-BPK-2026-004" class="input input-sm input-bordered w-full" />
                </div>
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Judul Perkara / Pemeriksaan') }} <span class="text-error">*</span></span></label>
                    <input type="text" name="title" required placeholder="Pemeriksaan Kepatuhan Pajak Cabang" class="input input-sm input-bordered w-full" />
                </div>
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Dasar Alasan Pembekuan') }} <span class="text-error">*</span></span></label>
                    <textarea name="reason" rows="3" required class="textarea textarea-bordered w-full text-xs"></textarea>
                </div>
                <div class="modal-action">
                    <button type="button" onclick="hold_modal.close()" class="btn btn-sm btn-ghost">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-sm btn-error text-white">{{ __('Terapkan Pembekuan') }}</button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Release Legal Hold Dynamic Modal --}}
    <dialog id="release_hold_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ __('Pencabutan Legal Hold') }}</h3>
            <p class="py-2 text-xs text-base-content/60" id="releaseHoldText"></p>
            <form id="releaseHoldForm" method="POST" action="" class="space-y-4">
                @csrf
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Alasan Pencabutan Hold (Wajib)') }} <span class="text-error">*</span></span></label>
                    <textarea name="release_reason" rows="3" required class="textarea textarea-bordered w-full text-xs"
                              placeholder="{{ __('Contoh: Pemeriksaan telah selesai dan surat ketetapan telah diterbitkan...') }}"></textarea>
                </div>
                <div class="modal-action">
                    <button type="button" onclick="release_hold_modal.close()" class="btn btn-sm btn-ghost">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-sm btn-success text-white">{{ __('Cabut Pembekuan') }}</button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Request Disposition Modal --}}
    <dialog id="disposition_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ __('Permohonan Penyusutan / Pemusnahan') }}</h3>
            <p class="py-2 text-xs text-base-content/60">
                {{ __('Proses penyusutan memerlukan tinjauan dan persetujuan bertingkat sebelum eksekusi.') }}
            </p>
            <form method="POST" action="{{ route('archives.dispositions.store', $archive->id) }}" class="space-y-4">
                @csrf
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Tindakan Penyusutan') }} <span class="text-error">*</span></span></label>
                    <select name="action_type" required class="select select-sm select-bordered w-full">
                        <option value="destroy">{{ __('Pemusnahan Berkas Fisik (Destroy)') }}</option>
                        <option value="permanent">{{ __('Tetapkan Menjadi Arsip Permanen') }}</option>
                        <option value="extend">{{ __('Perpanjang Masa Simpan Retensi') }}</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Justifikasi / Alasan Penyusutan') }} <span class="text-error">*</span></span></label>
                    <textarea name="justification" rows="3" required class="textarea textarea-bordered w-full text-xs"
                              placeholder="{{ __('Jelaskan justifikasi berdasarkan Jadwal Retensi Arsip...') }}"></textarea>
                </div>
                <div class="modal-action">
                    <button type="button" onclick="disposition_modal.close()" class="btn btn-sm btn-ghost">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Ajukan Permohonan') }}</button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Void Archive Modal --}}
    <dialog id="void_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-error">{{ __('Pembatalan (Void) Nomor Arsip') }}</h3>
            <p class="py-2 text-xs text-base-content/60">
                {{ __('PERINGATAN: Membatalkan rekaman ini akan menandai status void secara permanen. Nomor arsip ini akan dicatat dalam daftar void dan TIDAK PERNAH DAPAT DIGUNAKAN LAGI.') }}
            </p>
            <form method="POST" action="{{ route('archives.void', $archive->id) }}" class="space-y-4">
                @csrf
                <div class="form-control">
                    <label class="label font-bold text-xs"><span class="label-text">{{ __('Alasan Pembatalan Resmi (Wajib)') }} <span class="text-error">*</span></span></label>
                    <textarea name="void_reason" rows="3" required class="textarea textarea-bordered w-full text-xs"
                              placeholder="{{ __('Sebutkan alasan mutlak pembatalan arsip...') }}"></textarea>
                </div>
                <div class="modal-action">
                    <button type="button" onclick="void_modal.close()" class="btn btn-sm btn-ghost">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-sm btn-error text-white">{{ __('Batalkan Arsip (Void)') }}</button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        function openReleaseModal(holdId, caseRef) {
            const form = document.getElementById('releaseHoldForm');
            form.action = `/archive-holds/${holdId}/release`;
            document.getElementById('releaseHoldText').innerText = `Mencabut pembekuan untuk perkara: ${caseRef}`;
            document.getElementById('release_hold_modal').showModal();
        }
    </script>
</x-app-layout>
