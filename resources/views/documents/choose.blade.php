<x-app-layout>
    <x-slot name="header">{{ __('Dokumen Baru') }}</x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto w-full" x-data="{
            search: '',
            typeFilter: '',
            selectedModalType: '',
            selectedModalItems: [],
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
                        <a href="{{ route('documents.create') }}?template_id={{ $tmpl->id }}"
                           class="group w-36 h-48 flex flex-col rounded-xl border-2 border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer shadow-sm hover:shadow-md overflow-hidden">
                            <div class="flex-1 bg-gradient-to-br from-base-200/70 to-base-300/30 p-2.5 flex items-center justify-center relative overflow-hidden">
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
                            <div class="p-2 flex flex-col justify-center bg-base-100 z-10">
                                <div class="font-medium text-xs truncate group-hover:text-primary transition-colors" title="{{ $tmpl->title }}">{{ $tmpl->title }}</div>
                                <div class="text-[10px] text-base-content/40 mt-0.5 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    {{ $tmpl->documents_count }} {{ __('penggunaan') }}
                                </div>
                            </div>
                        </a>
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
                            <button type="button" class="btn btn-sm btn-ghost text-primary hover:bg-primary/10" @click="selectedModalType = typeName; selectedModalItems = items; $refs.allTemplatesModal.showModal()">
                                {{ __('Lihat Semua') }}
                            </button>
                        </div>
                        
                        {{-- Horizontal Scroll Container --}}
                        <div class="flex overflow-x-auto pb-4 gap-4 snap-x">
                            <template x-for="tmpl in items.slice(0, 10)" :key="tmpl.id">
                                <a :href="'{{ route('documents.create') }}?template_id=' + tmpl.id"
                                   class="group flex-none w-40 h-48 flex flex-col rounded-xl border-2 border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer shadow-sm hover:shadow-md overflow-hidden snap-start">
                                    {{-- Preview thumbnail area --}}
                                    <div class="flex-1 bg-gradient-to-br from-base-200/70 to-base-300/30 p-2.5 flex items-center justify-center relative overflow-hidden">
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
                                    {{-- Info --}}
                                    <div class="p-2 flex flex-col justify-center bg-base-100 z-10">
                                        <div class="font-medium text-xs truncate group-hover:text-primary transition-colors" x-text="tmpl.title" :title="tmpl.title"></div>
                                        <div class="text-[10px] text-base-content/40 mt-0.5 truncate" x-text="tmpl.description || tmpl.file_name"></div>
                                    </div>
                                </a>
                            </template>
                            
                            {{-- View All Card (shown if items > 10) --}}
                            <div x-show="items.length > 10" class="flex-none w-40 flex items-center justify-center snap-start">
                                <button type="button" @click="selectedModalType = typeName; selectedModalItems = items; $refs.allTemplatesModal.showModal()"
                                        class="group flex flex-col items-center justify-center h-full w-full rounded-xl border-2 border-dashed border-base-300 bg-base-50 hover:border-primary hover:bg-primary/5 transition-all duration-200">
                                    <div class="w-10 h-10 rounded-full bg-base-200 group-hover:bg-primary/20 flex items-center justify-center mb-2 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-base-content/50 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                    </div>
                                    <span class="text-xs font-medium text-base-content/60 group-hover:text-primary">{{ __('Lihat Semua') }}</span>
                                    <span class="text-[10px] text-base-content/40" x-text="'+' + (items.length - 10) + ' template'"></span>
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
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                        <span x-text="selectedModalType"></span>
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 max-h-[60vh] overflow-y-auto p-1">
                        <template x-for="tmpl in selectedModalItems" :key="tmpl.id">
                            <a :href="'{{ route('documents.create') }}?template_id=' + tmpl.id"
                               class="group flex flex-col h-48 rounded-xl border-2 border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer shadow-sm hover:shadow-md overflow-hidden">
                                <div class="flex-1 bg-gradient-to-br from-base-200/70 to-base-300/30 p-2.5 flex items-center justify-center relative overflow-hidden">
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
                                <div class="p-2 flex flex-col justify-center bg-base-100 z-10">
                                    <div class="font-medium text-xs truncate group-hover:text-primary transition-colors" x-text="tmpl.title" :title="tmpl.title"></div>
                                    <div class="text-[10px] text-base-content/40 mt-0.5 truncate" x-text="tmpl.description || tmpl.file_name"></div>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        </div>
    </div>
</x-app-layout>
