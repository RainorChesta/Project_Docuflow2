@props([
    'name',
    'id' => null,
    'options' => [],
    'selected' => '',
    'placeholder' => __('Semua'),
    'searchPlaceholder' => __('Cari...'),
    'emptyLabel' => __('Tidak ada hasil ditemukan'),
    'allowEmpty' => true,
    'emptyValue' => '',
    'class' => '',
    'btnClass' => '',
])

@php
    $inputName = $name;
    $inputId = $id ?? $name;
    $selectedStr = (string) ($selected ?? '');

    // Normalize options to a list of ['value' => '...', 'label' => '...']
    $formattedOptions = collect($options)->map(function ($item, $key) {
        if (is_array($item)) {
            return [
                'value' => (string) ($item['value'] ?? $key),
                'label' => (string) ($item['label'] ?? ($item['name'] ?? $key)),
            ];
        }
        if (is_object($item)) {
            return [
                'value' => (string) ($item->id ?? $item->value ?? $key),
                'label' => (string) ($item->name ?? $item->label ?? $key),
            ];
        }
        return [
            'value' => (string) $key,
            'label' => (string) $item,
        ];
    })->values()->all();

    $currentMatch = collect($formattedOptions)->firstWhere('value', $selectedStr);
    $initialLabel = $currentMatch ? $currentMatch['label'] : ($allowEmpty ? $placeholder : ($formattedOptions[0]['label'] ?? $placeholder));
@endphp

<div x-data="{
        open: false,
        search: '',
        selected: @js($selectedStr),
        options: @js($formattedOptions),
        placeholder: @js($placeholder),
        allowEmpty: @js($allowEmpty),
        emptyValue: @js($emptyValue),
        get selectedItem() {
            return this.options.find(opt => String(opt.value) === String(this.selected)) || null;
        },
        get selectedLabel() {
            const item = this.selectedItem;
            if (item) return item.label;
            return this.placeholder;
        },
        get filteredOptions() {
            if (!this.search.trim()) return this.options;
            const q = this.search.toLowerCase().trim();
            return this.options.filter(opt =>
                String(opt.label).toLowerCase().includes(q) ||
                String(opt.value).toLowerCase().includes(q)
            );
        },
        get matchesEmptyOption() {
            if (!this.allowEmpty) return false;
            if (!this.search.trim()) return true;
            const q = this.search.toLowerCase().trim();
            const p = this.placeholder.toLowerCase();
            return p.includes(q) || 'semua'.includes(q) || 'all'.includes(q);
        },
        selectOption(val) {
            this.selected = val;
            this.open = false;
            this.search = '';
            this.$nextTick(() => {
                const el = this.$refs.hiddenInput;
                if (el) {
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        },
        handleSearchKeydown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.matchesEmptyOption && !this.filteredOptions.length) {
                    this.selectOption(this.emptyValue);
                } else if (this.filteredOptions.length > 0) {
                    this.selectOption(this.filteredOptions[0].value);
                }
            }
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative w-full {{ $class }}"
>
    {{-- Hidden input for standard GET/POST form submission --}}
    <input type="hidden" 
           name="{{ $inputName }}" 
           id="{{ $inputId }}" 
           :value="selected" 
           x-ref="hiddenInput">

    {{-- Trigger Button Styled as DaisyUI Select --}}
    <button type="button"
            @click="open = !open; if(open) { search = ''; $nextTick(() => $refs.searchInput?.focus()); }"
            class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-2xs flex items-center justify-between font-normal cursor-pointer hover:border-primary/50 transition-all text-left px-3 {{ $btnClass }}"
            :class="{'!border-primary !ring-2 !ring-primary/20': open}"
            :title="selectedLabel"
            aria-haspopup="listbox"
            :aria-expanded="open">
        <span class="truncate flex-1"
              x-text="selectedLabel"
              :class="{'text-base-content/60': allowEmpty && (!selected || selected === emptyValue), 'font-medium text-base-content': selected && selected !== emptyValue}">
            {{ $initialLabel }}
        </span>
        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-3.5 h-3.5 shrink-0 text-base-content/40 transition-transform duration-200 ml-1.5"
             :class="{'rotate-180 text-primary': open}"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Searchable Dropdown Menu --}}
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         style="display: none;"
         class="absolute left-0 right-0 z-50 mt-1 min-w-[200px] bg-base-100 border border-base-300 rounded-xl shadow-xl overflow-hidden py-1">
        
        {{-- Search Input Box --}}
        <div class="p-1.5 border-b border-base-200">
            <div class="relative">
                <input type="text"
                       x-ref="searchInput"
                       x-model="search"
                       @keydown="handleSearchKeydown($event)"
                       placeholder="{{ $searchPlaceholder }}"
                       class="input input-xs input-bordered w-full pl-7 pr-6 text-xs bg-base-200/50 focus:bg-base-100 focus:border-primary transition-all">
                <svg class="w-3.5 h-3.5 absolute left-2 top-1.5 text-base-content/40 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <button type="button" 
                        x-show="search" 
                        @click="search = ''; $refs.searchInput?.focus()" 
                        class="absolute right-1.5 top-1 text-base-content/40 hover:text-base-content text-xs font-bold leading-none p-0.5">
                    ×
                </button>
            </div>
        </div>

        {{-- Options List --}}
        <ul class="max-h-52 overflow-y-auto p-1 space-y-0.5 text-xs">
            {{-- Default / All Option --}}
            <template x-if="allowEmpty && matchesEmptyOption">
                <li>
                    <button type="button"
                            @click="selectOption(emptyValue)"
                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-base-200/80 transition-colors flex items-center justify-between gap-2 cursor-pointer"
                            :class="{'bg-primary/10 text-primary font-semibold': !selected || selected === emptyValue}">
                        <span class="truncate" x-text="placeholder"></span>
                        <svg x-show="!selected || selected === emptyValue" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </li>
            </template>

            {{-- Filtered Options List --}}
            <template x-for="opt in filteredOptions" :key="opt.value">
                <li>
                    <button type="button"
                            @click="selectOption(opt.value)"
                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-base-200/80 transition-colors flex items-center justify-between gap-2 cursor-pointer"
                            :class="{'bg-primary/10 text-primary font-semibold': String(selected) === String(opt.value)}">
                        <span class="truncate" x-text="opt.label"></span>
                        <svg x-show="String(selected) === String(opt.value)" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </li>
            </template>

            {{-- No Results Found --}}
            <li x-show="filteredOptions.length === 0 && (!allowEmpty || !matchesEmptyOption)" 
                class="py-4 px-2 text-center text-xs text-base-content/50 italic">
                {{ $emptyLabel }}
            </li>
        </ul>
    </div>
</div>
