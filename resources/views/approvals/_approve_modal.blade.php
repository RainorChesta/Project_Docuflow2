@props([
    'document',
    'version',
    'modalId',
    'hasPendingSignature' => false,
])

<dialog id="{{ $modalId }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg text-base-content">
        <form method="POST" action="{{ route('approvals.approve', [$document, $version]) }}" data-prevent-double-submit="true" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn){ btn.disabled = true; btn.classList.add('opacity-75', 'cursor-not-allowed'); }">
            @csrf
            
            {{-- Modal Header --}}
            <div class="p-6 pb-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-base-content leading-snug">
                                {{ $hasPendingSignature ? __('Setujui & Bubuhkan Tanda Tangan') : __('Persetujuan Dokumen') }}
                            </h3>
                            <p class="text-xs text-base-content/60 mt-0.5">
                                {{ __('Tinjau dan setujui versi dokumen ini (v:version)', ['version' => $version->version_number]) }}
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('{{ $modalId }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                        ✕
                    </button>
                </div>

                {{-- Document Info Box --}}
                <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                    <div class="p-2 rounded-lg bg-base-100 text-base-content/70 shrink-0 shadow-xs">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="font-semibold text-sm text-base-content break-words">{{ $document->title }}</span>
                        <div class="flex items-center gap-2 text-xs text-base-content/60 mt-1 flex-wrap">
                            <span>{{ __('Penulis') }}: <strong class="text-base-content/80">{{ $version->author_name }}</strong></span>
                            <span>&bull;</span>
                            <span class="badge badge-xs badge-ghost font-mono">v{{ $version->version_number }}</span>
                            @if($document->document_number)
                                <span>&bull;</span>
                                <span class="font-mono text-[11px]">{{ $document->document_number }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Signature Info Notice (when document includes a signature request) --}}
                @if($hasPendingSignature)
                    <div class="mt-4 p-3 rounded-xl bg-success/10 border border-success/20 text-xs text-success-content flex items-center gap-2.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span class="font-medium leading-relaxed">
                            {{ __('Dokumen ini menyertakan permintaan tanda tangan. Tanda tangan digital Anda akan otomatis disematkan dan dibubuhkan saat Anda menyetujui.') }}
                        </span>
                    </div>
                @endif

            </div>

            {{-- Footer Actions --}}
            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                <button type="button" onclick="document.getElementById('{{ $modalId }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                    {{ __('Batal') }}
                </button>
                <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md transition-all flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>
                        {{ $hasPendingSignature ? __('Setujui & Tandatangani') : __('Setujui Dokumen') }}
                    </span>
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>{{ __('Batal') }}</button>
    </form>
</dialog>
