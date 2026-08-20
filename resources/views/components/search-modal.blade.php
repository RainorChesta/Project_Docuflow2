{{-- Global Search Modal — triggered by the search icon in the topbar.
     Uses Alpine.js for state + fetch for the search API.
     Keyboard shortcut: Ctrl+K / Cmd+K. --}}
<div x-data="{
        open: false,
        query: '',
        documentTypeId: '',
        documentTypes: [],
        results: [],
        pagination: { current_page: 1, last_page: 1, total: 0, has_more: false },
        loading: false,
        debounceTimer: null,
        init() {
            // Listen for open-search event from the topbar icon
            window.addEventListener('open-search', () => this.openModal());
            // Keyboard shortcut: Ctrl+K / Cmd+K
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.openModal();
                }
                if (e.key === 'Escape' && this.open) {
                    this.closeModal();
                }
            });
        },
        openModal() {
            this.open = true;
            this.query = '';
            this.documentTypeId = '';
            this.results = [];
            this.pagination = { current_page: 1, last_page: 1, total: 0, has_more: false };
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },
        closeModal() {
            this.open = false;
            this.query = '';
            this.documentTypeId = '';
            this.results = [];
        },
        search(page = 1) {
            clearTimeout(this.debounceTimer);
            if (this.query.length < 2 && !this.documentTypeId) {
                this.results = [];
                this.pagination = { current_page: 1, last_page: 1, total: 0, has_more: false };
                this.loading = false;
                return;
            }
            this.loading = true;
            this.debounceTimer = setTimeout(() => {
                const currentLang = document.documentElement.lang || '{{ app()->getLocale() }}';
                const url = new URL('{{ route('search') }}', window.location.origin);
                url.searchParams.set('q', this.query);
                url.searchParams.set('lang', currentLang);
                if (this.documentTypeId) {
                    url.searchParams.set('document_type_id', this.documentTypeId);
                }
                url.searchParams.set('page', page);

                fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(r => r.json())
                .then(data => {
                    this.results = data.results || [];
                    this.pagination = data.pagination || { current_page: 1, last_page: 1, total: 0, has_more: false };
                    this.loading = false;
                })
                .catch(() => {
                    this.loading = false;
                });
            }, 250);
        }
     }"
     x-cloak>

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] bg-black/50 backdrop-blur-sm"
         @click="closeModal()">
    </div>

    {{-- Modal --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
         class="fixed inset-x-0 top-[10%] z-[61] mx-auto w-full max-w-lg px-4">

        <div class="bg-base-100 border border-base-300 rounded-xl shadow-2xl overflow-hidden">
            {{-- Search input and Document Type Filter --}}
            <div class="p-3 border-b border-base-300 space-y-2">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input x-ref="searchInput"
                           type="text"
                           x-model="query"
                           @input="search(1)"
                           class="flex-1 bg-transparent border-none outline-none text-base-content placeholder-base-content/40 text-sm"
                           placeholder="{{ __('Cari dokumen berdasarkan judul atau nomor...') }}" />
                    <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono font-medium text-base-content/30 bg-base-200 rounded border border-base-300">ESC</kbd>
                </div>

                {{-- Document Type Quick Filter --}}
                <div class="flex items-center gap-2 pt-1 border-t border-base-200/60">
                    <span class="text-xs text-base-content/50">{{ __('Tipe:') }}</span>
                    <select x-model="documentTypeId"
                            @change="search(1)"
                            class="select select-bordered select-xs text-xs bg-base-200/50">
                        <option value="">{{ __('Semua tipe dokumen') }}</option>
                        @foreach(\App\Models\DocumentType::orderBy('name')->get() as $dt)
                            <option value="{{ $dt->id }}">{{ $dt->code }} - {{ $dt->name }}</option>
                        @endforeach
                    </select>

                    <template x-if="pagination.total > 0">
                        <span class="text-[11px] text-base-content/40 ml-auto">
                            <span x-text="pagination.total"></span> {{ __('dokumen') }}
                        </span>
                    </template>
                </div>
            </div>

            {{-- Results --}}
            <div class="max-h-80 overflow-y-auto">
                {{-- Loading --}}
                <template x-if="loading">
                    <div class="flex items-center justify-center py-8">
                        <span class="loading loading-spinner loading-sm text-primary"></span>
                        <span class="ml-2 text-sm text-base-content/50">{{ __('Mencari...') }}</span>
                    </div>
                </template>

                {{-- Results list --}}
                <template x-if="!loading && results.length > 0">
                    <ul class="py-1">
                        <template x-for="item in results" :key="item.id">
                            <li>
                                <a :href="item.url"
                                   class="flex items-start gap-3 px-4 py-3 hover:bg-base-200 transition-colors">
                                    {{-- Icon by visibility --}}
                                    <div class="mt-0.5 shrink-0">
                                        <template x-if="item.visibility === 'general'">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </template>
                                        <template x-if="item.visibility === 'division'">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        </template>
                                        <template x-if="item.visibility === 'personal'">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-base-content truncate" x-text="item.title"></div>
                                        <div class="flex items-center gap-2 mt-0.5 text-xs text-base-content/50">
                                            <span x-text="item.document_number" class="font-mono"></span>
                                            <span>·</span>
                                            <span x-text="item.owner"></span>
                                            <template x-if="item.division">
                                                <span class="flex items-center gap-1">
                                                    <span>·</span>
                                                    <span x-text="item.division"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    <span class="text-xs text-base-content/30 whitespace-nowrap shrink-0 mt-0.5" x-text="item.updated_at"></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </template>

                {{-- No results --}}
                <template x-if="!loading && query.length >= 2 && results.length === 0">
                    <div class="flex flex-col items-center justify-center py-8 text-base-content/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm">{{ __('Tidak ada dokumen ditemukan') }}</span>
                    </div>
                </template>

                {{-- Empty state (waiting for input) --}}
                <template x-if="!loading && query.length < 2">
                    <div class="flex flex-col items-center justify-center py-8 text-base-content/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span class="text-sm">{{ __('Ketik minimal 2 karakter untuk mencari') }}</span>
                    </div>
                </template>
            </div>

            {{-- Footer hint and pagination --}}
            <div class="px-4 py-2 border-t border-base-300 flex items-center justify-between text-[11px] text-base-content/40">
                <template x-if="pagination.last_page > 1">
                    <div class="flex items-center gap-1">
                        <button type="button"
                                class="btn btn-ghost btn-xs"
                                :disabled="pagination.current_page <= 1"
                                @click="search(pagination.current_page - 1)">
                            « {{ __('Prev') }}
                        </button>
                        <span class="px-1 text-xs text-base-content/60">
                            <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                        </span>
                        <button type="button"
                                class="btn btn-ghost btn-xs"
                                :disabled="pagination.current_page >= pagination.last_page"
                                @click="search(pagination.current_page + 1)">
                            {{ __('Next') }} »
                        </button>
                    </div>
                </template>
                <template x-if="pagination.last_page <= 1">
                    <span>
                        <kbd class="px-1 py-0.5 font-mono bg-base-200 rounded border border-base-300">↵</kbd>
                        {{ __('untuk membuka') }}
                    </span>
                </template>

                <div class="flex items-center gap-2">
                    <span>
                        <kbd class="px-1 py-0.5 font-mono bg-base-200 rounded border border-base-300">Ctrl</kbd>
                        +
                        <kbd class="px-1 py-0.5 font-mono bg-base-200 rounded border border-base-300">K</kbd>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
