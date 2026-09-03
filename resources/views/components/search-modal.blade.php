{{-- Global Search Spotlight Modal — Redesigned modern Command Palette
     Features: Keyboard navigation (Arrow keys + Enter), filter pills, recent searches, quick launch cards, and rich document metadata.
     Keyboard shortcut: Ctrl+K / Cmd+K. --}}
<div x-data="{
        open: false,
        query: '',
        visibility: '',
        documentTypeId: '',
        results: [],
        pagination: { current_page: 1, last_page: 1, total: 0, has_more: false },
        loading: false,
        selectedIndex: -1,
        recentSearches: [],
        debounceTimer: null,

        init() {
            // Load recent searches from localStorage
            try {
                this.recentSearches = JSON.parse(localStorage.getItem('docuflow_recent_searches') || '[]');
            } catch (e) {
                this.recentSearches = [];
            }

            // Listen for open-search event from navbar topbar
            window.addEventListener('open-search', () => this.openModal());

            // Global Keyboard shortcut: Ctrl+K / Cmd+K
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    if (this.open) {
                        this.closeModal();
                    } else {
                        this.openModal();
                    }
                }
                if (e.key === 'Escape' && this.open) {
                    this.closeModal();
                }
            });
        },

        openModal() {
            this.open = true;
            this.query = '';
            this.visibility = '';
            this.documentTypeId = '';
            this.results = [];
            this.selectedIndex = -1;
            this.pagination = { current_page: 1, last_page: 1, total: 0, has_more: false };
            try {
                this.recentSearches = JSON.parse(localStorage.getItem('docuflow_recent_searches') || '[]');
            } catch (e) {}
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        closeModal() {
            this.open = false;
            this.query = '';
            this.visibility = '';
            this.documentTypeId = '';
            this.results = [];
            this.selectedIndex = -1;
            clearTimeout(this.debounceTimer);
        },

        clearQuery() {
            this.query = '';
            this.selectedIndex = -1;
            if (this.visibility || this.documentTypeId) {
                this.search(1);
            } else {
                this.results = [];
                this.pagination = { current_page: 1, last_page: 1, total: 0, has_more: false };
            }
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        setVisibility(vis) {
            this.visibility = (this.visibility === vis) ? '' : vis;
            this.search(1);
        },

        search(page = 1) {
            clearTimeout(this.debounceTimer);
            if (this.query.length < 2 && !this.documentTypeId && !this.visibility) {
                this.results = [];
                this.pagination = { current_page: 1, last_page: 1, total: 0, has_more: false };
                this.loading = false;
                this.selectedIndex = -1;
                return;
            }

            this.loading = true;
            this.debounceTimer = setTimeout(() => {
                const currentLang = document.documentElement.lang || '{{ app()->getLocale() }}';
                const url = new URL('{{ route('search') }}', window.location.origin);
                url.searchParams.set('q', this.query);
                url.searchParams.set('lang', currentLang);
                if (this.visibility) {
                    url.searchParams.set('visibility', this.visibility);
                }
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
                    this.selectedIndex = this.results.length > 0 ? 0 : -1;
                    if (this.query.trim().length >= 2) {
                        this.saveRecentSearch(this.query.trim());
                    }
                })
                .catch(() => {
                    this.loading = false;
                });
            }, 200);
        },

        saveRecentSearch(term) {
            if (!term || term.length < 2) return;
            let list = this.recentSearches.filter(t => t.toLowerCase() !== term.toLowerCase());
            list.unshift(term);
            this.recentSearches = list.slice(0, 5);
            try {
                localStorage.setItem('docuflow_recent_searches', JSON.stringify(this.recentSearches));
            } catch (e) {}
        },

        applyRecentSearch(term) {
            this.query = term;
            this.search(1);
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        removeRecentSearch(term) {
            this.recentSearches = this.recentSearches.filter(t => t !== term);
            try {
                localStorage.setItem('docuflow_recent_searches', JSON.stringify(this.recentSearches));
            } catch (e) {}
        },

        clearRecentSearches() {
            this.recentSearches = [];
            try {
                localStorage.removeItem('docuflow_recent_searches');
            } catch (e) {}
        },

        navigateDown() {
            if (this.results.length === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % this.results.length;
            this.scrollToSelected();
        },

        navigateUp() {
            if (this.results.length === 0) return;
            this.selectedIndex = (this.selectedIndex - 1 + this.results.length) % this.results.length;
            this.scrollToSelected();
        },

        scrollToSelected() {
            this.$nextTick(() => {
                const el = this.$refs['result_item_' + this.selectedIndex];
                if (el) {
                    el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });
        },

        selectAndGo(item) {
            if (this.query.trim().length >= 2) {
                this.saveRecentSearch(this.query.trim());
            }
            window.location.href = item.url;
        },

        selectCurrent() {
            if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                this.selectAndGo(this.results[this.selectedIndex]);
            }
        }
     }"
     x-cloak>

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 backdrop-blur-none"
         x-transition:enter-end="opacity-100 backdrop-blur-md"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 backdrop-blur-md"
         x-transition:leave-end="opacity-0 backdrop-blur-none"
         class="fixed inset-0 z-[100] bg-slate-950/60 backdrop-blur-md"
         @click="closeModal()">
    </div>

    {{-- Spotlight Modal Container --}}
    <div x-show="open"
         x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-4"
         class="fixed inset-x-0 top-[6%] sm:top-[10%] z-[101] mx-auto w-full max-w-2xl px-3 sm:px-4 pointer-events-none"
         @keydown.down.prevent="navigateDown()"
         @keydown.up.prevent="navigateUp()"
         @keydown.enter.prevent="selectCurrent()">

        <div class="pointer-events-auto bg-base-100/95 dark:bg-base-100/90 backdrop-blur-2xl border border-base-300/80 dark:border-white/10 rounded-2xl sm:rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.35)] overflow-hidden transition-all">
            
            {{-- Search Bar Header --}}
            <div class="p-3.5 sm:p-4 border-b border-base-200/80 dark:border-white/5 space-y-3">
                <div class="flex items-center gap-3 relative">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="loading ? 'animate-pulse' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <input x-ref="searchInput"
                           type="text"
                           x-model="query"
                           @input="search(1)"
                           class="flex-1 bg-transparent border-none outline-none text-base sm:text-lg font-medium text-base-content placeholder-base-content/40 min-w-0"
                           placeholder="{{ __('Cari judul dokumen, nomor surat, atau kata kunci...') }}" />

                    {{-- Clear button --}}
                    <button type="button"
                            x-show="query.length > 0"
                            x-transition
                            @click="clearQuery()"
                            class="btn btn-ghost btn-circle btn-xs text-base-content/40 hover:text-base-content">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    {{-- Loading Spinner --}}
                    <template x-if="loading">
                        <span class="loading loading-spinner loading-xs text-primary shrink-0"></span>
                    </template>

                    <kbd class="hidden sm:inline-flex items-center px-2 py-0.5 text-[10px] font-mono font-medium text-base-content/40 bg-base-200/80 rounded-lg border border-base-300 shadow-2xs">ESC</kbd>
                </div>

                {{-- Interactive Filter Chips Bar --}}
                <div class="flex items-center justify-between gap-2 flex-wrap pt-1 text-xs">
                    {{-- Scope Filters --}}
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button type="button"
                                @click="setVisibility('')"
                                class="px-2.5 py-1 rounded-full text-xs font-semibold transition-all cursor-pointer"
                                :class="visibility === '' ? 'bg-primary text-primary-content shadow-xs' : 'bg-base-200/60 hover:bg-base-200 text-base-content/70'">
                            {{ __('Semua') }}
                        </button>
                        <button type="button"
                                @click="setVisibility('general')"
                                class="px-2.5 py-1 rounded-full text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="visibility === 'general' ? 'bg-success text-success-content shadow-xs' : 'bg-base-200/60 hover:bg-base-200 text-base-content/70'">
                            <span class="w-1.5 h-1.5 rounded-full" :class="visibility === 'general' ? 'bg-white' : 'bg-success'"></span>
                            {{ __('Umum') }}
                        </button>
                        <button type="button"
                                @click="setVisibility('division')"
                                class="px-2.5 py-1 rounded-full text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="visibility === 'division' ? 'bg-warning text-warning-content shadow-xs' : 'bg-base-200/60 hover:bg-base-200 text-base-content/70'">
                            <span class="w-1.5 h-1.5 rounded-full" :class="visibility === 'division' ? 'bg-white' : 'bg-warning'"></span>
                            {{ __('Divisi') }}
                        </button>
                        <button type="button"
                                @click="setVisibility('personal')"
                                class="px-2.5 py-1 rounded-full text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="visibility === 'personal' ? 'bg-info text-info-content shadow-xs' : 'bg-base-200/60 hover:bg-base-200 text-base-content/70'">
                            <span class="w-1.5 h-1.5 rounded-full" :class="visibility === 'personal' ? 'bg-white' : 'bg-info'"></span>
                            {{ __('Personal') }}
                        </button>
                    </div>

                    {{-- Document Type Selector Dropdown --}}
                    <div class="flex items-center gap-2">
                        <select x-model="documentTypeId"
                                @change="search(1)"
                                class="select select-bordered select-xs text-xs rounded-full bg-base-200/70 focus:outline-none max-w-[150px] sm:max-w-[200px] truncate">
                            <option value="">{{ __('Semua Tipe') }}</option>
                            @foreach(\App\Models\DocumentType::orderBy('name')->get() as $dt)
                                <option value="{{ $dt->id }}">{{ $dt->code }} - {{ $dt->name }}</option>
                            @endforeach
                        </select>

                        <template x-if="pagination.total > 0">
                            <span class="badge badge-sm badge-neutral font-medium">
                                <span x-text="pagination.total"></span> {{ __('hasil') }}
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Results Viewport --}}
            <div class="max-h-[380px] overflow-y-auto overscroll-contain p-2 space-y-1 divide-y divide-base-200/40">
                
                {{-- Zero-State: Recent Searches & Idle State (when query is empty and no filters) --}}
                <div x-show="query.length < 2 && !documentTypeId && !visibility && !loading" class="p-3 space-y-3">
                    {{-- Recent Searches --}}
                    <template x-if="recentSearches.length > 0">
                        <div>
                            <div class="flex items-center justify-between text-[11px] font-semibold text-base-content/50 uppercase tracking-wider mb-2">
                                <span>{{ __('Pencarian Terakhir') }}</span>
                                <button type="button" @click="clearRecentSearches()" class="hover:text-error transition-colors">
                                    {{ __('Hapus Riwayat') }}
                                </button>
                            </div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <template x-for="term in recentSearches" :key="term">
                                    <div class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-base-200/70 hover:bg-base-200 border border-base-300/40 text-xs text-base-content transition-all group">
                                        <button type="button" @click="applyRecentSearch(term)" class="flex items-center gap-1.5 cursor-pointer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span x-text="term"></span>
                                        </button>
                                        <button type="button" @click.stop="removeRecentSearch(term)" class="text-base-content/30 hover:text-error ml-1">
                                            &times;
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Empty/Idle Guidance when no recent searches --}}
                    <template x-if="recentSearches.length === 0">
                        <div class="flex flex-col items-center justify-center py-8 text-center text-base-content/40 space-y-2">
                            <div class="w-10 h-10 rounded-2xl bg-base-200/70 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <div class="text-xs font-medium text-base-content/60">{{ __('Cari dokumen di seluruh cabang dan perusahaan Anda') }}</div>
                            <div class="text-[11px] text-base-content/40">{{ __('Ketik judul dokumen, nomor surat, nama divisi, atau kata kunci.') }}</div>
                        </div>
                    </template>
                </div>

                {{-- Loading State --}}
                <template x-if="loading">
                    <div class="flex flex-col items-center justify-center py-10 text-base-content/50 space-y-2">
                        <span class="loading loading-spinner loading-md text-primary"></span>
                        <span class="text-xs font-medium">{{ __('Mencari dokumen...') }}</span>
                    </div>
                </template>

                {{-- Results List --}}
                <template x-if="!loading && results.length > 0">
                    <div class="py-1 space-y-1">
                        <template x-for="(item, index) in results" :key="item.id">
                            <div :x-ref="'result_item_' + index"
                                 @click="selectAndGo(item)"
                                 @mouseenter="selectedIndex = index"
                                 class="group flex items-start gap-3 p-3 rounded-2xl transition-all cursor-pointer border"
                                 :class="selectedIndex === index ? 'bg-primary/10 border-primary/40 shadow-xs' : 'border-transparent hover:bg-base-200/60'">
                                
                                {{-- Scope Icon / Avatar --}}
                                <div class="mt-0.5 shrink-0">
                                    <template x-if="item.visibility === 'general'">
                                        <div class="w-8 h-8 rounded-xl bg-success/15 text-success flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                    </template>
                                    <template x-if="item.visibility === 'division'">
                                        <div class="w-8 h-8 rounded-xl bg-warning/15 text-warning flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        </div>
                                    </template>
                                    <template x-if="item.visibility === 'personal'">
                                        <div class="w-8 h-8 rounded-xl bg-info/15 text-info flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                        </div>
                                    </template>
                                </div>

                                {{-- Main Details --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-semibold text-base-content break-words" x-text="item.title"></span>
                                        <template x-if="item.is_expired">
                                            <span class="badge badge-error badge-xs font-semibold">{{ __('Kedaluwarsa') }}</span>
                                        </template>
                                    </div>

                                    <div class="flex items-center gap-2 mt-1 flex-wrap text-xs text-base-content/60">
                                        {{-- Number badge --}}
                                        <template x-if="item.document_number">
                                            <span class="font-mono text-[11px] px-1.5 py-0.5 rounded bg-base-200/80 text-base-content/70 border border-base-300/50" x-text="item.document_number"></span>
                                        </template>

                                        {{-- Format badge --}}
                                        <template x-if="item.format_choice === 'lama'">
                                            <span class="badge badge-secondary badge-outline badge-xs">{{ __('Format Lama') }}</span>
                                        </template>
                                        <template x-if="item.format_choice === 'baru'">
                                            <span class="badge badge-primary badge-outline badge-xs">{{ __('Format Baru') }}</span>
                                        </template>

                                        {{-- Type & Division --}}
                                        <template x-if="item.type">
                                            <span class="badge badge-ghost badge-xs" x-text="item.type"></span>
                                        </template>

                                        <template x-if="item.division">
                                            <span class="text-[11px] text-base-content/50" x-text="item.division"></span>
                                        </template>

                                        {{-- Branch badge if present --}}
                                        <template x-if="item.branch">
                                            <span class="text-[11px] text-base-content/40 flex items-center gap-1">
                                                <span>•</span>
                                                <span x-text="item.branch"></span>
                                            </span>
                                        </template>

                                        <span class="text-[11px] text-base-content/40 flex items-center gap-1">
                                            <span>•</span>
                                            <span x-text="item.owner"></span>
                                        </span>
                                    </div>
                                </div>

                                {{-- Time and Selection Action Badge --}}
                                <div class="shrink-0 text-right flex flex-col items-end gap-1">
                                    <span class="text-[11px] text-base-content/40" x-text="item.updated_at"></span>
                                    <span x-show="selectedIndex === index"
                                          x-transition
                                          class="hidden sm:inline-flex items-center gap-1 text-[10px] font-semibold text-primary uppercase">
                                        <span>{{ __('Buka') }}</span>
                                        <kbd class="kbd kbd-xs bg-primary/20 text-primary border-primary/30 font-mono">↵</kbd>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- No Results State --}}
                <template x-if="!loading && (query.length >= 2 || documentTypeId || visibility) && results.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-base-content/40 space-y-2 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-base-200 flex items-center justify-center mb-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="font-bold text-sm text-base-content/70">{{ __('Tidak ada dokumen ditemukan') }}</div>
                        <div class="text-xs text-base-content/50 max-w-xs leading-relaxed">
                            {{ __('Coba sesuaikan kata kunci pencarian, filter tipe dokumen, atau filter cakupan visibilitas.') }}
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer Command Legend & Pagination Controls --}}
            <div class="px-4 py-2.5 bg-base-200/40 dark:bg-base-200/20 border-t border-base-200/80 dark:border-white/5 flex items-center justify-between text-[11px] text-base-content/50 flex-wrap gap-2">
                
                {{-- Keyboard Shortcuts helper --}}
                <div class="hidden sm:flex items-center gap-3">
                    <span class="inline-flex items-center gap-1">
                        <kbd class="kbd kbd-xs bg-base-100 border-base-300 font-mono text-[9px]">↑</kbd>
                        <kbd class="kbd kbd-xs bg-base-100 border-base-300 font-mono text-[9px]">↓</kbd>
                        <span>{{ __('Navigasi') }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <kbd class="kbd kbd-xs bg-base-100 border-base-300 font-mono text-[9px]">↵</kbd>
                        <span>{{ __('Buka') }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <kbd class="kbd kbd-xs bg-base-100 border-base-300 font-mono text-[9px]">Esc</kbd>
                        <span>{{ __('Tutup') }}</span>
                    </span>
                </div>

                {{-- Pagination Controls --}}
                <template x-if="pagination.last_page > 1">
                    <div class="flex items-center gap-1.5 ml-auto">
                        <button type="button"
                                class="btn btn-ghost btn-xs rounded-lg gap-1"
                                :disabled="pagination.current_page <= 1"
                                @click="search(pagination.current_page - 1)">
                            « {{ __('Prev') }}
                        </button>
                        <span class="text-xs font-semibold px-1 text-base-content/70">
                            <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                        </span>
                        <button type="button"
                                class="btn btn-ghost btn-xs rounded-lg gap-1"
                                :disabled="pagination.current_page >= pagination.last_page"
                                @click="search(pagination.current_page + 1)">
                            {{ __('Next') }} »
                        </button>
                    </div>
                </template>
                <template x-if="pagination.last_page <= 1">
                    <span class="sm:hidden text-xs text-base-content/40">{{ __('Ketik untuk mencari') }}</span>
                </template>
            </div>

        </div>
    </div>
</div>
