<x-app-layout>
    <x-slot name="header">{{ __('Dokumen Baru') }}</x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto w-full" x-data="{
            search: '',
            typeFilter: '',
            modalSearch: '',
            selectedModalType: '',
            selectedModalItems: [],
            previewLoading: false,
            previewError: '',
            previewTemplateTitle: '',
            templates: @js($templates->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'document_type_id' => $t->document_type_id,
                'document_type_code' => $t->documentType?->code ?? 'DOC',
                'document_type_label' => ($t->documentType?->code ?? 'DOC') . ' - ' . ($t->documentType?->name ?? 'Dokumen'),
                'file_name' => $t->file_original_name,
            ])),
            documentTypes: @js($documentTypes->map(fn($dt) => ['id' => $dt->id, 'label' => $dt->code . ' - ' . $dt->name])),
            get filteredTemplates() {
                return this.templates.filter(t => {
                    const matchSearch = !this.search || t.title.toLowerCase().includes(this.search.toLowerCase()) || (t.description && t.description.toLowerCase().includes(this.search.toLowerCase()));
                    const matchType = !this.typeFilter || t.document_type_id == this.typeFilter;
                    return matchSearch && matchType;
                });
            },
            get groupedTemplates() {
                const groups = {};
                this.filteredTemplates.forEach(t => {
                    if (!groups[t.document_type_label]) groups[t.document_type_label] = [];
                    groups[t.document_type_label].push(t);
                });
                return groups;
            },
            openPreview(templateId, templateTitle) {
                window.openTemplatePreview(templateId, templateTitle, this);
            },
            closePreview() {
                window.closeTemplatePreview(this);
            },
            get filteredModalItems() {
                if (!this.modalSearch.trim()) return this.selectedModalItems;
                const q = this.modalSearch.toLowerCase();
                return this.selectedModalItems.filter(t => 
                    (t.title && t.title.toLowerCase().includes(q)) || 
                    (t.description && t.description.toLowerCase().includes(q)) ||
                    (t.document_type_code && t.document_type_code.toLowerCase().includes(q)) ||
                    (t.file_name && t.file_name.toLowerCase().includes(q))
                );
            }
        }">

            {{-- Blank Document & Frequent Templates — Prominent Cards --}}
            <div class="mb-8">
                <h3 class="text-sm font-semibold text-base-content/50 uppercase tracking-wider mb-3">{{ __('Mulai Baru') }}</h3>
                <div class="flex flex-wrap gap-4">
                    {{-- Blank Document --}}
                    <a href="{{ route('documents.create') }}"
                       class="group w-36 h-48 flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer shadow-sm hover:shadow-md">
                        <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center mb-3 group-hover:bg-primary/20 group-hover:scale-110 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" /></svg>
                        </div>
                        <span class="text-sm font-medium text-base-content/70 group-hover:text-primary transition-colors">{{ __('Dokumen Kosong') }}</span>
                        <span class="text-[10px] text-base-content/40 mt-1">{{ __('Tulis manual / unggah') }}</span>
                    </a>

                    {{-- Frequently Used Templates --}}
                    @if(isset($frequentTemplates) && $frequentTemplates->isNotEmpty())
                        @foreach($frequentTemplates as $tmpl)
                        <div class="group w-36 h-48 flex flex-col rounded-xl border-2 border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer shadow-sm hover:shadow-md overflow-hidden relative">
                            <div @click="window.location.href = '{{ route('documents.create') }}?template_id={{ $tmpl->id }}'" class="flex-1 bg-gradient-to-br from-base-200/70 to-base-300/30 p-2.5 flex items-center justify-center relative overflow-hidden">
                                <div class="absolute top-2 left-2 bg-primary/15 text-primary text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10 backdrop-blur-sm">
                                    {{ __('Top') }}
                                </div>
                                {{-- Stylized Mini Paper Document --}}
                                <div class="w-20 h-24 bg-base-100 rounded-lg shadow-xs border border-base-300/70 p-2 flex flex-col justify-between group-hover:shadow-sm group-hover:scale-105 transition-all duration-200">
                                    <div>
                                        <div class="flex items-center justify-between gap-1 mb-1.5 pb-1 border-b border-base-200">
                                            <div class="w-3 h-3 rounded bg-primary/10 flex items-center justify-center shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-2 h-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="w-6 h-1 bg-primary/30 rounded-full"></div>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="w-full h-1 bg-base-content/15 rounded-full"></div>
                                            <div class="w-4/5 h-1 bg-base-content/15 rounded-full"></div>
                                            <div class="w-3/4 h-1 bg-base-content/10 rounded-full"></div>
                                            <div class="w-1/2 h-1 bg-base-content/10 rounded-full"></div>
                                        </div>
                                    </div>
                                    <div class="pt-0.5 flex items-center justify-between">
                                        <span class="text-[7.5px] font-bold text-primary truncate max-w-full px-1 py-0.5 rounded bg-primary/10">{{ $tmpl->documentType?->code ?? 'DOC' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Preview Button Overlay --}}
                            <button type="button" @click.stop="openPreview({{ $tmpl->id }}, '{{ addslashes($tmpl->title) }}')" class="absolute top-2 right-2 p-1.5 rounded-md bg-base-100/80 backdrop-blur text-base-content/70 opacity-0 group-hover:opacity-100 hover:text-primary hover:bg-base-100 transition-all shadow-sm z-20" title="Preview Template">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>

                            <div @click="window.location.href = '{{ route('documents.create') }}?template_id={{ $tmpl->id }}'" class="p-2 flex flex-col justify-center bg-base-100 z-10">
                                <div class="font-medium text-xs truncate group-hover:text-primary transition-colors" title="{{ $tmpl->title }}">{{ $tmpl->title }}</div>
                                <div class="text-[10px] text-base-content/40 mt-0.5 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    {{ $tmpl->documents_count }} {{ __('penggunaan') }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Template Gallery --}}
            @if($templates->isNotEmpty())
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <h3 class="text-sm font-semibold text-base-content/50 uppercase tracking-wider">{{ __('Dari Template') }}</h3>
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                        <input type="text" x-model="search" placeholder="{{ __('Cari template...') }}" class="input input-bordered input-sm w-full sm:w-52">
                        <select x-model="typeFilter" class="select select-bordered select-sm w-full sm:w-auto">
                            <option value="">{{ __('Semua Tipe') }}</option>
                            <template x-for="dt in documentTypes" :key="dt.id">
                                <option :value="dt.id" x-text="dt.label"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Grouped by Document Type --}}
                <template x-for="(items, typeName) in groupedTemplates" :key="typeName">
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-semibold text-base-content/70 flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                <span x-text="typeName"></span>
                                <span class="badge badge-sm badge-ghost ml-2" x-text="items.length"></span>
                            </h4>
                            <button type="button" class="btn btn-sm btn-ghost text-primary hover:bg-primary/10" @click="selectedModalType = typeName; selectedModalItems = items; modalSearch = ''; $refs.allTemplatesModal.showModal()">
                                {{ __('Lihat Semua') }}
                            </button>
                        </div>
                        
                        {{-- Horizontal Scroll Container --}}
                        <div class="flex overflow-x-auto pb-4 gap-4 snap-x">
                            <template x-for="tmpl in items.slice(0, 10)" :key="tmpl.id">
                                <div class="group flex-none w-40 h-48 flex flex-col rounded-xl border-2 border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer shadow-sm hover:shadow-md overflow-hidden snap-start relative">
                                    {{-- Preview thumbnail area --}}
                                    <div @click="window.location.href = '{{ route('documents.create') }}?template_id=' + tmpl.id" class="flex-1 bg-gradient-to-br from-base-200/70 to-base-300/30 p-2.5 flex items-center justify-center relative overflow-hidden">
                                        {{-- Stylized Mini Paper Document --}}
                                        <div class="w-22 h-26 bg-base-100 rounded-lg shadow-xs border border-base-300/70 p-2 flex flex-col justify-between group-hover:shadow-sm group-hover:scale-105 transition-all duration-200">
                                            <div>
                                                <div class="flex items-center justify-between gap-1 mb-1.5 pb-1 border-b border-base-200">
                                                    <div class="w-3 h-3 rounded bg-primary/10 flex items-center justify-center shrink-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-2 h-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </div>
                                                    <div class="w-7 h-1 bg-primary/30 rounded-full"></div>
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="w-full h-1 bg-base-content/15 rounded-full"></div>
                                                    <div class="w-4/5 h-1 bg-base-content/15 rounded-full"></div>
                                                    <div class="w-3/4 h-1 bg-base-content/10 rounded-full"></div>
                                                    <div class="w-1/2 h-1 bg-base-content/10 rounded-full"></div>
                                                </div>
                                            </div>
                                            <div class="pt-0.5 flex items-center justify-between">
                                                <span class="text-[7.5px] font-bold text-primary truncate max-w-full px-1 py-0.5 rounded bg-primary/10" x-text="tmpl.document_type_code || 'DOC'"></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Preview Button Overlay --}}
                                    <button type="button" @click.stop="openPreview(tmpl.id, tmpl.title)" class="absolute top-2 right-2 p-1.5 rounded-md bg-base-100/80 backdrop-blur text-base-content/70 opacity-0 group-hover:opacity-100 hover:text-primary hover:bg-base-100 transition-all shadow-sm z-20" title="Preview Template">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    {{-- Info --}}
                                    <div @click="window.location.href = '{{ route('documents.create') }}?template_id=' + tmpl.id" class="p-2 flex flex-col justify-center bg-base-100 z-10">
                                        <div class="font-medium text-xs truncate group-hover:text-primary transition-colors" x-text="tmpl.title" :title="tmpl.title"></div>
                                        <div class="text-[10px] text-base-content/40 mt-0.5 truncate" x-text="tmpl.description || tmpl.file_name"></div>
                                    </div>
                                </div>
                            </template>
                            
                            {{-- View All Card (shown if items > 10) --}}
                            <div x-show="items.length > 10" class="flex-none w-40 flex items-center justify-center snap-start">
                                <button type="button" @click="selectedModalType = typeName; selectedModalItems = items; modalSearch = ''; $refs.allTemplatesModal.showModal()"
                                        class="group flex flex-col items-center justify-center h-full w-full rounded-xl border-2 border-dashed border-base-300 bg-base-50 hover:border-primary hover:bg-primary/5 transition-all duration-200">
                                    <div class="w-10 h-10 rounded-full bg-base-200 group-hover:bg-primary/20 flex items-center justify-center mb-2 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-base-content/50 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                    </div>
                                    <span class="text-xs font-medium text-base-content/60 group-hover:text-primary">{{ __('Lihat Semua') }}</span>
                                    <span class="text-[10px] text-base-content/40" x-text="'+' + (items.length - 10) + ' ' + @json(__('template'))"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="filteredTemplates.length === 0" class="text-center py-8 text-base-content/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-sm">{{ __('Tidak ada template yang cocok dengan pencarian.') }}</p>
                </div>
            </div>
            @else
            <div class="text-center py-8 text-base-content/40">
                <p class="text-sm">{{ __('Belum ada template tersedia. Admin dapat mengunggah template di menu pengaturan.') }}</p>
            </div>
            @endif

            {{-- DaisyUI Modal for All Templates --}}
            <dialog x-ref="allTemplatesModal" class="modal">
                <div class="modal-box w-11/12 max-w-5xl">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                    </form>
                    
                    {{-- Modal Header with Title and Search --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pr-8">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                            <span x-text="selectedModalType"></span>
                            <span class="badge badge-sm badge-ghost ml-1" x-text="filteredModalItems.length"></span>
                        </h3>
                        
                        {{-- Modal Search Input --}}
                        <div class="relative w-full sm:w-72">
                            <input type="text" x-model="modalSearch" placeholder="{{ __('Cari di kategori ini...') }}" class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 focus:border-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <button type="button" x-show="modalSearch" @click="modalSearch = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content text-xs p-1">✕</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 max-h-[60vh] overflow-y-auto p-1">
                        <template x-for="tmpl in selectedModalItems" :key="tmpl.id">
                            <div class="group flex flex-col h-48 rounded-xl border-2 border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer shadow-sm hover:shadow-md overflow-hidden relative">
                                <div @click="window.location.href = '{{ route('documents.create') }}?template_id=' + tmpl.id" class="flex-1 bg-gradient-to-br from-base-200/70 to-base-300/30 p-2.5 flex items-center justify-center relative overflow-hidden">
                                    {{-- Stylized Mini Paper Document --}}
                                    <div class="w-22 h-26 bg-base-100 rounded-lg shadow-xs border border-base-300/70 p-2 flex flex-col justify-between group-hover:shadow-sm group-hover:scale-105 transition-all duration-200">
                                        <div>
                                            <div class="flex items-center justify-between gap-1 mb-1.5 pb-1 border-b border-base-200">
                                                <div class="w-3 h-3 rounded bg-primary/10 flex items-center justify-center shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2 h-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div class="w-7 h-1 bg-primary/30 rounded-full"></div>
                                            </div>
                                            <div class="space-y-1">
                                                <div class="w-full h-1 bg-base-content/15 rounded-full"></div>
                                                <div class="w-4/5 h-1 bg-base-content/15 rounded-full"></div>
                                                <div class="w-3/4 h-1 bg-base-content/10 rounded-full"></div>
                                                <div class="w-1/2 h-1 bg-base-content/10 rounded-full"></div>
                                            </div>
                                        </div>
                                        <div class="pt-0.5 flex items-center justify-between">
                                            <span class="text-[7.5px] font-bold text-primary truncate max-w-full px-1 py-0.5 rounded bg-primary/10" x-text="tmpl.document_type_code || 'DOC'"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Preview Button Overlay --}}
                                <button type="button" @click.stop="openPreview(tmpl.id, tmpl.title)" class="absolute top-2 right-2 p-1.5 rounded-md bg-base-100/80 backdrop-blur text-base-content/70 opacity-0 group-hover:opacity-100 hover:text-primary hover:bg-base-100 transition-all shadow-sm z-20" title="Preview Template">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                <div @click="window.location.href = '{{ route('documents.create') }}?template_id=' + tmpl.id" class="p-2 flex flex-col justify-center bg-base-100 z-10">
                                    <div class="font-medium text-xs truncate group-hover:text-primary transition-colors" x-text="tmpl.title" :title="tmpl.title"></div>
                                    <div class="text-[10px] text-base-content/40 mt-0.5 truncate" x-text="tmpl.description || tmpl.file_name"></div>
                                </div>
                            </div>
                        </template>

                        <div x-show="filteredModalItems.length === 0" class="col-span-full py-12 text-center text-base-content/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-2 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-sm">{{ __('Tidak ada template yang cocok dengan pencarian.') }}</p>
                        </div>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>{{ __('Tutup') }}</button>
                </form>
            </dialog>
            {{-- Template Preview Modal (Direct ONLYOFFICE, no iframe) --}}
            <dialog x-ref="previewModal" class="modal" @close="closePreview()">
                <div class="modal-box w-11/12 max-w-5xl h-[85vh] p-0 flex flex-col overflow-hidden relative rounded-xl border border-base-300">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between p-4 bg-base-100 border-b border-base-200 z-20 shrink-0 shadow-sm relative">
                        <h3 class="font-bold text-lg flex items-center gap-2 text-base-content">
                            <div class="w-8 h-8 rounded bg-primary/10 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <span>{{ __('Preview Template') }}<span x-show="previewTemplateTitle" class="font-normal text-base-content/60"> — <span x-text="previewTemplateTitle"></span></span></span>
                        </h3>
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost bg-base-200 hover:bg-base-300 text-base-content/70">✕</button>
                        </form>
                    </div>

                    {{-- Loading Indicator --}}
                    <div x-show="previewLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-base-100 z-10 mt-16">
                        <span class="loading loading-spinner text-primary w-10 h-10 mb-4"></span>
                        <p class="text-sm font-medium text-base-content/60 animate-pulse">{{ __('Memuat Preview Dokumen...') }}</p>
                    </div>

                    {{-- Error State --}}
                    <div x-show="previewError" x-cloak class="absolute inset-0 flex flex-col items-center justify-center bg-base-100 z-10 mt-16 px-8">
                        <div class="max-w-md text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-error mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-sm text-error font-medium" x-text="previewError"></p>
                        </div>
                    </div>

                    {{-- ONLYOFFICE Preview Container --}}
                    <div class="flex-1 w-full h-full relative overflow-hidden bg-base-100">
                        <div id="template-preview-editor" class="w-full h-full"></div>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        </div>
    </div>

    @push('scripts')
        <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js"></script>
        <script>
            window.openTemplatePreview = function(templateId, templateTitle, alpineContext) {
                alpineContext.previewLoading = true;
                alpineContext.previewError = '';
                alpineContext.previewTemplateTitle = templateTitle || 'Template';
                alpineContext.$refs.previewModal.showModal();

                const container = document.getElementById('template-preview-editor');
                if (container) container.innerHTML = '';

                // Ambil konfigurasi ONLYOFFICE untuk template
                const configUrl = '{{ url("/documents/templates") }}/' + templateId + '/preview-config';
                
                fetch(configUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(config => {
                    if (typeof DocsAPI === 'undefined') {
                        alpineContext.previewError = 'ONLYOFFICE server tidak dapat diakses.';
                        alpineContext.previewLoading = false;
                        return;
                    }

                    config.type = 'desktop';
                    config.editorConfig = config.editorConfig || {};
                    config.editorConfig.mode = 'view';
                    config.editorConfig.customization = config.editorConfig.customization || {};
                    config.editorConfig.customization.compactHeader = false;
                    config.editorConfig.customization.toolbarNoTabs = false;
                    config.editorConfig.customization.mobile = { force: false };

                    config.events = config.events || {};
                    config.events.onAppReady = function() {
                        alpineContext.previewLoading = false;
                    };
                    config.events.onDocumentReady = function() {
                        alpineContext.previewLoading = false;
                    };
                    config.events.onError = function(e) {
                        console.error('ONLYOFFICE error:', e);
                        alpineContext.previewLoading = false;
                    };

                    if (window._templateDocEditor) {
                        try { window._templateDocEditor.destroyEditor(); } catch(e) {}
                        window._templateDocEditor = null;
                    }

                    window._templateDocEditor = new DocsAPI.DocEditor("template-preview-editor", config);
                    
                    // Langsung hilangkan loading spinner begitu editor ONLYOFFICE dimuat ke DOM
                    setTimeout(() => {
                        alpineContext.previewLoading = false;
                    }, 500);
                })
                .catch(err => {
                    console.error('Preview error:', err);
                    alpineContext.previewLoading = false;
                    alpineContext.previewError = 'Gagal memuat pratinjau ONLYOFFICE: ' + err.message;
                });
            };

            window.closeTemplatePreview = function(alpineContext) {
                if (window._templateDocEditor) {
                    try { window._templateDocEditor.destroyEditor(); } catch(e) {}
                    window._templateDocEditor = null;
                }
                const container = document.getElementById('template-preview-editor');
                if (container) container.innerHTML = '';
                if (alpineContext) {
                    alpineContext.previewLoading = false;
                    alpineContext.previewError = '';
                    alpineContext.previewTemplateTitle = '';
                }
            };
        </script>
    @endpush
</x-app-layout>
