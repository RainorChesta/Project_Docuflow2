@php 
    $isSeen = $doc->isDirectorRead(); 
@endphp
<tr id="doc-row-{{ $doc->id }}" class="hover:bg-base-200/40 transition-colors">
    <td>
        <input type="checkbox" 
               value="{{ $doc->id }}" 
               x-model="selectedIds" 
               class="checkbox checkbox-primary checkbox-xs rounded"
               aria-label="{{ __('Pilih dokumen') }}">
    </td>
    <td>
        <div class="flex items-center gap-2">
            <a href="{{ route('documents.show', $doc) }}" 
               class="font-bold text-sm text-base-content hover:text-primary transition-colors max-w-sm truncate block">
                {{ $doc->title }}
            </a>
            <span class="badge badge-ghost badge-xs text-[10px] font-semibold border-base-300 text-base-content/70 shrink-0">v{{ $doc->currentVersion?->version_number ?? '1.0' }}</span>
        </div>
    </td>
    <td class="font-mono text-base-content/80">
        <div class="flex items-center gap-1.5 flex-wrap">
            <span>{{ $doc->document_number }}</span>
            <x-document-format-badge :format="$doc->format_choice" size="xs" />
        </div>
    </td>
    <td class="whitespace-nowrap">
        <span class="font-medium text-base-content/90 block">{{ $doc->activated_at->translatedFormat('d M Y') }}</span>
        <span class="text-[10px] text-base-content/50 block">{{ $doc->activated_at->format('H:i') }}</span>
    </td>
    <td>
        <span class="font-medium block">{{ $doc->branch?->name ?? '—' }}</span>
        <span class="text-base-content/40 text-[11px] block">{{ $doc->company?->name ?? $doc->branch?->company?->name }}</span>
    </td>
    <td>
        <span class="badge badge-ghost badge-sm text-[11px]" title="{{ $doc->unitKerja?->nama_unit_kerja }}">
            {{ $doc->unitKerja?->kode_unit_kerja ?? '—' }}
        </span>
    </td>
    <td>
        <div class="flex items-center gap-1.5">
            <x-user-avatar :user="$doc->owner" size="w-5 h-5" text-size="text-[9px]" />
            <span class="truncate max-w-[120px]">{{ $doc->owner->name }}</span>
        </div>
    </td>
    <td>
        <div id="seen-badge-row-{{ $doc->id }}">
            @if($isSeen)
                <span class="badge badge-ghost border border-secondary/30 text-secondary badge-sm gap-1 font-semibold py-1 px-2.5 bg-secondary/10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    {{ __('Sudah Ditinjau') }}
                </span>
            @else
                <span class="badge badge-ghost border border-primary/20 text-primary badge-sm gap-1.5 font-normal py-1 px-2.5 bg-primary/5">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                    {{ __('Belum Ditinjau') }}
                </span>
            @endif
        </div>
    </td>
    <td class="text-right">
        <div class="flex items-center justify-end gap-1.5">
            <a href="{{ route('documents.preview', $doc) }}" 
               class="btn btn-ghost btn-xs btn-circle text-base-content/60 hover:text-primary hover:bg-primary/10" 
               title="{{ __('Pratinjau') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </a>

            {{-- Toggle Seen Checkmark Action Button --}}
            <button type="button" 
                    id="btn-toggle-row-{{ $doc->id }}"
                    @click="toggleSeen({{ $doc->id }}, '{{ $isSeen ? 'unseen' : 'seen' }}')"
                    class="btn btn-xs rounded-lg gap-1 font-semibold transition-all {{ $isSeen ? 'btn-ghost text-base-content/50 hover:text-error hover:bg-error/10' : 'btn-secondary text-white shadow-xs' }}">
                @if($isSeen)
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    <span>{{ __('Batal') }}</span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    <span>{{ __('Ditinjau') }}</span>
                @endif
            </button>
        </div>
    </td>
</tr>
