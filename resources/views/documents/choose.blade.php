<x-app-layout>
    <x-slot name="header">{{ __('Dokumen Baru') }}</x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto w-full" x-data="{
            search: '',
            typeFilter: '',
            templates: @js($templates->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'document_type_id' => $t->document_type_id,
                'document_type_label' => $t->documentType->code . ' - ' . $t->documentType->name,
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
                        <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
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
                            <div class="h-28 bg-base-200/50 flex items-center justify-center border-b border-base-300 relative">
                                <div class="absolute top-2 left-2 bg-primary/10 text-primary text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide">
                                    {{ __('Top') }}
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-primary/30 group-hover:text-primary/50 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <div class="p-2.5 flex flex-col h-full justify-between">
                                <div class="font-medium text-sm truncate group-hover:text-primary transition-colors">{{ $tmpl->title }}</div>
                                <div class="text-[10px] text-base-content/40 mt-1 flex items-center gap-1">
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
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-sm font-semibold text-base-content/50 uppercase tracking-wider">{{ __('Dari Template') }}</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" x-model="search" placeholder="{{ __('Cari template...') }}" class="input input-bordered input-sm w-52">
                        <select x-model="typeFilter" class="select select-bordered select-sm">
                            <option value="">{{ __('Semua Tipe') }}</option>
                            <template x-for="dt in documentTypes" :key="dt.id">
                                <option :value="dt.id" x-text="dt.label"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Grouped by Document Type — horizontal scroll, one row per category --}}
                <div class="flex flex-row gap-5 overflow-x-auto pb-3"
                     style="scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;">
                    <template x-for="(items, typeName) in groupedTemplates" :key="typeName">
                        <div class="flex-shrink-0 w-52" style="scroll-snap-align: start;">
                            <h4 class="text-xs font-semibold text-base-content/60 mb-2 flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                <span x-text="typeName" class="truncate"></span>
                            </h4>
                            <div class="flex flex-col gap-3">
                                <template x-for="tmpl in items" :key="tmpl.id">
                                    <a :href="'{{ route('documents.create') }}?template_id=' + tmpl.id"
                                       class="group flex flex-col rounded-xl border-2 border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer shadow-sm hover:shadow-md overflow-hidden">
                                        {{-- Preview thumbnail area --}}
                                        <div class="h-28 bg-base-200/50 flex items-center justify-center border-b border-base-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-primary/30 group-hover:text-primary/50 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                        {{-- Info --}}
                                        <div class="p-2.5">
                                            <div class="font-medium text-sm truncate group-hover:text-primary transition-colors" x-text="tmpl.title"></div>
                                            <div class="text-[10px] text-base-content/40 mt-0.5 truncate" x-text="tmpl.description || tmpl.file_name"></div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

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
        </div>
    </div>
</x-app-layout>
