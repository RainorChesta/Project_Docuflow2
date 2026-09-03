<x-app-layout>
    <x-slot name="header">{{ __('Direktori Dokumen Perusahaan') }}</x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- 1 & 2. Direktori and Search/Filter Card --}}
            <div class="bg-base-100 border border-base-300 rounded-2xl shadow-sm flex flex-col">
                {{-- Breadcrumbs Bar --}}
                <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center gap-4">

                    {{-- Breadcrumbs Navigation Trail --}}
                <nav class="flex items-center flex-wrap gap-1.5 text-xs font-medium bg-base-200/50 px-2.5 py-1.5 rounded-lg border border-base-300">
                        @if($parentUrl)
                            <a href="{{ $parentUrl }}" 
                               class="btn btn-ghost btn-xs btn-circle mr-1" 
                               title="{{ __('Kembali ke folder sebelumnya') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </a>
                        @endif

                        @foreach($breadcrumbs as $index => $crumb)
                            @if($index > 0)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            @endif

                            @if($crumb['active'] ?? false)
                                <span class="font-bold text-base-content flex items-center gap-1.5 px-2 py-0.5 bg-primary/10 rounded-md text-primary">
                                    @if(($crumb['icon'] ?? '') === 'home')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                    @elseif(($crumb['icon'] ?? '') === 'company')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    @elseif(($crumb['icon'] ?? '') === 'branch')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                                    @elseif(($crumb['icon'] ?? '') === 'division')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                                    @endif
                                    {{ $crumb['name'] }}
                                </span>
                            @else
                                <a href="{{ $crumb['url'] }}" 
                                   class="font-medium text-base-content/70 hover:text-primary transition-colors flex items-center gap-1.5 px-1.5 py-0.5 rounded-md hover:bg-base-200">
                                    @if(($crumb['icon'] ?? '') === 'home')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                    @elseif(($crumb['icon'] ?? '') === 'company')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    @elseif(($crumb['icon'] ?? '') === 'branch')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                                    @endif
                                    {{ $crumb['name'] }}
                                </a>
                            @endif
                        @endforeach
                    </nav>
                </div>

                {{-- Search & Filter Section --}}
                @if($selectedCompanyId && !$selectedBranchId)
                    <div class="px-4 sm:px-5 pb-4 sm:pb-5 space-y-4 border-t border-base-200 pt-4 sm:pt-5">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                            <h3 class="font-bold text-sm text-base-content">
                                {{ __('Cari Folder Cabang') }}
                            </h3>
                        </div>

                    <form method="GET" action="{{ route('admin.documents.index') }}" class="space-y-3">
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                        <input type="hidden" name="view_mode" value="{{ $viewMode }}">

                        <div class="flex flex-col sm:flex-row gap-3 w-full">
                            <div class="flex-grow relative">
                                <input type="text" name="search" value="{{ $search }}" 
                                       placeholder="{{ __('Search branch...') }}" 
                                       class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 shadow-sm focus:shadow-md focus:border-primary transition-all">
                                <svg class="w-4 h-4 absolute left-3 top-2.5 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                @if($search)
                                    <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" 
                                       class="absolute right-2.5 top-2 text-base-content/40 hover:text-base-content" 
                                       title="{{ __('Hapus pencarian') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </a>
                                @endif
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                <button type="submit" class="btn btn-primary btn-sm w-full sm:w-auto px-6">
                                    {{ __('Search') }}
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    @if($search)
                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-base-200 text-xs">
                            <span class="text-base-content/50 font-medium">{{ __('Filter Aktif:') }}</span>
                            <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                {{ __('Cari: ') }} "{{ $search }}"
                                <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="hover:text-error">✕</a>
                            </span>
                        </div>
                    @endif
                </div>
            @elseif($selectedBranchId)
                <div class="px-4 sm:px-5 pb-4 sm:pb-5 space-y-4 border-t border-base-200 pt-4 sm:pt-5">


                    {{-- Search & Filter Form --}}
                    <form method="GET" action="{{ route('admin.documents.index') }}" class="space-y-3">
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                        <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">
                        @if($selectedDivisionId)<input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">@endif
                        <input type="hidden" name="view_mode" value="{{ $viewMode }}">

                        <div class="flex flex-col lg:flex-row gap-3 w-full">
                            @if(!$selectedDivisionId)
                                {{-- Division Search (When in Branch) --}}
                                <div class="flex-grow relative">
                                    <input type="text" name="search" value="{{ $search }}" 
                                           placeholder="{{ __('Search division...') }}" 
                                           class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 shadow-sm focus:shadow-md focus:border-primary transition-all">
                                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    @if($search)
                                        <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" 
                                           class="absolute right-2.5 top-2 text-base-content/40 hover:text-base-content" 
                                           title="{{ __('Hapus pencarian') }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </a>
                                    @endif
                                </div>
                                <div class="shrink-0 flex items-center gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-full lg:w-auto px-6">
                                        {{ __('Search') }}
                                    </button>
                                </div>
                            @else
                                {{-- Document Search & Filters (When inside a Division) --}}
                                <div class="flex-grow relative">
                                    <input type="text" name="search" value="{{ $search }}" 
                                           placeholder="{{ __('Search documents...') }}" 
                                           class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 shadow-sm focus:shadow-md focus:border-primary transition-all">
                                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    @if($search)
                                        <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" 
                                           class="absolute right-2.5 top-2 text-base-content/40 hover:text-base-content" 
                                           title="{{ __('Hapus pencarian') }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </a>
                                    @endif
                                </div>

                                {{-- Document Type Filter --}}
                                <div class="w-full lg:w-48 shrink-0">
                                    <select name="document_type_id" 
                                            class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-sm focus:shadow-md focus:border-primary transition-all">
                                        <option value="">{{ __('Document Type') }} (Semua)</option>
                                        @foreach($availableDocumentTypes as $dt)
                                            <option value="{{ $dt->id }}" {{ $selectedDocTypeId == $dt->id ? 'selected' : '' }}>
                                                {{ $dt->name }} ({{ $dt->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Created By Filter --}}
                                <div class="w-full lg:w-48 shrink-0">
                                    <select name="owner_id" 
                                            class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-sm focus:shadow-md focus:border-primary transition-all">
                                        <option value="">{{ __('Created By') }} (Semua)</option>
                                        @foreach($availableCreators as $creator)
                                            <option value="{{ $creator->id }}" {{ $selectedOwnerId == $creator->id ? 'selected' : '' }}>
                                                {{ $creator->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Format Choice Filter --}}
                                <div class="w-full lg:w-44 shrink-0">
                                    <select name="format_choice" 
                                            class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-sm focus:shadow-md focus:border-primary transition-all">
                                        <option value="">{{ __('Semua Format') }}</option>
                                        <option value="baru" {{ ($selectedFormatChoice ?? '') === 'baru' ? 'selected' : '' }}>{{ __('Format Baru') }}</option>
                                        <option value="lama" {{ ($selectedFormatChoice ?? '') === 'lama' ? 'selected' : '' }}>{{ __('Format Lama') }}</option>
                                    </select>
                                </div>

                                {{-- Action Buttons: Search --}}
                                <div class="shrink-0 flex items-center gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-full lg:w-auto px-6">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </form>

                    {{-- Active Filter Badges & Reset Filter button --}}
                    @php
                        $hasActiveFilters = $search || $selectedDocTypeId || $selectedOwnerId || $selectedFormatChoice;
                    @endphp
                    @if($hasActiveFilters || $selectedDivisionId)
                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-base-200 text-xs">
                            <span class="text-base-content/50 font-medium">{{ __('Filter Aktif:') }}</span>
                            
                            @if($search)
                                <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                    {{ __('Cari: ') }} "{{ $search }}"
                                    <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="hover:text-error">✕</a>
                                </span>
                            @endif

                            @if($selectedDocTypeId)
                                @php $activeDocType = $availableDocumentTypes->firstWhere('id', $selectedDocTypeId); @endphp
                                @if($activeDocType)
                                    <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                        {{ __('Tipe: ') }} {{ $activeDocType->name }}
                                        <a href="{{ request()->fullUrlWithQuery(['document_type_id' => null]) }}" class="hover:text-error">✕</a>
                                    </span>
                                @endif
                            @endif

                            @if($selectedOwnerId)
                                @php $activeOwner = $availableCreators->firstWhere('id', $selectedOwnerId); @endphp
                                @if($activeOwner)
                                    <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                        {{ __('Pembuat: ') }} {{ $activeOwner->name }}
                                        <a href="{{ request()->fullUrlWithQuery(['owner_id' => null]) }}" class="hover:text-error">✕</a>
                                    </span>
                                @endif
                            @endif

                            @if($selectedFormatChoice)
                                <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                    {{ __('Format: ') }} {{ $selectedFormatChoice === 'lama' ? __('Format Lama') : __('Format Baru') }}
                                    <a href="{{ request()->fullUrlWithQuery(['format_choice' => null]) }}" class="hover:text-error">✕</a>
                                </span>
                            @endif

                            <a href="{{ route('admin.documents.index', ['company_id' => $selectedCompanyId, 'branch_id' => $selectedBranchId]) }}" 
                               class="btn btn-ghost btn-xs text-error hover:bg-error/10 ml-auto font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Reset Filter') }}
                            </a>
                        </div>
                    @endif
                </div>
            @endif
            </div>

            {{-- 3. FOLDERS SECTION (Company / Branch / Division Folders) --}}
            @if($folders->isNotEmpty())
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-base-content/60 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                            </svg>
                            @if(!$selectedCompanyId)
                                {{ __('Folder Perusahaan') }} ({{ $folders->count() }})
                            @elseif($selectedCompanyId && !$selectedBranchId)
                                {{ __('Folder Cabang') }} ({{ $folders->count() }})
                            @else
                                {{ __('Folder Divisi') }} ({{ $folders->count() }})
                            @endif
                        </h3>

                        {{-- View Mode Toggle for Folders / Divisions --}}
                        <div class="join border border-base-300 bg-base-200/50 p-0.5 rounded-lg">
                            <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'grid']) }}" 
                               class="join-item btn btn-xs {{ $viewMode === 'grid' ? 'btn-primary shadow-xs' : 'btn-ghost' }}"
                               title="{{ __('Tampilan Grid') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span class="text-xs">{{ __('Grid') }}</span>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'list']) }}" 
                               class="join-item btn btn-xs {{ $viewMode === 'list' ? 'btn-primary shadow-xs' : 'btn-ghost' }}"
                               title="{{ __('Tampilan List') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <span class="text-xs">{{ __('List') }}</span>
                            </a>
                        </div>
                    </div>

                    @if($viewMode === 'grid')
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3.5 sm:gap-4">
                            @foreach($folders as $folder)
                                <a href="{{ $folder['url'] }}" 
                                   title="{{ $folder['name'] }}"
                                   class="group bg-base-100 hover:bg-base-200/50 dark:hover:bg-base-200/30 border border-base-300/80 hover:border-primary/50 dark:hover:border-primary/50 rounded-2xl p-4 transition-all duration-200 shadow-xs hover:shadow-lg hover:-translate-y-0.5 flex flex-col justify-between gap-3.5 relative overflow-hidden ring-0 hover:ring-2 hover:ring-primary/10">
                                    
                                    {{-- Top Row: Folder Icon & Entity Badges --}}
                                    <div class="flex items-start justify-between gap-2.5">
                                        {{-- Folder Icon --}}
                                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 
                                            {{ $folder['type'] === 'company' ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : ($folder['type'] === 'branch' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-primary/10 text-primary') }}
                                            group-hover:scale-110 transition-transform duration-200 shadow-xs">
                                            @if($folder['type'] === 'company')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            @elseif($folder['type'] === 'branch')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                            @endif
                                        </div>

                                        {{-- Badges --}}
                                        <div class="flex items-center gap-1.5 shrink-0 flex-wrap justify-end max-w-[55%]">
                                            @if($folder['is_pusat'] ?? false)
                                                <span class="badge badge-primary badge-sm font-bold shadow-xs">{{ __('Pusat') }}</span>
                                            @endif
                                            @if(!empty($folder['code']))
                                                <span class="badge badge-sm font-mono font-bold bg-base-200/80 border border-base-300 text-base-content/80 group-hover:border-primary/40 transition-colors" title="{{ __('Kode: ') . $folder['code'] }}">{{ $folder['code'] }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Middle: Entity Name with 2-line clamp and guaranteed uniform height --}}
                                    <div class="flex-1 flex flex-col justify-start">
                                        <h4 class="font-bold text-sm text-base-content group-hover:text-primary transition-colors line-clamp-2 leading-snug min-h-[2.6rem] break-words" title="{{ $folder['name'] }}">
                                            {{ $folder['name'] }}
                                        </h4>
                                    </div>

                                    {{-- Bottom: Metadata & Arrow affordance --}}
                                    <div class="pt-2.5 border-t border-base-200/70 dark:border-white/5 flex items-center justify-between text-xs text-base-content/60">
                                        <div class="flex items-center gap-1.5 font-medium truncate">
                                            @if(isset($folder['sub_count']))
                                                <span>{{ $folder['sub_count'] }} {{ $folder['sub_label'] }}</span>
                                                <span class="text-base-content/30">•</span>
                                            @endif
                                            <span class="flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                {{ $folder['doc_count'] ?? 0 }} {{ __('Dokumen') }}
                                            </span>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-base-content/30 group-hover:text-primary group-hover:translate-x-0.5 transition-all duration-200 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        {{-- LIST VIEW --}}
                        <div class="bg-base-100 border border-base-300 rounded-2xl overflow-hidden shadow-xs">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full text-xs">
                                    <thead>
                                        <tr class="bg-base-200/50 text-base-content/70">
                                            <th>
                                                @if(!$selectedCompanyId)
                                                    {{ __('Nama Perusahaan') }}
                                                @elseif($selectedCompanyId && !$selectedBranchId)
                                                    {{ __('Nama Cabang') }}
                                                @else
                                                    {{ __('Nama Divisi') }}
                                                @endif
                                            </th>
                                            <th>{{ __('Kode') }}</th>
                                            <th>{{ __('Tipe / Kategori') }}</th>
                                            <th>{{ __('Total Dokumen') }}</th>
                                            <th class="text-right">{{ __('Aksi') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($folders as $folder)
                                            <tr class="hover cursor-pointer" onclick="window.location='{{ $folder['url'] }}'">
                                                <td>
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 
                                                            {{ $folder['type'] === 'company' ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : ($folder['type'] === 'branch' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-primary/10 text-primary') }}">
                                                            @if($folder['type'] === 'company')
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                                </svg>
                                                            @elseif($folder['type'] === 'branch')
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                                                </svg>
                                                            @else
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                                </svg>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <a href="{{ $folder['url'] }}" class="font-bold text-sm text-base-content hover:text-primary transition-colors">
                                                                {{ $folder['name'] }}
                                                            </a>
                                                            @if(isset($folder['sub_count']))
                                                                <span class="text-xs text-base-content/50 block">{{ $folder['sub_count'] }} {{ $folder['sub_label'] }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if(!empty($folder['code']))
                                                        <span class="badge badge-ghost badge-sm font-mono font-bold">{{ $folder['code'] }}</span>
                                                    @else
                                                        <span class="text-base-content/40">—</span>
                                                    @endif
                                                    @if($folder['is_pusat'] ?? false)
                                                        <span class="badge badge-primary badge-xs font-semibold ml-1">{{ __('Pusat') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($folder['type'] === 'company')
                                                        <span class="badge badge-outline badge-sm text-indigo-600 dark:text-indigo-400">{{ __('Perusahaan') }}</span>
                                                    @elseif($folder['type'] === 'branch')
                                                        <span class="badge badge-outline badge-sm text-amber-600 dark:text-amber-400">{{ __('Cabang') }}</span>
                                                    @else
                                                        <span class="badge badge-outline badge-sm text-primary">{{ __('Divisi') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="font-semibold text-base-content">{{ $folder['doc_count'] ?? 0 }}</span>
                                                    <span class="text-base-content/50 text-[11px]">{{ __('Dokumen') }}</span>
                                                </td>
                                                <td class="text-right">
                                                    <a href="{{ $folder['url'] }}" class="btn btn-ghost btn-xs text-primary font-medium gap-1 hover:bg-primary/10">
                                                        <span>{{ __('Buka') }}</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @elseif($search && !$selectedDivisionId)
                <div class="bg-base-100 border border-base-300 rounded-2xl p-10 text-center shadow-xs">
                    <div class="w-14 h-14 rounded-2xl bg-base-200/80 text-base-content/30 flex items-center justify-center mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    @if(!$selectedCompanyId)
                        <h4 class="font-bold text-base text-base-content">{{ __('No company folders found.') }}</h4>
                    @elseif($selectedCompanyId && !$selectedBranchId)
                        <h4 class="font-bold text-base text-base-content">{{ __('No branch folders found.') }}</h4>
                    @else
                        <h4 class="font-bold text-base text-base-content">{{ __('No division folders found.') }}</h4>
                    @endif
                    <p class="text-xs text-base-content/60 mt-1 max-w-md mx-auto">
                        {{ __('Tidak ada folder yang sesuai dengan kata kunci pencarian yang dimasukkan.') }}
                    </p>
                    <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="btn btn-primary btn-sm mt-3">
                        {{ __('Bersihkan Pencarian') }}
                    </a>
                </div>
            @endif

            {{-- 4. DOCUMENT RESULTS SECTION (Only rendered when in a division) --}}
            @if($selectedDivisionId)
                <div class="space-y-4 pt-2">
                    
                    {{-- Results Header with View Mode Toggle --}}
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-base-content/60 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Hasil Dokumen') }}
                            <span class="badge badge-sm badge-ghost font-normal">{{ $documents->total() }}</span>
                        </h3>

                        {{-- View Mode Toggle --}}
                        <div class="join border border-base-300 bg-base-200/50 p-0.5 rounded-lg">
                            <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'grid']) }}" 
                               class="join-item btn btn-xs {{ $viewMode === 'grid' ? 'btn-primary shadow-xs' : 'btn-ghost' }}"
                               title="{{ __('Tampilan Grid') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span class="text-xs">{{ __('Grid') }}</span>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'list']) }}" 
                               class="join-item btn btn-xs {{ $viewMode === 'list' ? 'btn-primary shadow-xs' : 'btn-ghost' }}"
                               title="{{ __('Tampilan List') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <span class="text-xs">{{ __('List') }}</span>
                            </a>
                        </div>
                    </div>

                    @if($documents->isNotEmpty())
                        {{-- GRID VIEW --}}
                        @if($viewMode === 'grid')
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-3 bg-base-100 border border-base-300 rounded-xl shadow-sm p-3">
                                @foreach($documents as $doc)
                                    @include('documents._grid', ['doc' => $doc])
                                @endforeach
                            </div>
                        {{-- LIST VIEW --}}
                        @else
                            <div class="bg-base-100 border border-base-300 rounded-2xl overflow-hidden shadow-xs">
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra w-full text-xs">
                                        <thead>
                                            <tr class="bg-base-200/50 text-base-content/70">
                                                <th>{{ __('Judul Dokumen') }}</th>
                                                <th>{{ __('No. Dokumen') }}</th>
                                                <th>{{ __('Cabang & Perusahaan') }}</th>
                                                <th>{{ __('Divisi') }}</th>
                                                <th>{{ __('Tipe') }}</th>
                                                <th>{{ __('Pembuat') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th class="text-right">{{ __('Aksi') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($documents as $doc)
                                                @php
                                                    $hasDraft = $doc->versions->contains('status', 'draft');
                                                    $hasPending = $doc->versions->contains('status', 'pending');
                                                @endphp
                                                <tr class="hover">
                                                    <td>
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                            </div>
                                                            <a href="{{ route('documents.show', $doc) }}" 
                                                               class="font-bold text-sm text-base-content hover:text-primary transition-colors max-w-xs truncate block">
                                                                {{ $doc->title }}
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td class="font-mono text-base-content/60">
                                                        <div class="flex items-center gap-1.5 flex-wrap">
                                                            <span>{{ $doc->document_number }}</span>
                                                            @if($doc->format_choice === 'lama')
                                                                <span class="badge badge-secondary badge-outline badge-xs">{{ __('Format Lama') }}</span>
                                                            @else
                                                                <span class="badge badge-primary badge-outline badge-xs">{{ __('Format Baru') }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($doc->branch)
                                                            <span class="font-medium">{{ $doc->branch->name }}</span>
                                                            @if($doc->branch->is_pusat)<span class="text-primary font-semibold">(Pusat)</span>@endif
                                                            <span class="text-base-content/40 block">{{ $doc->branch->company?->name }}</span>
                                                        @else
                                                            <span class="text-base-content/40">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($doc->division)
                                                            <span class="badge badge-ghost badge-sm max-w-[140px] inline-flex items-center" title="{{ $doc->division->name }}">
                                                                <span class="truncate">{{ $doc->division->name }}</span>
                                                            </span>
                                                        @else
                                                            <span class="text-base-content/40">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($doc->documentType)
                                                            <span class="badge badge-outline badge-sm max-w-[140px] inline-flex items-center" title="{{ $doc->documentType->name }}">
                                                                <span class="truncate">{{ $doc->documentType->name }}</span>
                                                            </span>
                                                        @else
                                                            <span class="text-base-content/40">—</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $doc->owner->name }}</td>
                                                    <td>
                                                        @if($doc->currentVersion)
                                                            <span class="badge badge-success badge-xs font-semibold">v{{ $doc->currentVersion->version_number }}</span>
                                                        @elseif($hasPending)
                                                            <span class="badge badge-warning badge-xs font-semibold">{{ __('Tertunda') }}</span>
                                                        @elseif($hasDraft)
                                                            <span class="badge badge-info badge-xs font-semibold">{{ __('Draf') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-right">
                                                        <div class="flex items-center justify-end gap-1">
                                                            <a href="{{ route('documents.preview', $doc) }}" 
                                                               class="btn btn-ghost btn-xs btn-circle"
                                                               title="{{ __('Pratinjau') }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                </svg>
                                                            </a>
                                                            <a href="{{ route('documents.show', $doc) }}" 
                                                               class="btn btn-ghost btn-xs text-primary font-semibold">
                                                                {{ __('Buka') }}
                                                            </a>
                                                            @if(auth()->user()->isAdmin())
                                                                <button type="button" onclick="event.stopPropagation(); document.getElementById('admin-del-doc-modal-{{ $doc->id }}').showModal()" class="btn btn-ghost btn-xs btn-circle text-error" title="{{ __('Hapus') }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                                </button>

                                                                <dialog id="admin-del-doc-modal-{{ $doc->id }}" onclick="event.stopPropagation();" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                                                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-sm">
                                                                        <div class="p-6 pb-4">
                                                                            <div class="flex items-start justify-between gap-4">
                                                                                <div class="flex items-center gap-3.5">
                                                                                    <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                                        </svg>
                                                                                    </div>
                                                                                    <div>
                                                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Hapus Dokumen') }}</h3>
                                                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Tindakan ini tidak bisa dibatalkan.') }}</p>
                                                                                    </div>
                                                                                </div>
                                                                                <button type="button" onclick="document.getElementById('admin-del-doc-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                                    ✕
                                                                                </button>
                                                                            </div>
                                                                            <p class="text-sm text-base-content/70 mt-3">
                                                                                {{ __('Hapus dokumen :title beserta semua versinya?', ['title' => $doc->title]) }}
                                                                            </p>
                                                                        </div>
                                                                        <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}">
                                                                            @csrf @method('DELETE')
                                                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                                                <button type="button" onclick="document.getElementById('admin-del-doc-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                                    {{ __('Batal') }}
                                                                                </button>
                                                                                <button type="submit" class="btn btn-error btn-sm text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                                    </svg>
                                                                                    {{ __('Hapus') }}
                                                                                </button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                    <form method="dialog" class="modal-backdrop">
                                                                        <button>{{ __('Batal') }}</button>
                                                                    </form>
                                                                </dialog>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Pagination --}}
                        @if($documents->hasPages())
                            <div class="pt-2">
                                {{ $documents->links() }}
                            </div>
                        @endif
                    @else
                        {{-- Empty State when no documents found --}}
                        <div class="bg-base-100 border border-base-300 rounded-2xl p-10 text-center shadow-xs">
                            <div class="w-14 h-14 rounded-2xl bg-base-200/80 text-base-content/30 flex items-center justify-center mx-auto mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h4 class="font-bold text-base text-base-content">{{ __('Tidak Ada Dokumen Ditemukan') }}</h4>
                            <p class="text-xs text-base-content/60 mt-1 max-w-md mx-auto">
                                {{ __('Tidak ada dokumen pada cabang ini yang sesuai dengan filter atau kata kunci pencarian yang dipilih.') }}
                            </p>
                            @if($hasActiveFilters)
                                <a href="{{ route('admin.documents.index', ['company_id' => $selectedCompanyId, 'branch_id' => $selectedBranchId]) }}" 
                                   class="btn btn-primary btn-sm mt-3">
                                    {{ __('Bersihkan Filter') }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
