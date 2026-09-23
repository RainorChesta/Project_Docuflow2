@php
    $isSeen = $doc->isDirectorRead();
@endphp
<div id="doc-card-{{ $doc->id }}" 
     class="bg-base-100 border {{ $isSeen ? 'border-base-300 hover:border-secondary/50' : 'border-base-300 hover:border-primary/50' }} rounded-2xl p-4.5 transition-all duration-200 shadow-xs hover:shadow-md flex flex-col justify-between h-full relative group">
    
    {{-- Card Top: Header, Title, Number, Metadata --}}
    <div class="space-y-3">
        {{-- Row 1: Checkbox & Branch (Left) + Format & Version (Right) --}}
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <input type="checkbox" 
                       value="{{ $doc->id }}" 
                       x-model="selectedIds" 
                       class="checkbox checkbox-primary checkbox-sm rounded-md shrink-0"
                       aria-label="{{ __('Pilih dokumen') }}">
                
                <span class="badge badge-ghost badge-sm text-[11px] font-semibold text-base-content/70 border-base-300 shrink-0" title="{{ $doc->branch?->name }} ({{ $doc->company?->code ?? $doc->branch?->company?->code }})">
                    {{ $doc->branch?->effective_code ?? $doc->branch?->code ?? '—' }}
                </span>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <x-document-format-badge :format="$doc->format_choice" size="xs" />
                <span class="badge badge-ghost badge-xs text-[10px] font-semibold py-0.5 px-1.5 border-base-300 text-base-content/70">
                    v{{ $doc->currentVersion?->version_number ?? '1.0' }}
                </span>
            </div>
        </div>

        {{-- Row 2: Document Title & Number --}}
        <div class="min-h-[58px]">
            <a href="{{ route('documents.show', $doc) }}" 
               class="font-bold text-sm text-base-content hover:text-primary transition-colors line-clamp-2 leading-snug" 
               title="{{ $doc->title }}">
                {{ $doc->title }}
            </a>

            <div class="flex items-center justify-between gap-2 mt-1.5 pt-1">
                <p class="font-mono text-xs text-base-content/70 font-medium truncate" title="{{ $doc->document_number }}">
                    {{ $doc->document_number }}
                </p>
                @if($doc->documentType)
                    <span class="badge badge-outline badge-xs text-[10px] text-base-content/60 font-normal shrink-0">
                        {{ $doc->documentType->code }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Row 3: Metadata Details --}}
        <div class="space-y-1.5 text-xs text-base-content/70 pt-2.5 border-t border-base-200">
            <div class="flex items-center justify-between gap-2">
                <span class="text-base-content/50 text-[11px]">{{ __('Unit Kerja:') }}</span>
                <span class="font-medium truncate max-w-[150px] text-right" title="{{ $doc->unitKerja?->nama_unit_kerja }}">
                    {{ $doc->unitKerja?->kode_unit_kerja ?? '—' }}
                </span>
            </div>
            <div class="flex items-center justify-between gap-2">
                <span class="text-base-content/50 text-[11px]">{{ __('Pembuat:') }}</span>
                <div class="flex items-center gap-1.5 truncate max-w-[150px]">
                    <x-user-avatar :user="$doc->owner" size="w-4 h-4" text-size="text-[9px]" />
                    <span class="truncate font-medium">{{ $doc->owner->name }}</span>
                </div>
            </div>
            <div class="flex items-center justify-between gap-2">
                <span class="text-base-content/50 text-[11px]">{{ __('Tgl. Aktif:') }}</span>
                <span class="font-medium text-right text-[11px] text-base-content/80" title="{{ $doc->activated_at->format('d/m/Y H:i') }} WIB">
                    {{ $doc->activated_at->translatedFormat('d M Y') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Card Bottom: Status Pill & Action Buttons --}}
    <div class="pt-3 border-t border-base-200 space-y-2.5 mt-2">
        {{-- Status Banner (Fixed height 34px for alignment) --}}
        <div id="seen-badge-{{ $doc->id }}" class="h-[34px] flex items-center">
            @if($isSeen)
                <div class="w-full flex items-center justify-between gap-1.5 bg-secondary/10 border border-secondary/25 text-secondary px-2.5 py-1.5 rounded-xl text-xs font-semibold">
                    <span class="flex items-center gap-1.5 truncate">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="truncate">{{ __('Sudah Ditinjau') }}</span>
                    </span>
                    <span class="text-[10px] text-secondary/70 shrink-0 font-normal" title="{{ __('Ditinjau pada:') }} {{ $doc->director_read_at?->format('d/m/Y H:i') }} WIB">
                        {{ $doc->director_read_at?->diffForHumans() }}
                    </span>
                </div>
            @else
                <div class="w-full flex items-center justify-between gap-1.5 bg-primary/5 border border-primary/20 text-primary px-2.5 py-1.5 rounded-xl text-xs font-semibold">
                    <span class="flex items-center gap-1.5 truncate">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse shrink-0"></span>
                        <span class="truncate">{{ __('Belum Ditinjau') }}</span>
                    </span>
                    <span class="text-[10px] text-primary/70 shrink-0 font-normal">
                        {{ __('Perlu Ditinjau') }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Action Buttons Row --}}
        <div class="flex items-center justify-between gap-2 pt-0.5">
            <a href="{{ route('documents.preview', $doc) }}" 
               class="btn btn-ghost btn-xs rounded-lg gap-1 text-base-content/70 hover:text-primary hover:bg-primary/10" 
               title="{{ __('Pratinjau Dokumen') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>{{ __('Preview') }}</span>
            </a>

            {{-- Toggle Seen Checkmark Action Button --}}
            <button type="button" 
                    id="btn-toggle-{{ $doc->id }}"
                    @click="toggleSeen({{ $doc->id }}, '{{ $isSeen ? 'unseen' : 'seen' }}')"
                    class="btn btn-xs rounded-lg gap-1 font-semibold transition-all {{ $isSeen ? 'btn-ghost text-base-content/50 hover:text-error hover:bg-error/10' : 'btn-secondary text-white shadow-xs' }}">
                @if($isSeen)
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    <span>{{ __('Batal Ditinjau') }}</span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    <span>{{ __('Tandai Ditinjau') }}</span>
                @endif
            </button>
        </div>
    </div>
</div>
