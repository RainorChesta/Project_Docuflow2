<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-base-content leading-tight">
                    {{ __('Persetujuan Penghapusan Dokumen Aktif') }}
                </h2>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ __('Tinjau permohonan penghapusan dokumen aktif dan evaluasi kesinambungan nomor urut surat sebelum menyetujui.') }}
                </p>
            </div>
            @if(($counts['deletions'] ?? $pendingDeletions->total()) > 0)
            <div class="flex items-center gap-2 shrink-0">
                <span class="badge badge-error font-bold text-white gap-1.5 text-xs py-2.5 px-3 shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $counts['deletions'] ?? $pendingDeletions->total() }} {{ __('Menunggu') }}
                </span>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        @if(session('success'))
            <div class="alert alert-success shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error shadow-sm">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Filter and Search Controls Bar --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3.5">
                <div class="flex items-center gap-2.5 shrink-0">
                    <span class="text-sm font-semibold text-base-content/90 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ __('Daftar Permohonan Hapus Dokumen') }}
                    </span>
                    @if($pendingDeletions->total() > 0)
                        <span class="badge badge-sm badge-error font-bold text-white">{{ $pendingDeletions->total() }}</span>
                    @endif
                </div>

                {{-- Search Box & Per-Page Controls --}}
                <form method="GET" action="{{ route('approvals.deletions') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full lg:w-auto justify-end">
                    <div class="relative w-full sm:w-80 md:w-96 min-w-[240px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="search" 
                               value="{{ $search ?? '' }}" 
                               placeholder="{{ __('Cari judul, nomor dokumen, pemohon, alasan...') }}" 
                               class="input input-sm input-bordered w-full pl-9 pr-8 text-sm rounded-xl bg-base-200/50 focus:bg-base-100 transition-colors" />
                        @if(!empty($search))
                            <a href="{{ route('approvals.deletions', request()->except('search')) }}" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-base-content/40 hover:text-base-content transition-colors" title="{{ __('Hapus pencarian') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <select name="per_page" onchange="this.form.submit()" class="select select-sm select-bordered text-xs rounded-xl bg-base-200/50 shrink-0 w-auto font-medium">
                            <option value="10" {{ ($perPage ?? 15) == 10 ? 'selected' : '' }}>10 / hal</option>
                            <option value="15" {{ ($perPage ?? 15) == 15 ? 'selected' : '' }}>15 / hal</option>
                            <option value="25" {{ ($perPage ?? 15) == 25 ? 'selected' : '' }}>25 / hal</option>
                            <option value="50" {{ ($perPage ?? 15) == 50 ? 'selected' : '' }}>50 / hal</option>
                        </select>

                        <button type="submit" class="btn btn-sm btn-primary rounded-xl text-xs font-semibold px-4 shrink-0 shadow-xs flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            {{ __('Cari') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Pending Document Deletion Requests Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-base-200/50 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Permohonan Penghapusan Menunggu Persetujuan') }}
                            @if($pendingDeletions->total() > 0)
                                <span class="badge badge-error badge-sm font-semibold text-white">{{ $pendingDeletions->total() }}</span>
                            @endif
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Persetujuan penghapusan memindahkan dokumen ke Tempat Sampah (Soft Delete).') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($pendingDeletions->isEmpty())
                <div class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-3xl bg-base-200/70 border border-base-300/60 flex items-center justify-center mx-auto mb-3.5 shadow-2xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-sm text-base-content mb-1">{{ __('Semua Permohonan Penghapusan Selesai!') }}</h4>
                    <p class="text-xs text-base-content/50 max-w-sm mx-auto leading-relaxed">
                        @if(!empty($search))
                            {{ __('Tidak ada permohonan penghapusan yang cocok dengan kata kunci pencarian Anda.') }}
                        @else
                            {{ __('Saat ini tidak ada permohonan penghapusan dokumen yang menunggu persetujuan Administrator.') }}
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full text-sm">
                        <thead>
                            <tr class="bg-base-200/40 border-b border-base-300 text-base-content/70 text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Pertimbangan Penomoran') }}</th>
                                <th class="py-3 px-4">{{ __('Pemohon & Alasan') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu Pengajuan') }}</th>
                                <th class="py-3 px-4 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-300">
                            @foreach($pendingDeletions as $doc)
                                @php
                                    $seqContext = app(\App\Services\DocumentService::class)->getNumberingSequenceContext($doc);
                                @endphp
                                <tr class="hover:bg-base-200/30 transition-colors">
                                    {{-- Document Info --}}
                                    <td class="py-3.5 px-4 min-w-[240px]">
                                        <div class="font-bold text-base-content leading-snug hover:text-primary transition-colors">
                                            <a href="{{ route('documents.show', $doc) }}">
                                                {{ $doc->title }}
                                            </a>
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                            <span class="badge badge-ghost badge-xs font-mono font-medium text-base-content/70 border-base-300">
                                                {{ $doc->document_number ?? '—' }}
                                            </span>
                                            @if($doc->unitKerja)
                                                <span class="badge badge-ghost badge-xs text-base-content/60">
                                                    {{ $doc->unitKerja->nama_unit_kerja }}
                                                </span>
                                            @endif
                                            @if($doc->branch)
                                                <span class="badge badge-outline badge-xs text-base-content/50">
                                                    {{ $doc->branch->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Numbering Consideration Context --}}
                                    <td class="py-3.5 px-4 min-w-[280px]">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="text-base-content/50 shrink-0 w-16">{{ __('Sebelum:') }}</span>
                                                @if($seqContext['previous_document'])
                                                    <span class="font-mono text-base-content/80 font-medium truncate" title="{{ $seqContext['previous_document']['title'] }}">
                                                        {{ $seqContext['previous_document']['document_number'] }}
                                                    </span>
                                                @else
                                                    <span class="text-base-content/40 italic">{{ __('Tidak ada (awal seri)') }}</span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="text-base-content/50 shrink-0 w-16">{{ __('Sesudah:') }}</span>
                                                @if($seqContext['next_document'])
                                                    <span class="font-mono text-base-content/80 font-medium truncate" title="{{ $seqContext['next_document']['title'] }}">
                                                        {{ $seqContext['next_document']['document_number'] }}
                                                    </span>
                                                @else
                                                    <span class="text-base-content/40 italic">{{ __('Tidak ada (nomor terakhir)') }}</span>
                                                @endif
                                            </div>

                                            <div>
                                                @if($seqContext['has_gap_risk'])
                                                    <span class="inline-flex items-center gap-1 badge badge-warning badge-xs font-semibold py-1.5 px-2 text-amber-950">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-amber-900" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        {{ __('Risiko Celah Nomor Lompat') }}
                                                    </span>
                                                @elseif($seqContext['is_latest'])
                                                    <span class="inline-flex items-center gap-1 badge badge-success badge-xs font-semibold py-1.5 px-2 text-white">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        {{ __('Nomor Urut Terakhir (Aman)') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Requester & Notes --}}
                                    <td class="py-3.5 px-4 min-w-[220px]">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <x-user-avatar :user="$doc->deletionRequestedBy" size="w-6 h-6" text-size="text-[10px]" />
                                            <span class="font-medium text-xs text-base-content truncate">
                                                {{ $doc->deletionRequestedBy?->name ?? '—' }}
                                            </span>
                                        </div>
                                        @if($doc->deletion_request_notes)
                                            <div class="text-xs text-base-content/70 bg-base-200/60 p-2 rounded-lg border border-base-300/40 line-clamp-2" title="{{ $doc->deletion_request_notes }}">
                                                "{{ $doc->deletion_request_notes }}"
                                            </div>
                                        @else
                                            <span class="text-xs text-base-content/40 italic">{{ __('Tanpa catatan') }}</span>
                                        @endif
                                    </td>

                                    {{-- Submission Time --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap text-xs text-base-content/60">
                                        {{ $doc->deletion_requested_at ? $doc->deletion_requested_at->diffForHumans() : ($doc->updated_at ? $doc->updated_at->diffForHumans() : '—') }}
                                    </td>

                                    {{-- Action Buttons --}}
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            {{-- Detail Link --}}
                                            <a href="{{ route('documents.show', $doc) }}" 
                                               class="btn btn-ghost btn-xs text-base-content/70 hover:text-base-content" 
                                               title="{{ __('Lihat Detail') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                {{ __('Detail') }}
                                            </a>

                                            {{-- Reject Button --}}
                                            <button type="button" 
                                                    onclick="document.getElementById('reject-deletion-modal-{{ $doc->id }}').showModal()" 
                                                    class="btn btn-ghost btn-xs text-base-content/60 hover:text-error hover:bg-error/10" 
                                                    title="{{ __('Tolak Permohonan') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                {{ __('Tolak') }}
                                            </button>

                                            {{-- Approve Button --}}
                                            <button type="button" 
                                                    onclick="document.getElementById('approve-deletion-modal-{{ $doc->id }}').showModal()" 
                                                    class="btn btn-error btn-xs text-white shadow-2xs font-semibold gap-1" 
                                                    title="{{ __('Setujui Penghapusan') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                {{ __('Setujui Hapus') }}
                                            </button>
                                        </div>

                                        {{-- Modal Approve Deletion --}}
                                        <dialog id="approve-deletion-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                <div class="p-6 pb-4">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div class="flex items-center gap-3.5">
                                                            <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Penghapusan Dokumen?') }}</h3>
                                                                <p class="text-xs text-base-content/60 mt-0.5">{{ __('Dokumen akan dipindahkan ke Tempat Sampah (Soft Delete).') }}</p>
                                                            </div>
                                                        </div>
                                                        <button type="button" onclick="document.getElementById('approve-deletion-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                            ✕
                                                        </button>
                                                    </div>

                                                    <div class="mt-4 p-3.5 bg-base-200/50 rounded-xl border border-base-200 space-y-2 text-xs">
                                                        <div class="flex justify-between">
                                                            <span class="text-base-content/60">{{ __('Dokumen:') }}</span>
                                                            <span class="font-bold text-base-content text-right">{{ $doc->title }}</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-base-content/60">{{ __('Nomor:') }}</span>
                                                            <span class="font-mono font-medium text-base-content">{{ $doc->document_number ?? '—' }}</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-base-content/60">{{ __('Pemohon:') }}</span>
                                                            <span class="font-medium text-base-content">{{ $doc->deletionRequestedBy?->name ?? '—' }}</span>
                                                        </div>
                                                        @if($doc->deletion_request_notes)
                                                        <div class="pt-2 border-t border-base-300/40">
                                                            <span class="text-base-content/60 block mb-1">{{ __('Alasan Pemohon:') }}</span>
                                                            <div class="p-2 bg-base-100 rounded text-base-content/80 font-normal">
                                                                "{{ $doc->deletion_request_notes }}"
                                                            </div>
                                                        </div>
                                                        @endif
                                                    </div>

                                                    {{-- Sequence warning box --}}
                                                    <div class="mt-3.5 p-3 rounded-xl border {{ $seqContext['has_gap_risk'] ? 'bg-warning/10 border-warning/30 text-amber-950 dark:text-amber-200' : 'bg-info/10 border-info/30 text-info-content' }} text-xs">
                                                        <div class="font-bold mb-1 flex items-center gap-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                            </svg>
                                                            {{ __('Pertimbangan Penomoran:') }}
                                                        </div>
                                                        <p class="leading-relaxed">{{ $seqContext['warning_message'] }}</p>
                                                    </div>
                                                </div>

                                                <form method="POST" action="{{ route('approvals.deletion-request.approve', $doc) }}">
                                                    @csrf
                                                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                        <button type="button" onclick="document.getElementById('approve-deletion-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                            {{ __('Batal') }}
                                                        </button>
                                                        <button type="submit" class="btn btn-error btn-sm text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                            {{ __('Setujui & Hapus') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                            <form method="dialog" class="modal-backdrop">
                                                <button>{{ __('Batal') }}</button>
                                            </form>
                                        </dialog>

                                        {{-- Modal Reject Deletion --}}
                                        <dialog id="reject-deletion-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-md">
                                                <form method="POST" action="{{ route('approvals.deletion-request.reject', $doc) }}">
                                                    @csrf
                                                    <div class="p-6 pb-4">
                                                        <div class="flex items-start justify-between gap-4">
                                                            <div class="flex items-center gap-3.5">
                                                                <div class="w-11 h-11 rounded-2xl bg-warning/10 text-warning flex items-center justify-center shrink-0 ring-4 ring-warning/5 shadow-xs">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Permohonan Hapus') }}</h3>
                                                                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Dokumen akan tetap aktif dan pemohon akan menerima notifikasi.') }}</p>
                                                                </div>
                                                            </div>
                                                            <button type="button" onclick="document.getElementById('reject-deletion-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                ✕
                                                            </button>
                                                        </div>

                                                        <div class="mt-4">
                                                            <label class="label text-xs font-semibold text-base-content/80">
                                                                <span>{{ __('Alasan Penolakan') }}</span>
                                                                <span class="label-text-alt text-base-content/50">{{ __('Opsional') }}</span>
                                                            </label>
                                                            <textarea name="notes" rows="3" class="textarea textarea-bordered w-full text-xs rounded-xl bg-base-200/50 focus:bg-base-100" placeholder="{{ __('Tuliskan alasan penolakan permohonan penghapusan...') }}"></textarea>
                                                        </div>
                                                    </div>

                                                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                        <button type="button" onclick="document.getElementById('reject-deletion-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                            {{ __('Batal') }}
                                                        </button>
                                                        <button type="submit" class="btn btn-warning btn-sm font-semibold rounded-xl px-5 shadow-xs flex items-center gap-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            {{ __('Tolak Permohonan') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                            <form method="dialog" class="modal-backdrop">
                                                <button>{{ __('Batal') }}</button>
                                            </form>
                                        </dialog>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($pendingDeletions->hasPages())
                    <div class="px-5 py-4 border-t border-base-200">
                        {{ $pendingDeletions->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>

