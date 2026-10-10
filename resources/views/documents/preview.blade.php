<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
            <span class="min-w-0 font-bold break-words">{{ $document->title }}</span>
            @if($document->document_number)
                <span class="text-xs sm:text-sm font-normal text-base-content/60 shrink-0 font-mono">{{ $document->document_number }}</span>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto w-full px-0">
            @if(session('success'))
                <div class="alert alert-success mb-4 shadow-sm">
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4 shadow-sm">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($document->trashed())
                <div class="alert alert-warning mb-4 shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span class="text-xs sm:text-sm font-semibold">{{ __('Dokumen ini saat ini berada di Tempat Sampah (Trash).') }}</span>
                    </div>
                    @can('restore', $document)
                        <form method="POST" action="{{ route('trash.restore', $document->id) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success text-white font-semibold gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Pulihkan Dokumen') }}
                            </button>
                        </form>
                    @endcan
                </div>
            @endif

            @php
                $isFileBased = $document->displayVersion()?->file_path;
                $pendingVersion = $document->versions->firstWhere('status', 'pending');
            @endphp

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-4 pb-4 border-b border-base-300">
                        <div class="text-sm">
                            <div><span class="text-base-content/60">{{ __('Unit Kerja') }}:</span> {{ $document->unitKerja?->nama_unit_kerja ?? $document->unitKerja?->kode_unit_kerja ?? '—' }}</div>
                            <div class="flex items-center gap-1.5 mt-0.5"><span class="text-base-content/60">{{ __('Pemilik') }}:</span> <x-user-avatar :user="$document->owner" size="w-4 h-4" text-size="text-[9px]" /> <span class="font-medium text-base-content">{{ $document->owner->name }}</span></div>
                            @if($document->isDirectorRead())
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="badge badge-success badge-sm text-white font-bold gap-1 shadow-2xs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        {{ __('Ditinjau oleh Direktur') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        @php
                            $isFileBased = $document->displayVersion()?->file_path;
                            $pendingVersion = $document->versions->firstWhere('status', 'pending');
                        @endphp
                        @php
                            $pendingSigRequest = $document->signatureRequests
                                ->where('target_user_id', auth()->id())
                                ->where('status', 'pending')
                                ->sortByDesc('id')
                                ->first();
                            $isSignatureContext = in_array(request('from'), ['signatures', 'signature_requests'], true) || (bool) $pendingSigRequest;
                            $isApprovalContext = request('from') === 'approvals';

                            $backUrl = match(true) {
                                request('from') === 'trash' || $document->trashed() => route('trash.index'),
                                $isSignatureContext => route('signatures.requests.index'),
                                $isApprovalContext => route('documents.approvals'),
                                request()->routeIs('documents.hash*') && request()->route('token') => route('documents.hash', ['token' => request()->route('token')]),
                                auth()->user()->can('view', $document) => route('documents.show', $document),
                                default => route('dashboard'),
                            };
                        @endphp
                        <div class="flex flex-wrap items-center gap-2">
                            @if($pendingSigRequest)
                                @php
                                    $isStampReq = $pendingSigRequest->isStamp();
                                    $companyName = $isStampReq && $pendingSigRequest->requestedSignature && $pendingSigRequest->requestedSignature->company ? $pendingSigRequest->requestedSignature->company->name : null;
                                @endphp
                                {{-- Approve Button --}}
                                <button type="button" onclick="document.getElementById('approve-sig-modal-{{ $pendingSigRequest->id }}').showModal()" class="btn btn-success btn-sm gap-1.5 font-semibold shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    {{ $isStampReq ? __('Approve Stempel') : __('Approve TTD') }}
                                </button>

                                {{-- Reject Button --}}
                                <button type="button" onclick="document.getElementById('reject-sig-modal-{{ $pendingSigRequest->id }}').showModal()" class="btn btn-error btn-outline btn-sm gap-1.5 font-semibold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ $isStampReq ? __('Reject Stempel') : __('Reject TTD') }}
                                </button>

                                {{-- Custom Approve Confirmation Modal --}}
                                <dialog id="approve-sig-modal-{{ $pendingSigRequest->id }}" class="modal text-left whitespace-normal">
                                    <div class="modal-box max-w-md">
                                        <div class="flex items-center gap-3 text-success mb-3">
                                            <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-lg text-base-content">{{ $isStampReq ? __('Konfirmasi Persetujuan Stempel') : __('Konfirmasi Persetujuan Tanda Tangan') }}</h3>
                                                <p class="text-xs text-base-content/60">{{ $isStampReq ? ($companyName ? __('Stempel Perusahaan :comp', ['comp' => $companyName]) : __('Stempel Perusahaan')) : __('Penyematan tanda tangan digital') }}</p>
                                            </div>
                                        </div>
                                        <p class="text-sm text-base-content/80 py-2">
                                            @if($isStampReq)
                                                {!! __('Apakah Anda yakin ingin menyetujui penggunaan <strong>Stempel Perusahaan :company</strong> Anda oleh <strong>:name</strong> untuk dokumen <strong>:doc</strong>?', [
                                                    'company' => e($companyName ?? 'Perusahaan'),
                                                    'name' => e($pendingSigRequest->requester->name),
                                                    'doc' => e($document->title)
                                                ]) !!}
                                            @else
                                                {!! __('Apakah Anda yakin ingin menyetujui penggunaan tanda tangan Anda oleh <strong>:name</strong> untuk dokumen <strong>:doc</strong>?', [
                                                    'name' => e($pendingSigRequest->requester->name),
                                                    'doc' => e($document->title)
                                                ]) !!}
                                            @endif
                                        </p>
                                        <p class="text-xs text-base-content/60 bg-base-200/50 p-2.5 rounded-lg mb-4">
                                            ℹ️ {{ $isStampReq ? __('Stempel perusahaan akan dibubuhkan secara otomatis ke dalam dokumen ini.') : __('Tanda tangan Anda akan dibubuhkan secara otomatis ke dalam dokumen ini.') }}
                                        </p>
                                        <div class="modal-action">
                                            <button type="button" onclick="document.getElementById('approve-sig-modal-{{ $pendingSigRequest->id }}').close()" class="btn btn-ghost btn-sm">{{ __('Batal') }}</button>
                                            <form method="POST" action="{{ route('signatures.requests.approve', $pendingSigRequest) }}" data-prevent-double-submit="true" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn){ btn.disabled = true; btn.classList.add('opacity-75', 'cursor-not-allowed'); } document.getElementById('approve-sig-modal-{{ $pendingSigRequest->id }}')?.close(); document.getElementById('loading-modal')?.showModal();" class="inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm gap-1.5 font-semibold text-white">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    {{ $isStampReq ? __('Ya, Setujui Stempel') : __('Ya, Setujui TTD') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>close</button>
                                    </form>
                                </dialog>

                                {{-- Custom Reject Modal --}}
                                <dialog id="reject-sig-modal-{{ $pendingSigRequest->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs text-base-content">
                                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg text-base-content">
                                        {{-- Header --}}
                                        <div class="p-6 pb-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="flex items-center gap-3.5">
                                                    <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ $isStampReq ? __('Tolak Penggunaan Stempel') : __('Tolak Penggunaan Tanda Tangan') }}</h3>
                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ $isStampReq ? __('Izin stempel perusahaan tidak akan diberikan.') : __('Izin tanda tangan tidak akan diberikan.') }}</p>
                                                    </div>
                                                </div>
                                                <button type="button" onclick="document.getElementById('reject-sig-modal-{{ $pendingSigRequest->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                    ✕
                                                </button>
                                            </div>

                                            {{-- Target Document Info Box --}}
                                            <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                <div class="p-2 rounded-lg bg-base-100 text-base-content/70 shrink-0 shadow-xs">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="font-semibold text-sm text-base-content break-words">{{ $document->title }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-1.5 text-xs text-base-content/60 mt-1">
                                                        <span>{{ __('Diminta oleh') }}:</span>
                                                        <x-user-avatar :user="$pendingSigRequest->requester" size="w-4 h-4" text-size="text-[9px]" />
                                                        <span class="font-medium text-base-content/80">{{ $pendingSigRequest->requester->name }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Form --}}
                                        <form method="POST" action="{{ route('signatures.requests.reject', $pendingSigRequest) }}" data-prevent-double-submit="true" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn){ btn.disabled = true; btn.classList.add('opacity-75', 'cursor-not-allowed'); }">
                                            @csrf
                                            <div class="px-6 pb-5 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <label for="reject-sig-preview-reason-{{ $pendingSigRequest->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                        {{ __('Alasan Penolakan') }}
                                                    </label>
                                                    <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                </div>
                                                <div class="relative">
                                                    <textarea 
                                                        id="reject-sig-preview-reason-{{ $pendingSigRequest->id }}"
                                                        name="reason" 
                                                        maxlength="500"
                                                        class="textarea textarea-bordered w-full text-sm text-base-content rounded-xl bg-base-200/30 border-base-300 focus:border-error focus:ring-2 focus:ring-error/20 focus:outline-hidden transition-all placeholder:text-base-content/40 leading-relaxed min-h-[95px] p-3" 
                                                        placeholder="{{ $isStampReq ? __('Tuliskan alasan penolakan izin stempel...') : __('Tuliskan alasan penolakan izin tanda tangan...') }}"></textarea>
                                                </div>
                                                <p class="text-[11px] text-base-content/50 flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ $isStampReq ? __('Catatan ini akan dikirimkan ke pemohon izin stempel.') : __('Catatan ini akan dikirimkan ke pemohon izin tanda tangan.') }}
                                                </p>
                                            </div>

                                            {{-- Modal Action Footer --}}
                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                <button type="button" onclick="document.getElementById('reject-sig-modal-{{ $pendingSigRequest->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                    {{ __('Batal') }}
                                                </button>
                                                <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    {{ __('Tolak Permintaan') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>{{ __('Batal') }}</button>
                                    </form>
                                </dialog>
                            @elseif($pendingVersion && auth()->user() && auth()->user()->can('approve', $document))
                                @php
                                    $hasPendingSignature = \App\Models\SignatureRequest::where('document_id', $document->id)
                                        ->where('target_user_id', auth()->id())
                                        ->where('status', 'pending')
                                        ->exists();
                                @endphp
                                <button type="button" onclick="document.getElementById('approve-version-preview-modal-{{ $pendingVersion->id }}').showModal()" class="btn btn-success btn-sm rounded-xl text-white font-semibold gap-1.5 px-4 shadow-xs hover:shadow-md transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    {{ $hasPendingSignature ? __('Setujui & Tandatangani') : __('Setujui Dokumen') }}
                                </button>
                                <button type="button" onclick="document.getElementById('reject-version-preview-modal-{{ $pendingVersion->id }}').showModal()" class="btn btn-error btn-sm rounded-xl text-white font-semibold gap-1 px-3 shadow-xs hover:shadow-md transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ __('Tolak') }}
                                </button>

                                {{-- Reusable Approve Version Modal --}}
                                @include('approvals._approve_modal', [
                                    'document' => $document,
                                    'version' => $pendingVersion,
                                    'modalId' => 'approve-version-preview-modal-' . $pendingVersion->id,
                                    'hasPendingSignature' => $hasPendingSignature,
                                ])

                                {{-- Reject Version Modal --}}
                                <dialog id="reject-version-preview-modal-{{ $pendingVersion->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                        <form method="POST" action="{{ route('approvals.reject', [$document, $pendingVersion]) }}" data-prevent-double-submit="true" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn){ btn.disabled = true; btn.classList.add('opacity-75', 'cursor-not-allowed'); }">
                                            @csrf
                                            <div class="p-6 pb-4">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="flex items-center gap-3.5">
                                                        <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Versi Dokumen') }}</h3>
                                                            <p class="text-xs text-base-content/60 mt-0.5">{{ __('Tolak pengajuan versi v:version dokumen ini', ['version' => $pendingVersion->version_number]) }}</p>
                                                        </div>
                                                    </div>
                                                    <button type="button" onclick="document.getElementById('reject-version-preview-modal-{{ $pendingVersion->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                        ✕
                                                    </button>
                                                </div>

                                                <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                    <div class="min-w-0 flex-1">
                                                        <span class="font-semibold text-sm text-base-content break-words">{{ $document->title }}</span>
                                                        <p class="text-xs text-base-content/60 mt-1">
                                                            {{ __('Penulis Versi') }}: <span class="font-medium text-base-content/80">{{ $pendingVersion->author_name }}</span> &bull; <span class="badge badge-sm badge-ghost font-mono">v{{ $pendingVersion->version_number }}</span>
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <label class="text-xs font-semibold text-base-content uppercase tracking-wider block mb-1">
                                                        {{ __('Alasan Penolakan') }} <span class="text-error">*</span>
                                                    </label>
                                                    <textarea name="notes" rows="3" required class="textarea textarea-bordered textarea-sm w-full rounded-xl text-xs" placeholder="{{ __('Tuliskan alasan penolakan versi dokumen ini...') }}"></textarea>
                                                </div>
                                            </div>

                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                <button type="button" onclick="document.getElementById('reject-version-preview-modal-{{ $pendingVersion->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                    {{ __('Batal') }}
                                                </button>
                                                <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md transition-all flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    {{ __('Tolak Dokumen') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>{{ __('Batal') }}</button>
                                    </form>
                                </dialog>
                            @endif

                            {{-- Modal Cetak & Unduh Dokumen (Dengan / Tanpa Kop Surat) --}}
                            <button type="button" onclick="document.getElementById('export-pdf-modal').showModal()" class="btn btn-ghost btn-sm border border-base-300 gap-1.5 font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                {{ __('Unduh / Cetak') }}
                            </button>

                            <dialog id="export-pdf-modal" class="modal" x-data="{
                                withKop: '{{ $document->hasKop() ? '1' : '0' }}',
                                hasKopDoc: {{ $document->hasKop() ? 'true' : 'false' }},
                                paperSize: '{{ $document->paper_size ?? 'A4' }}',
                                customWidth: '',
                                customHeight: '',
                                customUnit: 'cm',
                                downloadDocx() {
                                    const url = '{{ route('documents.download', $document) }}?with_kop=' + this.withKop;
                                    window.location.href = url;
                                    document.getElementById('export-pdf-modal').close();
                                },
                                applyPrintOnlyOffice() {
                                    document.getElementById('export-pdf-modal').close();
                                    const previewContainer = document.getElementById('docx-preview-{{ $document->latestVersion?->id ?? $document->id }}') || document.querySelector('iframe');
                                    if (previewContainer) {
                                        previewContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    }
                                    setTimeout(() => {
                                        if (window.docEditorPreview) {
                                            try {
                                                if (typeof window.docEditorPreview.print === 'function') {
                                                    window.docEditorPreview.print();
                                                } else if (typeof window.docEditorPreview.serviceCommand === 'function') {
                                                    window.docEditorPreview.serviceCommand('print');
                                                } else if (typeof window.docEditorPreview.executeMethod === 'function') {
                                                    window.docEditorPreview.executeMethod('Print');
                                                }
                                            } catch(e) {
                                                console.warn('ONLYOFFICE print error:', e);
                                            }
                                        }
                                        const iframe = previewContainer ? (previewContainer.querySelector('iframe') || previewContainer) : null;
                                        if (iframe && iframe.contentWindow) {
                                            try {
                                                iframe.contentWindow.postMessage(JSON.stringify({ type: 'onExternalPluginMessage', subType: 'print' }), '*');
                                                iframe.contentWindow.postMessage(JSON.stringify({ type: 'print' }), '*');
                                            } catch(e) {}
                                        }
                                    }, 120);
                                }
                            }">
                                <div class="modal-box max-w-lg max-h-[85vh] overflow-y-auto text-left">
                                    {{-- Header Modal --}}
                                    <div class="flex items-center justify-between pb-3 border-b border-base-200 mb-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-base shadow-xs shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-base text-base-content leading-tight">{{ __('Cetak & Unduh Dokumen') }}</h3>
                                                <p class="text-xs text-base-content/60" x-text="hasKopDoc && withKop === '1' ? '{{ __('Mencetak dokumen resmi dengan kop surat langsung di editor ONLYOFFICE') }}' : '{{ __('Pilih format dan ukuran kertas untuk mengunduh dokumen') }}'"></p>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('export-pdf-modal').close()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Pilihan Kop Surat (Kop Options) - Hanya tampil jika dokumen memiliki Kop Surat --}}
                                    @if($document->hasKop())
                                        <div class="mb-4">
                                            <label class="label pt-0 pb-1.5">
                                                <span class="label-text font-bold text-xs uppercase tracking-wider text-base-content/70">{{ __('Pilihan Kop Surat') }}</span>
                                            </label>
                                            <div class="grid grid-cols-1 gap-2.5">
                                                {{-- Opsi 1: Dengan Kop Surat Resmi --}}
                                                <label class="flex items-start gap-3 p-3.5 rounded-2xl border transition-all cursor-pointer select-none"
                                                       :class="withKop === '1' ? 'bg-primary/10 border-primary shadow-xs ring-1 ring-primary/30' : 'bg-base-100 border-base-300 hover:bg-base-200/50'">
                                                    <input type="radio" name="with_kop_choice_preview" value="1" x-model="withKop" class="radio radio-primary radio-sm mt-0.5 shrink-0">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center justify-between gap-1">
                                                            <span class="text-xs sm:text-sm font-bold text-base-content flex items-center gap-1.5">
                                                                {{ __('Dengan Kop Surat Resmi') }}
                                                            </span>
                                                            <span class="badge badge-primary badge-xs font-semibold shrink-0">{{ __('Cetak Editor') }}</span>
                                                        </div>
                                                        <p class="text-[11px] text-base-content/60 mt-1 leading-normal">
                                                            {{ __('Menyertakan kop surat resmi. Mengarahkan otomatis ke tampilan print editor ONLYOFFICE untuk dicetak di atas kertas polos.') }}
                                                        </p>
                                                    </div>
                                                </label>

                                                {{-- Opsi 2: Tanpa Kop Surat --}}
                                                <label class="flex items-start gap-3 p-3.5 rounded-2xl border transition-all cursor-pointer select-none"
                                                       :class="withKop === '0' ? 'bg-secondary/10 border-secondary shadow-xs ring-1 ring-secondary/30' : 'bg-base-100 border-base-300 hover:bg-base-200/50'">
                                                    <input type="radio" name="with_kop_choice_preview" value="0" x-model="withKop" class="radio radio-secondary radio-sm mt-0.5 shrink-0">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center justify-between gap-1">
                                                            <span class="text-xs sm:text-sm font-bold text-base-content flex items-center gap-1.5">
                                                                {{ __('Tanpa Kop Surat (Kertas Kop Fisik)') }}
                                                            </span>
                                                            <span class="badge badge-ghost badge-xs font-semibold shrink-0 border-base-300">{{ __('Unduh Word / PDF') }}</span>
                                                        </div>
                                                        <p class="text-[11px] text-base-content/60 mt-1 leading-normal">
                                                            {{ __('Header kop surat dilepas otomatis. Anda dapat mengunduh format Word (.docx) atau PDF untuk dicetak pada kertas fisik yang sudah tercetak kopnya.') }}
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Form Ekspor PDF & Download Actions --}}
                                    <form id="form-export-pdf-preview" method="POST" action="{{ route('documents.export-pdf', $document) }}">
                                        @csrf
                                        <input type="hidden" name="with_kop" :value="withKop">

                                        {{-- Format Kertas Cetak PDF (Hanya tampil saat tanpa kop atau dokumen tanpa kop) --}}
                                        <div x-show="!hasKopDoc || withKop === '0'" x-transition class="form-control w-full mb-4 bg-base-200/40 p-3 rounded-2xl border border-base-200">
                                            <label class="label py-0 pb-1.5">
                                                <span class="label-text font-bold text-xs text-base-content/80">{{ __('Ukuran Kertas Cetak PDF') }}</span>
                                                <span class="label-text-alt text-[11px] text-primary font-semibold">{{ __('Default Dokumen: :size', ['size' => $document->paper_size ?? 'A4']) }}</span>
                                            </label>
                                            <select name="paper_size" x-model="paperSize" class="select select-bordered select-sm w-full bg-base-100">
                                                <option value="A4">A4 (21 x 29.7 cm)</option>
                                                <option value="F4">F4 (21 x 33 cm) — {{ __('Folio / Standar') }}</option>
                                                <option value="Letter">Letter (8.5" x 11" / 21.59 x 27.94 cm)</option>
                                                <option value="Legal">Legal (8.5" x 14" / 21.59 x 35.56 cm)</option>
                                                <option value="A5">A5 (14.8 x 21 cm)</option>
                                                <option value="A3">A3 (29.7 x 42 cm)</option>
                                                <option value="Custom">{{ __('Custom Size (Ukuran Khusus)') }}</option>
                                            </select>

                                            {{-- Custom Size Inputs --}}
                                            <div x-show="paperSize === 'Custom'" x-transition class="mt-2.5 p-2.5 bg-base-100 rounded-xl border border-base-300 space-y-2">
                                                <div class="text-[11px] font-semibold text-base-content/80 flex items-center justify-between">
                                                    <span>{{ __('Dimensi Ukuran Kustom') }}</span>
                                                    <div class="flex items-center gap-2">
                                                        <label class="text-xs font-normal cursor-pointer flex items-center gap-1">
                                                            <input type="radio" name="custom_unit" value="cm" x-model="customUnit" class="radio radio-primary radio-xs"> cm
                                                        </label>
                                                        <label class="text-xs font-normal cursor-pointer flex items-center gap-1">
                                                            <input type="radio" name="custom_unit" value="mm" x-model="customUnit" class="radio radio-primary radio-xs"> mm
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div class="form-control">
                                                        <label class="label py-0.5"><span class="label-text text-[10px]">{{ __('Lebar') }} (<span x-text="customUnit"></span>)</span></label>
                                                        <input type="number" step="0.1" min="0.1" name="custom_width" x-model="customWidth" :required="paperSize === 'Custom'" placeholder="Contoh: 21" class="input input-bordered input-xs w-full">
                                                    </div>
                                                    <div class="form-control">
                                                        <label class="label py-0.5"><span class="label-text text-[10px]">{{ __('Tinggi') }} (<span x-text="customUnit"></span>)</span></label>
                                                        <input type="number" step="0.1" min="0.1" name="custom_height" x-model="customHeight" :required="paperSize === 'Custom'" placeholder="Contoh: 33" class="input input-bordered input-xs w-full">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Action Buttons --}}
                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 pt-3 border-t border-base-200">
                                            <button type="button" class="btn btn-ghost btn-sm order-last sm:order-first" onclick="document.getElementById('export-pdf-modal').close()">{{ __('Batal') }}</button>
                                            
                                            {{-- Jika Dengan Kop Surat: Tombol Terapkan & Cetak Dokumen ke Editor ONLYOFFICE --}}
                                            <div x-show="hasKopDoc && withKop === '1'" class="flex items-center justify-end gap-2">
                                                <button type="button" @click="applyPrintOnlyOffice()" class="btn btn-primary btn-sm gap-1.5 font-medium shadow-xs" title="{{ __('Lanjut ke tampilan print editor ONLYOFFICE') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                                    {{ __('Terapkan & Cetak Dokumen') }}
                                                </button>
                                            </div>

                                            {{-- Jika Tanpa Kop Surat atau dokumen tidak ada kop: Tombol Unduh Word & Unduh PDF --}}
                                            <div x-show="!hasKopDoc || withKop === '0'" class="flex flex-wrap sm:flex-nowrap items-center gap-2 justify-end">
                                                <button type="button" @click="downloadDocx()" class="btn btn-outline btn-sm gap-1.5 font-medium shadow-xs" title="{{ __('Unduh file Word DOCX tanpa kop') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                    {{ __('Unduh Word') }}
                                                </button>
                                                <button type="submit" class="btn btn-primary btn-sm gap-1.5 font-medium shadow-xs" title="{{ __('Unduh file PDF tanpa kop') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                    {{ __('Unduh PDF') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <form method="dialog" class="modal-backdrop">
                                    <button>close</button>
                                </form>
                            </dialog>

                            {{-- Edit Dokumen: only when NOT in signature review context, NOT locked, and user has edit permissions --}}
                            @if(!$isSignatureContext && auth()->user()->can('edit', $document))
                                <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    {{ __('Edit Dokumen') }}
                                </a>
                            @endif

                            {{-- Director Seen Quick Action Button --}}
                            @if(auth()->user() && (auth()->user()->isDirector() || auth()->user()->isAdmin()))
                                <form method="POST" action="{{ route('director.documents.acknowledge', $document) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="{{ $document->isDirectorRead() ? 'unseen' : 'seen' }}">
                                    <button type="submit" 
                                            class="btn btn-sm gap-1.5 {{ $document->isDirectorRead() ? 'btn-ghost text-base-content/60 hover:text-error' : 'btn-success text-white shadow-xs' }}" 
                                            title="{{ $document->isDirectorRead() ? __('Batalkan status tinjauan Direktur') : __('Tandai telah ditinjau oleh Direktur') }}">
                                        @if($document->isDirectorRead())
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            <span>{{ __('Batal Ditinjau') }}</span>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                            <span>{{ __('Tandai Ditinjau') }}</span>
                                        @endif
                                    </button>
                                </form>
                            @endif

                            <a href="{{ $backUrl }}" class="btn btn-ghost btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                {{ __('Kembali') }}
                            </a>
                        </div>
                    </div>

                    @if(session('pdf_export'))
                        <div class="alert alert-success mt-3">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 w-full">
                                <span>{{ __('PDF berhasil dibuat.') }} <span class="font-medium">{{ session('pdf_export.filename') }}</span></span>
                                <a href="{{ session('pdf_export.url') }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm shrink-0">
                                    {{ __('Unduh PDF') }}
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($errors->has('export'))
                        <div class="alert alert-error mb-6">
                            <span>{{ $errors->first('export') }} {{ __('Silakan coba lagi.') }}</span>
                        </div>
                    @endif

                    <div id="live-preview-content">
                        @php $display = $document->displayVersion(); @endphp
                        @if($display && $display->file_path)
                            @include('documents._file-preview', ['document' => $document, 'version' => $display])
                        @elseif($display)
                            @include('documents._paper', [
                                'content' => $display->content,
                                'document' => $document,
                                'liveStorage' => 'doc-preview-' . $document->id,
                                'paperSize' => $document->paper_size ?? 'A4',
                                'paperMargin' => $document->paper_margin,
                            ])
                        @else
                            <p class="text-base-content/60 italic">{{ __('Belum ada konten yang disetujui.') }}</p>
                        @endif
                    </div>

                    {{-- Live sync: konten dari tab editor via localStorage --}}
                    <script>
                        (function () {
                            const key = 'doc-preview-{{ $document->id }}';
                            const target = document.getElementById('live-preview-content');
                            if (!target) return;

                            function render(html) {
                                if (html && html.trim().length) {
                                    // Bungkus dengan struktur yang SAMA PERSIS seperti partial _paper:
                                    // .doku-paper-scope > .doku-paper, karena semua CSS kertas A4
                                    // (garis pembatas halaman, font, padding) di-scope lewat
                                    // selector ".doku-paper-scope .doku-paper". Kalau wrapper
                                    // scope ini hilang, style-nya nggak ke-apply sama sekali.
                                    const scope = document.createElement('div');
                                    scope.className = 'doku-paper-scope';
                                    scope.dataset.liveStorage = 'doc-preview-{{ $document->id }}';
                                    scope.dataset.paperSize = '{{ $document->paper_size ?? "A4" }}';
                                    scope.dataset.paperMargin = '{{ json_encode($document->paper_margin) }}';

                                    const paper = document.createElement('div');
                                    paper.className = 'doku-paper';
                                    paper.innerHTML = html;

                                    scope.appendChild(paper);
                                    target.innerHTML = '';
                                    target.appendChild(scope);

                                    // Terapkan batas antar halaman sesuai ukuran kertas yang
                                    // aktif di editor (dibaca dari localStorage).
                                    if (window.__initPreviewPagination) {
                                        window.__initPreviewPagination(scope);
                                    }
                                }
                            }

                            // Hanya render draft kalau benar-benar ada konten (bukan cuma <p><br></p>)
                            function hasRealContent(html) {
                                if (!html) return false;
                                const el = document.createElement('div');
                                el.innerHTML = html;
                                return el.textContent.trim().length > 0 || el.querySelector('img, table, iframe');
                            }

                            // Konten terakhir yang tersimpan di editor (kalau ada)
                            const draft = localStorage.getItem(key);
                            if (hasRealContent(draft)) render(draft);

                            // Tab editor menulis → storage event → update realtime
                            window.addEventListener('storage', (e) => {
                                if (e.key === key && hasRealContent(e.newValue)) {
                                    render(e.newValue);
                                }
                            });
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading Modal with Shimmer --}}
    <dialog id="loading-modal" class="modal">
        <div class="modal-box flex flex-col items-center justify-center p-8 sm:p-10 max-w-sm rounded-3xl border border-base-300 shadow-2xl relative overflow-hidden bg-base-100/95 backdrop-blur-md">
            <div class="relative w-16 h-16 flex items-center justify-center mb-4">
                <div class="relative w-14 h-14 rounded-full border-3 border-base-300 border-t-primary animate-spin flex items-center justify-center shadow-md" style="animation-duration: 1.2s;">
                    <div class="w-full h-full bg-base-100 rounded-full"></div>
                </div>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <img src="{{ asset('logo.webp') }}" alt="DokuFlow" class="w-7 h-7 object-contain animate-loading-float drop-shadow-sm" />
                </div>
            </div>
            <h3 class="font-bold text-base text-base-content text-center">{{ __('Memproses Dokumen...') }}</h3>
            <p class="text-xs text-base-content/60 mt-1 text-center max-w-xs leading-relaxed">{{ __('Harap tunggu sebentar, sistem sedang membubuhkan tanda tangan Anda ke dalam dokumen secara otomatis.') }}</p>
            <div class="w-40 h-1.5 rounded-full overflow-hidden mt-4 bg-base-200 border border-base-300/60">
                <div class="shimmer-brand w-full h-full"></div>
            </div>
        </div>
    </dialog>
</x-app-layout>