<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
            <span class="min-w-0 font-bold break-words">{{ $document->title }} — v{{ $version->version_number }}</span>
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



            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-4 pb-4 border-b border-base-300">
                        <div class="text-sm">
                            <div><span class="text-base-content/60">{{ __('Versi') }}:</span> v{{ $version->version_number }}</div>
                            <div><span class="text-base-content/60">{{ __('Penulis') }}:</span> {{ $version->author_name }}</div>
                            <div><span class="text-base-content/60">{{ __('Status') }}:</span>
                                @if($version->id === $document->current_version_id)
                                    <span class="badge badge-success badge-sm">{{ __('Aktif') }}</span>
                                @elseif($version->status === 'inactive')
                                    <span class="badge badge-neutral badge-sm">{{ __('Tidak Aktif') }}</span>
                                @elseif($version->status === 'pending')
                                    <span class="badge badge-warning badge-sm">{{ __('Tertunda') }}</span>
                                @elseif($version->status === 'discarded' || $version->discarded_at)
                                    <span class="badge badge-neutral badge-sm">{{ __('Dibuang') }}</span>
                                @elseif($version->status === 'rejected')
                                    <span class="badge badge-error badge-sm">{{ __('Ditolak') }}</span>
                                @else
                                    <span class="badge badge-ghost badge-sm">{{ $version->status }}</span>
                                @endif
                            </div>
                        </div>
                        @php
                            $pendingSigRequest = $document->signatureRequests
                                ->where('target_user_id', auth()->id())
                                ->where('status', 'pending')
                                ->first();
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
                                            <form method="POST" action="{{ route('signatures.requests.approve', $pendingSigRequest) }}" onsubmit="document.getElementById('loading-modal')?.showModal()" class="inline">
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
                                                    <p class="text-xs text-base-content/60 mt-1">
                                                        {{ __('Diminta oleh') }}: <span class="font-medium text-base-content/80">{{ $pendingSigRequest->requester->name }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Form --}}
                                        <form method="POST" action="{{ route('signatures.requests.reject', $pendingSigRequest) }}">
                                            @csrf
                                            <div class="px-6 pb-5 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <label for="reject-sig-preview-ver-reason-{{ $pendingSigRequest->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                        {{ __('Alasan Penolakan') }}
                                                    </label>
                                                    <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                </div>
                                                <div class="relative">
                                                    <textarea 
                                                        id="reject-sig-preview-ver-reason-{{ $pendingSigRequest->id }}"
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
                            @endif

                            <a href="{{ route('documents.show', $document) }}" class="btn btn-ghost btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                Back
                            </a>
                        </div>
                    </div>

                    @if($version->file_path)
                        @include('documents._file-preview', ['document' => $document, 'version' => $version])
                    @else
                        @include('documents._paper', [
                            'content'     => $version->content ?? '',
                            'document'    => $document,
                            'paperSize'   => $document->paper_size ?? 'A4',
                            'paperMargin' => $document->paper_margin,
                        ])
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>