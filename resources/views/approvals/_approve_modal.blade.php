@props([
    'document',
    'version',
    'modalId',
    'hasPendingSignature' => false,
    'userSignatures' => null,
])

@php
    $user = auth()->user();
    $userSignatures = $userSignatures ?? ($user ? $user->signatures()->where('status', 'active')->get() : collect());
    $isFilePdf = ($version->file_path && str_ends_with(strtolower($version->file_path), '.pdf')) || ($version->file_mime && str_contains(strtolower($version->file_mime), 'pdf'));
    $hasExistingSignatures = $userSignatures->isNotEmpty();
    $defaultTab = $hasExistingSignatures ? 'saved' : 'draw';
@endphp

<dialog id="{{ $modalId }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-xl text-base-content"
         x-data="approvalModalComponent('{{ $modalId }}', {{ $hasPendingSignature ? 'true' : 'false' }}, {{ $hasExistingSignatures ? 'true' : 'false' }}, '{{ $defaultTab }}')">
        
        <form method="POST" action="{{ route('approvals.approve', [$document, $version]) }}" enctype="multipart/form-data" @submit="handleSubmit($event)">
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

                {{-- Case A: Triggered via signature request --}}
                @if($hasPendingSignature)
                    <div class="mt-4 p-3 rounded-xl bg-success/10 border border-success/20 text-xs text-success-content flex items-center gap-2.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span class="font-medium leading-relaxed">
                            {{ __('Dokumen ini menyertakan permintaan tanda tangan. Tanda tangan digital Anda akan otomatis disematkan dan dibubuhkan saat Anda menyetujui.') }}
                        </span>
                    </div>

                {{-- Case B: Review / Approval Only (with Optional Direct Signature Stamping) --}}
                @else
                    <div class="mt-4 rounded-xl bg-base-200/50 border border-base-300/70 p-3.5 space-y-3">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" name="include_signature" value="1" x-model="withSig" class="checkbox checkbox-primary checkbox-sm rounded-md">
                            <span class="text-xs font-bold text-base-content">
                                {{ __('Sisipkan tanda tangan digital saya pada dokumen ini (Opsional)') }}
                            </span>
                        </label>

                        {{-- Direct Signature Options --}}
                        <div x-show="withSig" x-cloak class="pt-2 border-t border-base-300/60 space-y-3">
                            {{-- Tab Selector --}}
                            <div class="flex items-center gap-1.5 p-1 bg-base-200 rounded-lg text-xs font-semibold">
                                @if($hasExistingSignatures)
                                    <button type="button" 
                                            @click="setTab('saved')" 
                                            :class="tab === 'saved' ? 'bg-base-100 text-primary shadow-xs' : 'text-base-content/60 hover:text-base-content'"
                                            class="flex-1 py-1.5 px-2 rounded-md transition-all text-center">
                                        {{ __('TTD Tersimpan') }}
                                    </button>
                                @endif
                                <button type="button" 
                                        @click="setTab('draw')" 
                                        :class="tab === 'draw' ? 'bg-base-100 text-primary shadow-xs' : 'text-base-content/60 hover:text-base-content'"
                                        class="flex-1 py-1.5 px-2 rounded-md transition-all text-center">
                                    {{ __('Gambar Langsung') }}
                                </button>
                                <button type="button" 
                                        @click="setTab('upload')" 
                                        :class="tab === 'upload' ? 'bg-base-100 text-primary shadow-xs' : 'text-base-content/60 hover:text-base-content'"
                                        class="flex-1 py-1.5 px-2 rounded-md transition-all text-center">
                                    {{ __('Unggah Gambar') }}
                                </button>
                            </div>

                            {{-- Tab 1: Saved Signature Selection --}}
                            @if($hasExistingSignatures)
                                <div x-show="tab === 'saved'" class="space-y-2 pt-1">
                                    <label class="text-[11px] font-semibold text-base-content/70 block">
                                        {{ __('Pilih Tanda Tangan / Stempel:') }}
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach($userSignatures as $idx => $sig)
                                            <label class="flex items-center gap-2.5 p-2 rounded-xl border border-base-300 bg-base-100 hover:border-primary/50 cursor-pointer transition-colors"
                                                   :class="selectedSigId == '{{ $sig->id }}' ? 'border-primary ring-1 ring-primary/20 bg-primary/5' : ''">
                                                <input type="radio" name="signature_id" value="{{ $sig->id }}" x-model="selectedSigId" class="radio radio-primary radio-xs">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <span class="text-xs font-semibold text-base-content truncate">
                                                            {{ $sig->type === 'company_stamp' ? ($sig->company->name ?? 'Stempel Perusahaan') : __('TTD Utama') }}
                                                        </span>
                                                        <span class="badge badge-xs {{ $sig->type === 'company_stamp' ? 'badge-secondary' : 'badge-primary' }}">
                                                            {{ $sig->type === 'company_stamp' ? 'Stempel' : 'TTD' }}
                                                        </span>
                                                    </div>
                                                </div>
                                                @if($sig->file_path)
                                                    <img src="{{ asset('storage/' . $sig->file_path) }}" alt="Preview" class="h-7 w-12 object-contain bg-white rounded border border-base-300 shrink-0 p-0.5" />
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Tab 2: Canvas Direct Drawing --}}
                            <div x-show="tab === 'draw'" class="space-y-2 pt-1">
                                <div class="flex items-center justify-between">
                                    <label class="text-[11px] font-semibold text-base-content/70">
                                        {{ __('Goreskan Tanda Tangan Anda:') }}
                                    </label>
                                    <button type="button" @click="clearCanvas()" class="btn btn-ghost btn-xs text-base-content/60 hover:text-error gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        {{ __('Hapus') }}
                                    </button>
                                </div>
                                <div class="relative border-2 border-dashed border-base-300 rounded-xl bg-white p-1 overflow-hidden">
                                    <canvas :id="'canvas-' + modalId" class="w-full h-36 touch-none cursor-crosshair rounded-lg bg-white"></canvas>
                                    <div x-show="!hasDrawn" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center text-base-content/30 text-xs">
                                        <span>✍️ {{ __('Gores tanda tangan di area ini') }}</span>
                                    </div>
                                </div>
                                <input type="hidden" name="signature_data" :id="'sigdata-' + modalId" x-model="signatureData">
                            </div>

                            {{-- Tab 3: Upload Image File --}}
                            <div x-show="tab === 'upload'" class="space-y-2 pt-1">
                                <label class="text-[11px] font-semibold text-base-content/70 block">
                                    {{ __('Unggah Gambar Tanda Tangan (PNG / JPG):') }}
                                </label>
                                <input type="file" name="signature_image" accept="image/png, image/jpeg, image/jpg" class="file-input file-input-bordered file-input-sm w-full rounded-xl text-xs">
                                <p class="text-[10px] text-base-content/50">
                                    {{ __('Disarankan format PNG dengan latar transparan (maks. 2MB).') }}
                                </p>
                            </div>

                            {{-- PDF Position & Page (If file is PDF) --}}
                            @if($isFilePdf)
                                <div class="pt-2 border-t border-base-300/50 grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <label class="text-[10px] font-semibold text-base-content/60 block mb-1">{{ __('Posisi TTD pada PDF:') }}</label>
                                        <select name="signature_preset_position" class="select select-bordered select-xs w-full rounded-lg">
                                            <option value="bottom-right" selected>{{ __('Bawah Kanan') }}</option>
                                            <option value="bottom-left">{{ __('Bawah Kiri') }}</option>
                                            <option value="top-right">{{ __('Atas Kanan') }}</option>
                                            <option value="top-left">{{ __('Atas Kiri') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-semibold text-base-content/60 block mb-1">{{ __('Halaman Ke:') }}</label>
                                        <input type="number" name="signature_page_number" value="1" min="1" class="input input-bordered input-xs w-full rounded-lg">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Approval Notes --}}
                <div class="mt-3.5">
                    <label class="text-xs font-medium text-base-content/70 block mb-1">
                        {{ __('Catatan Persetujuan (Opsional):') }}
                    </label>
                    <textarea name="notes" rows="2" class="textarea textarea-bordered textarea-sm w-full rounded-xl text-xs bg-base-100" placeholder="{{ __('Tulis catatan atau instruksi jika ada...') }}"></textarea>
                </div>
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
                    <span x-text="hasPendingSignature ? '{{ __('Setujui & Tandatangani') }}' : (withSig ? '{{ __('Setujui & Sisipkan TTD') }}' : '{{ __('Setujui Dokumen') }}')">
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

