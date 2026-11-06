<x-app-layout>
    <x-slot name="header">{{ __('Detail Permohonan Pengarsipan') }}</x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto w-full space-y-6">

            {{-- Top Navigation --}}
            <div class="flex items-center justify-between">
                <a href="{{ route('archive-requests.index') }}" class="btn btn-ghost btn-sm gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Kembali ke Antrean Permohonan') }}
                </a>

                <div class="flex items-center gap-2">
                    @if($archiveRequest->isDraft())
                        <span class="badge badge-ghost font-bold">{{ __('Draf') }}</span>
                    @elseif($archiveRequest->isPendingApproval())
                        <span class="badge badge-warning font-bold">{{ __('Menunggu Persetujuan') }}</span>
                    @elseif($archiveRequest->isApproved())
                        <span class="badge badge-info font-bold">{{ __('Disetujui — Siap Finalisasi') }}</span>
                    @elseif($archiveRequest->isRegistered())
                        <span class="badge badge-success font-bold text-white">{{ __('Telah Terdaftar Resmi') }}</span>
                    @elseif($archiveRequest->state === 'rejected')
                        <span class="badge badge-error font-bold text-white">{{ __('Ditolak') }}</span>
                    @elseif($archiveRequest->state === 'returned_for_correction')
                        <span class="badge badge-warning font-bold">{{ __('Dikembalikan untuk Perbaikan') }}</span>
                    @endif
                </div>
            </div>

            {{-- Flash Alerts --}}
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

            {{-- Main Details Card --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm p-6 space-y-6">
                <div>
                    <div class="text-xs font-bold text-base-content/50 uppercase tracking-wider">{{ __('Permohonan Pengarsipan #:id', ['id' => $archiveRequest->id]) }}</div>
                    <h2 class="text-2xl font-black text-base-content mt-1">{{ $archiveRequest->archive_title }}</h2>
                </div>

                {{-- Source & Metadata Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-xl bg-base-200/50 text-xs">
                    <div>
                        <div class="text-base-content/50">{{ __('Dokumen Asli') }}</div>
                        <div class="font-bold text-base-content mt-0.5">
                            @if($archiveRequest->document)
                                <a href="{{ route('documents.show', $archiveRequest->document->id) }}" class="link link-primary">
                                    {{ $archiveRequest->document->document_number ?? $archiveRequest->document->title }}
                                </a>
                            @else
                                <span class="text-base-content/40">-</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-base-content/50">{{ __('Versi Sumber') }}</div>
                        <div class="font-bold text-base-content mt-0.5">
                            v{{ $archiveRequest->requestedVersion?->version_number ?? 1 }} ({{ $archiveRequest->requestedVersion?->status ?? 'active' }})
                        </div>
                    </div>

                    <div>
                        <div class="text-base-content/50">{{ __('Klasifikasi') }}</div>
                        <div class="font-bold text-base-content mt-0.5">
                            {{ $archiveRequest->classification?->code }} - {{ $archiveRequest->classification?->name }}
                        </div>
                    </div>

                    <div>
                        <div class="text-base-content/50">{{ __('Tingkat Keamanan') }}</div>
                        <div class="font-bold text-base-content mt-0.5">
                            <span class="badge badge-outline badge-xs uppercase">{{ $archiveRequest->sensitivity_level }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-base-content/50">{{ __('Pemohon') }}</div>
                        <div class="font-bold text-base-content mt-0.5">
                            {{ $archiveRequest->requester?->name }}
                        </div>
                    </div>

                    <div>
                        <div class="text-base-content/50">{{ __('Unit / Cabang') }}</div>
                        <div class="font-bold text-base-content mt-0.5">
                            {{ $archiveRequest->branch?->code }} / {{ $archiveRequest->unitKerja?->kode_unit_kerja }}
                        </div>
                    </div>

                    <div>
                        <div class="text-base-content/50">{{ __('Jadwal Retensi (JRA)') }}</div>
                        <div class="font-bold text-base-content mt-0.5">
                            {{ $archiveRequest->retentionPolicy?->code ?? __('Bawaan') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-base-content/50">{{ __('Tanggal Diajukan') }}</div>
                        <div class="font-bold text-base-content mt-0.5">
                            {{ $archiveRequest->created_at?->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>

                {{-- Description / Reason --}}
                @if($archiveRequest->archive_reason)
                    <div>
                        <div class="text-xs font-bold text-base-content/60 uppercase tracking-wider mb-1">{{ __('Alasan Pengarsipan') }}</div>
                        <div class="p-3 rounded-lg bg-base-200/30 text-sm text-base-content/80 whitespace-pre-line">
                            {{ $archiveRequest->archive_reason }}
                        </div>
                    </div>
                @endif

                {{-- Decision Notes (if any) --}}
                @if($archiveRequest->decision_notes)
                    <div>
                        <div class="text-xs font-bold text-base-content/60 uppercase tracking-wider mb-1">{{ __('Catatan Keputusan / Review') }}</div>
                        <div class="p-3 rounded-lg bg-info/10 text-sm text-info-content whitespace-pre-line">
                            {{ $archiveRequest->decision_notes }}
                        </div>
                    </div>
                @endif

                {{-- Registered Archive Entry Banner --}}
                @if($archiveRequest->archiveEntry)
                    <div class="alert alert-success shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div class="flex-1">
                            <div class="font-bold">{{ __('Arsip Resmi Telah Terbit!') }}</div>
                            <div class="font-mono text-sm mt-0.5">{{ $archiveRequest->archiveEntry->archive_number }}</div>
                        </div>
                        <a href="{{ route('archives.show', $archiveRequest->archiveEntry->id) }}" class="btn btn-sm bg-white text-success border-0">
                            {{ __('Buka Buku Induk') }}
                        </a>
                    </div>
                @elseif($archiveRequest->isApproved() || $archiveRequest->state === 'ready_to_register' || $archiveRequest->state === 'failed' || $archiveRequest->state === 'preparing_snapshot')
                    <div class="alert {{ $archiveRequest->state === 'failed' ? 'alert-error' : 'alert-info' }} shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div class="flex-1">
                            <div class="font-bold">
                                @if($archiveRequest->state === 'failed')
                                    {{ __('Finalisasi Pendaftaran Mengalami Kendala') }}
                                @else
                                    {{ __('Permohonan Telah Disetujui') }}
                                @endif
                            </div>
                            <div class="text-xs mt-0.5">
                                @if($archiveRequest->state === 'failed' && $archiveRequest->last_error_message)
                                    <span class="font-mono text-[11px] block opacity-90 mb-1">{{ $archiveRequest->last_error_message }}</span>
                                @endif
                                {{ __('Lakukan finalisasi untuk menerbitkan nomor arsip resmi dan snapshot berkas imutabel.') }}
                            </div>
                        </div>
                        @if($canFinalize)
                            <form method="POST" action="{{ route('archive-requests.finalize', $archiveRequest->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    {{ $archiveRequest->state === 'failed' ? __('Coba Finalisasi Ulang') : __('Finalisasi Pendaftaran') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                {{-- Workflow Action Buttons Bar --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-base-200">
                    <div class="flex items-center gap-2">
                        @if($canCancel)
                            <form method="POST" action="{{ route('archive-requests.cancel', $archiveRequest->id) }}" onsubmit="return confirm('{{ __('Batalkan permohonan pengarsipan ini?') }}');">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm text-error">
                                    {{ __('Batalkan Permohonan') }}
                                </button>
                            </form>
                        @endif
                        @can('delete', $archiveRequest)
                            <form method="POST" action="{{ route('archive-requests.destroy', $archiveRequest->id) }}" onsubmit="return confirm('{{ __('Hapus permanen permohonan ini?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-error btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    {{ __('Hapus Permohonan') }}
                                </button>
                            </form>
                        @endcan
                    </div>

                    <div class="flex items-center gap-2">
                        @if($canSubmit && $archiveRequest->isEditable())
                            <form method="POST" action="{{ route('archive-requests.submit', $archiveRequest->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm px-6">
                                    {{ __('Ajukan Permohonan') }}
                                </button>
                            </form>
                        @endif

                        @if($canApprove && $archiveRequest->isPendingApproval())
                            {{-- Return for Correction Modal Button --}}
                            <button type="button" onclick="return_modal.showModal()" class="btn btn-warning btn-sm">
                                {{ __('Minta Perbaikan') }}
                            </button>

                            {{-- Reject Modal Button --}}
                            <button type="button" onclick="reject_modal.showModal()" class="btn btn-error btn-sm">
                                {{ __('Tolak') }}
                            </button>

                            {{-- Approve Action --}}
                            <form method="POST" action="{{ route('archive-requests.approve', $archiveRequest->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm text-white px-6">
                                    {{ __('Setujui Permohonan') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Return Modal --}}
    <dialog id="return_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ __('Kembalikan untuk Perbaikan') }}</h3>
            <p class="py-2 text-xs text-base-content/60">{{ __('Berikan instruksi perbaikan yang harus dilakukan pemohon.') }}</p>
            <form method="POST" action="{{ route('archive-requests.return', $archiveRequest->id) }}" class="space-y-4">
                @csrf
                <textarea name="notes" rows="4" required class="textarea textarea-bordered w-full"
                          placeholder="{{ __('Jelaskan bagian yang perlu diperbaiki...') }}"></textarea>
                <div class="modal-action">
                    <button type="button" onclick="return_modal.close()" class="btn btn-ghost btn-sm">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-warning btn-sm">{{ __('Kirim Instruksi Perbaikan') }}</button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Reject Modal --}}
    <dialog id="reject_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-error">{{ __('Tolak Permohonan Pengarsipan') }}</h3>
            <p class="py-2 text-xs text-base-content/60">{{ __('Alasan penolakan akan dicatat secara permanen dalam riwayat audit.') }}</p>
            <form method="POST" action="{{ route('archive-requests.reject', $archiveRequest->id) }}" class="space-y-4">
                @csrf
                <textarea name="notes" rows="4" required class="textarea textarea-bordered w-full"
                          placeholder="{{ __('Sebutkan alasan penolakan...') }}"></textarea>
                <div class="modal-action">
                    <button type="button" onclick="reject_modal.close()" class="btn btn-ghost btn-sm">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-error btn-sm">{{ __('Tolak Permohonan') }}</button>
                </div>
            </form>
        </div>
    </dialog>
</x-app-layout>

