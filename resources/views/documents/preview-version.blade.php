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
                            <div><span class="text-base-content/60">Version:</span> v{{ $version->version_number }}</div>
                            <div><span class="text-base-content/60">Author:</span> {{ $version->author_name }}</div>
                            <div><span class="text-base-content/60">Status:</span>
                                @if($version->id === $document->current_version_id)
                                    <span class="badge badge-success badge-sm">Active</span>
                                @elseif($version->status === 'inactive')
                                    <span class="badge badge-neutral badge-sm">Inactive</span>
                                @elseif($version->status === 'pending')
                                    <span class="badge badge-warning badge-sm">Pending</span>
                                @elseif($version->status === 'discarded' || $version->discarded_at)
                                    <span class="badge badge-neutral badge-sm">Discarded</span>
                                @elseif($version->status === 'rejected')
                                    <span class="badge badge-error badge-sm">Rejected</span>
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
                                {{-- Approve Signature Button --}}
                                <button type="button" onclick="document.getElementById('approve-sig-modal-{{ $pendingSigRequest->id }}').showModal()" class="btn btn-success btn-sm gap-1.5 font-semibold shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    {{ __('Approve TTD') }}
                                </button>

                                {{-- Reject Signature Button --}}
                                <button type="button" onclick="document.getElementById('reject-sig-modal-{{ $pendingSigRequest->id }}').showModal()" class="btn btn-error btn-outline btn-sm gap-1.5 font-semibold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ __('Reject TTD') }}
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
                                                <h3 class="font-bold text-lg text-base-content">{{ __('Konfirmasi Persetujuan Tanda Tangan') }}</h3>
                                                <p class="text-xs text-base-content/60">{{ __('Penyematan tanda tangan digital') }}</p>
                                            </div>
                                        </div>
                                        <p class="text-sm text-base-content/80 py-2">
                                            {!! __('Apakah Anda yakin ingin menyetujui penggunaan tanda tangan Anda oleh <strong>:name</strong> untuk dokumen <strong>:doc</strong>?', [
                                                'name' => e($pendingSigRequest->requester->name),
                                                'doc' => e($document->title)
                                            ]) !!}
                                        </p>
                                        <p class="text-xs text-base-content/60 bg-base-200/50 p-2.5 rounded-lg mb-4">
                                            ℹ️ {{ __('Tanda tangan Anda akan dibubuhkan secara otomatis ke dalam dokumen ini.') }}
                                        </p>
                                        <div class="modal-action">
                                            <button type="button" onclick="document.getElementById('approve-sig-modal-{{ $pendingSigRequest->id }}').close()" class="btn btn-ghost btn-sm">{{ __('Batal') }}</button>
                                            <form method="POST" action="{{ route('signatures.requests.approve', $pendingSigRequest) }}" onsubmit="document.getElementById('loading-modal')?.showModal()" class="inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm gap-1.5 font-semibold text-white">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    {{ __('Ya, Setujui TTD') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>close</button>
                                    </form>
                                </dialog>

                                {{-- Custom Reject Modal --}}
                                <dialog id="reject-sig-modal-{{ $pendingSigRequest->id }}" class="modal text-left whitespace-normal">
                                    <div class="modal-box max-w-md">
                                        <div class="flex items-center gap-3 text-error mb-3">
                                            <div class="w-10 h-10 rounded-full bg-error/10 flex items-center justify-center shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-lg text-base-content">{{ __('Tolak Penggunaan Tanda Tangan') }}</h3>
                                                <p class="text-xs text-base-content/60">{{ __('Permintaan dari :name', ['name' => $pendingSigRequest->requester->name]) }}</p>
                                            </div>
                                        </div>
                                        <p class="text-sm text-base-content/80 mb-3">
                                            {!! __('Anda akan menolak permohonan penggunaan tanda tangan Anda untuk dokumen <strong>:doc</strong>.', ['doc' => e($document->title)]) !!}
                                        </p>
                                        <form method="POST" action="{{ route('signatures.requests.reject', $pendingSigRequest) }}">
                                            @csrf
                                            <div class="form-control mb-4">
                                                <label class="label pb-1">
                                                    <span class="label-text text-xs font-semibold">{{ __('Alasan Penolakan (Opsional)') }}</span>
                                                </label>
                                                <textarea name="reason" class="textarea textarea-bordered w-full text-sm" rows="3" placeholder="{{ __('Tuliskan alasan penolakan izin tanda tangan...') }}"></textarea>
                                            </div>
                                            <div class="modal-action">
                                                <button type="button" onclick="document.getElementById('reject-sig-modal-{{ $pendingSigRequest->id }}').close()" class="btn btn-ghost btn-sm">{{ __('Batal') }}</button>
                                                <button type="submit" class="btn btn-error btn-sm font-semibold">{{ __('Tolak Permintaan') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>close</button>
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