<script>
function approvalModalComponent(modalId, hasPendingSignature, hasExistingSignatures, defaultTab) {
    return {
        modalId: modalId,
        hasPendingSignature: hasPendingSignature,
        withSig: hasPendingSignature,
        tab: defaultTab,
        selectedSigId: '{{ $userSignatures->first()?->id ?? '' }}',
        signatureData: '',
        hasDrawn: false,
        canvas: null,
        ctx: null,
        isDrawing: false,

        init() {
            this.$watch('tab', (value) => {
                if (value === 'draw') {
                    this.$nextTick(() => this.initCanvas());
                }
            });
            this.$watch('withSig', (value) => {
                if (value && this.tab === 'draw') {
                    this.$nextTick(() => this.initCanvas());
                }
            });
        },

        setTab(newTab) {
            this.tab = newTab;
            if (newTab === 'draw') {
                this.$nextTick(() => this.initCanvas());
            }
        },

        initCanvas() {
            const canvasEl = document.getElementById('canvas-' + this.modalId);
            if (!canvasEl) return;
            this.canvas = canvasEl;
            this.ctx = canvasEl.getContext('2d');

            // Responsive sizing with device pixel ratio
            const rect = canvasEl.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            canvasEl.width = (rect.width || 400) * dpr;
            canvasEl.height = (rect.height || 140) * dpr;
            this.ctx.scale(dpr, dpr);
            this.ctx.strokeStyle = '#1e293b';
            this.ctx.lineWidth = 2.5;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';

            const getPos = (e) => {
                const r = canvasEl.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return {
                    x: clientX - r.left,
                    y: clientY - r.top
                };
            };

            const startDraw = (e) => {
                this.isDrawing = true;
                this.hasDrawn = true;
                const pos = getPos(e);
                this.ctx.beginPath();
                this.ctx.moveTo(pos.x, pos.y);
                e.preventDefault();
            };

            const draw = (e) => {
                if (!this.isDrawing) return;
                const pos = getPos(e);
                this.ctx.lineTo(pos.x, pos.y);
                this.ctx.stroke();
                e.preventDefault();
            };

            const stopDraw = () => {
                if (!this.isDrawing) return;
                this.isDrawing = false;
                this.signatureData = this.canvas.toDataURL('image/png');
            };

            canvasEl.onmousedown = startDraw;
            canvasEl.onmousemove = draw;
            window.addEventListener('mouseup', stopDraw);

            canvasEl.ontouchstart = startDraw;
            canvasEl.ontouchmove = draw;
            canvasEl.ontouchend = stopDraw;
        },

        clearCanvas() {
            if (!this.canvas || !this.ctx) return;
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.hasDrawn = false;
            this.signatureData = '';
        },

        handleSubmit(e) {
            if (this.withSig && this.tab === 'draw' && this.canvas && this.hasDrawn) {
                this.signatureData = this.canvas.toDataURL('image/png');
            }
        }
    };
}
</script>
