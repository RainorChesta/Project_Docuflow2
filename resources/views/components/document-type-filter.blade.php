@props([
    'documentTypes' => collect(),
    'name' => 'document_type_id',
    'selected' => request('document_type_id'),
    'placeholder' => __('Semua Tipe'),
    'submitOnChange' => true,
    'class' => '',
])

@php
    $selected = (string) ($selected ?? '');
    $typesData = collect($documentTypes)->map(function ($dt) {
        return [
            'id' => (string) $dt->id,
            'code' => (string) $dt->code,
            'name' => (string) $dt->name,
            'label' => $dt->code . ' - ' . $dt->name,
        ];
    })->values()->all();

    $activeItem = collect($typesData)->firstWhere('id', $selected);
    $initialLabel = $activeItem ? $activeItem['label'] : $placeholder;
@endphp

<div x-data="{
        open: false,
        search: '',
        selectedId: @js($selected),
        options: @js($typesData),
        placeholder: @js($placeholder),
        get selectedItem() {
            return this.options.find(opt => opt.id === this.selectedId) || null;
        },
        get selectedLabel() {
            const item = this.selectedItem;
            return item ? item.label : this.placeholder;
        },
        get filteredOptions() {
            if (!this.search.trim()) return this.options;
            const q = this.search.toLowerCase().trim();
            return this.options.filter(opt =>
                opt.name.toLowerCase().includes(q) ||
                opt.code.toLowerCase().includes(q) ||
                opt.label.toLowerCase().includes(q)
            );
        },
        get matchesAllOption() {
            if (!this.search.trim()) return true;
            const q = this.search.toLowerCase().trim();
            const allLabel = this.placeholder.toLowerCase();
            return allLabel.includes(q) || 'semua'.includes(q) || 'all'.includes(q);
        },
        selectOption(id) {
            const isChanged = this.selectedId !== id;
            this.selectedId = id;
            this.open = false;
            this.search = '';
            if (isChanged && {{ $submitOnChange ? 'true' : 'false' }}) {
                $nextTick(() => {
                    const form = this.$refs.hiddenInput?.closest('form');
                    if (form) form.submit();
                });
            }
        },
        handleSearchKeydown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.filteredOptions.length > 0) {
                    this.selectOption(this.filteredOptions[0].id);
                } else if (this.matchesAllOption) {
                    this.selectOption('');
                }
            }
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative {{ $class }}"
>
    <!-- Hidden input for form submission -->
    <input type="hidden" name="{{ $name }}" :value="selectedId" x-ref="hiddenInput">

    <!-- Trigger Button -->
    <button type="button"
            @click="open = !open; if(open) { search = ''; $nextTick(() => $refs.searchInput?.focus()); }"
            class="select select-bordered select-sm w-full sm:w-auto min-w-[160px] sm:min-w-[200px] max-w-full flex items-center justify-between gap-2 text-left font-normal bg-base-100 hover:border-primary/50 transition-all cursor-pointer shadow-sm"
            :class="{'!border-primary !ring-2 !ring-primary/20': open}"
            :title="selectedLabel"
            aria-haspopup="listbox"
            :aria-expanded="open">
        <span class="truncate text-xs sm:text-sm flex-1"
              x-text="selectedLabel"
              :class="{'text-base-content/60': !selectedId, 'font-medium text-base-content': selectedId}">
            {{ $initialLabel }}
        </span>
        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-4 h-4 shrink-0 text-base-content/40 transition-transform duration-200"
             :class="{'rotate-180 text-primary': open}"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
         class="absolute left-0 sm:left-auto right-0 sm:right-auto z-50 mt-1.5 w-full sm:w-80 bg-base-100 border border-base-300 rounded-xl shadow-2xl overflow-hidden"
         style="display: none;">
        
        <!-- Search Input Header -->
        <div class="p-2 border-b border-base-200 bg-base-200/40 backdrop-blur-sm sticky top-0 z-10">
            <div class="relative flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-4 h-4 text-base-content/40 absolute left-2.5 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input x-ref="searchInput"
                       type="text"
                       x-model="search"
                       @keydown="handleSearchKeydown($event)"
                       placeholder="{{ __('Cari tipe dokumen...') }}"
                       class="input input-sm input-bordered w-full pl-8 pr-7 text-xs bg-base-100 focus:bg-base-100 focus:border-primary focus:outline-none rounded-lg"
                       autocomplete="off">
                <button type="button"
                        x-show="search.length > 0"
                        @click="search = ''; $refs.searchInput?.focus()"
                        class="absolute right-2 text-base-content/40 hover:text-base-content p-0.5 rounded-full hover:bg-base-300 transition-colors"
                        aria-label="{{ __('Hapus teks pencarian') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Options List -->
        <ul class="max-h-64 overflow-y-auto p-1.5 space-y-0.5" role="listbox">
            <!-- All / Reset Option -->
            <li x-show="matchesAllOption">
                <button type="button"
                        @click="selectOption('')"
                        class="w-full text-left px-3 py-2 rounded-lg flex items-center justify-between transition-colors text-xs hover:bg-base-200/80 cursor-pointer"
                        :class="{'bg-primary/10 text-primary font-semibold': !selectedId, 'text-base-content/80': selectedId}">
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        <span x-text="placeholder"></span>
                    </span>
                    <svg x-show="!selectedId" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </li>

            <!-- Filtered Document Types -->
            <template x-for="option in filteredOptions" :key="option.id">
                <li>
                    <button type="button"
                            @click="selectOption(option.id)"
                            class="w-full text-left px-3 py-2 rounded-lg flex items-center justify-between gap-2 transition-colors text-xs hover:bg-base-200/80 cursor-pointer"
                            :class="{'bg-primary/10 text-primary font-semibold': selectedId == option.id, 'text-base-content': selectedId != option.id}">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <span class="badge badge-sm badge-neutral font-mono text-[10px] font-bold px-1.5 py-0.5 shrink-0"
                                  :class="{'badge-primary': selectedId == option.id}"
                                  x-text="option.code"></span>
                            <span class="truncate font-medium" x-text="option.name"></span>
                        </div>
                        <svg x-show="selectedId == option.id" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </li>
            </template>

            <!-- No Results Message -->
            <li x-show="filteredOptions.length === 0 && !matchesAllOption" class="py-6 px-4 text-center space-y-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 mx-auto text-base-content/25" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs font-semibold text-base-content/70">{{ __('Tipe dokumen tidak ditemukan.') }}</p>
                <p class="text-[11px] text-base-content/40">{{ __('Coba cari dengan kata kunci kode atau nama yang berbeda.') }}</p>
            </li>
        </ul>
    </div>
</div>
