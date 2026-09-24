<x-app-layout>
    <x-slot name="header">{{ __('Tinjauan Dokumen Aktif') }}</x-slot>

    <div class="py-4 sm:py-6 px-3 sm:px-4 lg:px-6 space-y-4 sm:space-y-6">
        <div class="max-w-7xl mx-auto w-full space-y-4 sm:space-y-6">

            {{-- 1. Metric Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                {{-- Total Active Docs --}}
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}" 
                   class="bg-base-100 border {{ $tab === 'all' ? 'border-primary ring-2 ring-primary/25 bg-primary/[0.03] shadow-xs' : 'border-base-300 hover:border-primary/40' }} rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between transition-all duration-200 hover:shadow-md group">
                    {{-- Top Row: Label & Icon --}}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-base-content/60 group-hover:text-primary transition-colors">
                            {{ __('Total Dokumen Aktif') }}
                        </span>
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-primary group-hover:text-white transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Bottom Row: Value & Status Pill --}}
                    <div class="mt-3 sm:mt-4 flex items-end justify-between gap-2 pt-2 border-t border-base-200/60">
                        <div>
                            <span class="text-2xl sm:text-3xl font-black tracking-tight text-base-content leading-none block">
                                {{ number_format($totalActiveCount) }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-[11px] sm:text-xs font-semibold bg-base-200 text-base-content/70 border border-base-300 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"></span>
                            {{ __('Semua Cabang') }}
                        </span>
                    </div>
                </a>

                {{-- Unseen by Director (Belum Ditinjau) --}}
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'unseen', 'page' => 1]) }}" 
                   class="bg-base-100 border {{ $tab === 'unseen' ? 'border-primary ring-2 ring-primary/25 bg-primary/[0.03] shadow-xs' : 'border-base-300 hover:border-primary/40' }} rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between transition-all duration-200 hover:shadow-md group">
                    {{-- Top Row: Label & Icon --}}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-base-content/60 group-hover:text-primary transition-colors">
                            {{ __('Belum Ditinjau') }}
                        </span>
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-primary group-hover:text-white transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Bottom Row: Value & Status Pill --}}
                    <div class="mt-3 sm:mt-4 flex items-end justify-between gap-2 pt-2 border-t border-base-200/60">
                        <div>
                            <span class="text-2xl sm:text-3xl font-black tracking-tight text-base-content leading-none block">
                                {{ number_format($unseenActiveCount) }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-[11px] sm:text-xs font-semibold bg-base-200 text-base-content/70 border border-base-300 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse shrink-0"></span>
                            {{ __('Perlu Ditinjau') }}
                        </span>
                    </div>
                </a>

                {{-- Seen by Director (Sudah Ditinjau) --}}
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'seen', 'page' => 1]) }}" 
                   class="bg-base-100 border {{ $tab === 'seen' ? 'border-secondary ring-2 ring-secondary/25 bg-secondary/[0.03] shadow-xs' : 'border-base-300 hover:border-secondary/40' }} rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between transition-all duration-200 hover:shadow-md group">
                    {{-- Top Row: Label & Icon --}}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-base-content/60 group-hover:text-secondary transition-colors">
                            {{ __('Sudah Ditinjau') }}
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-secondary group-hover:text-white transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Bottom Row: Value & Status Pill --}}
                    <div class="mt-3 sm:mt-4 flex items-end justify-between gap-2 pt-2 border-t border-base-200/60">
                        <div>
                            <span class="text-2xl sm:text-3xl font-black tracking-tight text-base-content leading-none block">
                                {{ number_format($seenActiveCount) }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-[11px] sm:text-xs font-semibold bg-base-200 text-base-content/70 border border-base-300 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Terkonfirmasi') }}
                        </span>
                    </div>
                </a>
            </div>

            {{-- 2. Filter Bar and Tabs Container --}}
            <div class="bg-base-100 border border-base-300 rounded-2xl shadow-xs p-3.5 sm:p-5 space-y-3.5 sm:space-y-4">
                
                {{-- Tabs & View Mode / Action Row --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-base-200 pb-3 sm:pb-4">
                    {{-- Status Tabs (Scrollable on small mobile) --}}
                    <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto pb-1 sm:pb-0 -mx-1 px-1 flex-nowrap sm:flex-wrap">
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}" 
                           class="btn btn-xs sm:btn-sm rounded-xl gap-1.5 sm:gap-2 font-semibold transition-all shrink-0 {{ $tab === 'all' ? 'btn-primary text-white shadow-xs' : 'btn-ghost text-base-content/70 hover:bg-base-200' }}">
                            <span>{{ __('Semua Dokumen Aktif') }}</span>
                            <span class="badge {{ $tab === 'all' ? 'badge-primary-content text-primary' : 'badge-ghost' }} badge-xs sm:badge-sm font-bold">{{ $totalActiveCount }}</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'unseen', 'page' => 1]) }}" 
                           class="btn btn-xs sm:btn-sm rounded-xl gap-1.5 sm:gap-2 font-semibold transition-all shrink-0 {{ $tab === 'unseen' ? 'btn-primary text-white shadow-xs' : 'btn-ghost text-base-content/70 hover:bg-base-200' }}">
                            <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full {{ $tab === 'unseen' ? 'bg-white' : 'bg-primary animate-pulse' }}"></span>
                            <span>{{ __('Belum Ditinjau') }}</span>
                            <span class="badge {{ $tab === 'unseen' ? 'badge-primary-content text-primary' : 'badge-ghost' }} badge-xs sm:badge-sm font-bold">{{ $unseenActiveCount }}</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'seen', 'page' => 1]) }}" 
                           class="btn btn-xs sm:btn-sm rounded-xl gap-1.5 sm:gap-2 font-semibold transition-all shrink-0 {{ $tab === 'seen' ? 'btn-secondary text-white shadow-xs' : 'btn-ghost text-base-content/70 hover:bg-base-200' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            <span>{{ __('Sudah Ditinjau') }}</span>
                            <span class="badge {{ $tab === 'seen' ? 'badge-secondary-content text-secondary' : 'badge-ghost' }} badge-xs sm:badge-sm font-bold">{{ $seenActiveCount }}</span>
                        </a>
                    </div>

                    {{-- Actions: Apply Filter Button & View Mode Toggle --}}
                    <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-base-200/60">
                        @php
                            $hasFilters = filled($search) || filled($selectedCompanyId) || filled($selectedBranchId) || filled($selectedUnitKerjaId) || filled($selectedDocTypeId) || filled($selectedOwnerId) || filled($selectedFormatChoice) || filled($selectedActiveDate) || filled($startDate) || filled($endDate);
                        @endphp
                        @if($hasFilters)
                            <a href="{{ route('director.active-documents.index', ['tab' => $tab, 'view_mode' => $viewMode]) }}" 
                               class="btn btn-ghost btn-xs sm:btn-sm text-base-content/60 hover:text-error hover:bg-error/10 font-medium gap-1 rounded-xl px-2 sm:px-2.5"
                               title="{{ __('Hapus semua filter aktif') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span>{{ __('Reset Filter') }}</span>
                            </a>
                        @endif

                        {{-- Apply Filter Button --}}
                        <button type="submit" form="director-filter-form" class="btn btn-primary btn-xs sm:btn-sm rounded-xl px-3 sm:px-3.5 gap-1.5 shadow-xs font-semibold text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>{{ __('Terapkan Filter') }}</span>
                        </button>

                        {{-- View Mode Toggle --}}
                        <div class="join border border-base-300 bg-base-200/50 p-0.5 rounded-xl shrink-0">
                            <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'grid']) }}" 
                               class="join-item btn btn-xs {{ $viewMode === 'grid' ? 'btn-primary shadow-xs text-white' : 'btn-ghost text-base-content/70' }}"
                               title="{{ __('Tampilan Grid') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span class="hidden sm:inline">{{ __('Grid') }}</span>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'list']) }}" 
                                class="join-item btn btn-xs {{ $viewMode === 'list' ? 'btn-primary shadow-xs text-white' : 'btn-ghost text-base-content/70' }}"
                                title="{{ __('Tampilan List') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <span class="hidden sm:inline">{{ __('List') }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Filter Form --}}
                <form id="director-filter-form" method="GET" action="{{ route('director.active-documents.index') }}" x-data="dateRangeHelper()">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="view_mode" value="{{ $viewMode }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-12 gap-2.5 items-end">
                        {{-- Search Input (4 cols on lg) --}}
                        <div class="sm:col-span-2 md:col-span-3 lg:col-span-4 relative">
                            <label class="text-[11px] font-semibold text-base-content/60 block mb-1">{{ __('Cari Judul / No. Dokumen') }}</label>
                            <div class="relative">
                                <input type="text" name="search" value="{{ $search }}" 
                                       placeholder="{{ __('Cari judul/no...') }}" 
                                       class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 shadow-xs focus:border-primary transition-all">
                                <svg class="w-4 h-4 absolute left-3 top-2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                @if($search)
                                    <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="absolute right-2.5 top-1.5 text-base-content/40 hover:text-base-content">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Smart Range Preset Dropdown (2 cols on lg) --}}
                        <div class="lg:col-span-2">
                            <label class="text-[11px] font-semibold text-base-content/60 block mb-1">{{ __('Periode Cepat') }}</label>
                            <select x-model="preset" @change="applyPreset()" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                                <option value="custom">{{ __('Kustom') }}</option>
                                <option value="today">{{ __('Hari Ini') }}</option>
                                <option value="yesterday">{{ __('Kemarin') }}</option>
                                <option value="7days">{{ __('7 Hari Terakhir') }}</option>
                                <option value="30days">{{ __('30 Hari Terakhir') }}</option>
                                <option value="this_month">{{ __('Bulan Ini') }}</option>
                            </select>
                        </div>

                        {{-- Start Date Input (3 cols on lg) --}}
                        <div class="lg:col-span-3">
                            <label class="text-[11px] font-semibold text-base-content/60 block mb-1">{{ __('Dari Tanggal') }}</label>
                            <input type="date" name="start_date" x-model="startDate" @change="preset = 'custom'"
                                   class="input input-bordered input-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                        </div>

                        {{-- Stop / End Date Input (3 cols on lg) --}}
                        <div class="lg:col-span-3">
                            <label class="text-[11px] font-semibold text-base-content/60 block mb-1">{{ __('Sampai Tanggal') }}</label>
                            <input type="date" name="end_date" x-model="endDate" @change="preset = 'custom'"
                                   class="input input-bordered input-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                        </div>

                        {{-- Company Filter (3 cols on lg) --}}
                        <div class="lg:col-span-3">
                            <label class="text-[11px] font-semibold text-base-content/60 block mb-1">{{ __('Perusahaan') }}</label>
                            <select name="company_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                                <option value="">{{ __('Semua Perusahaan') }}</option>
                                @foreach($availableCompanies as $company)
                                    <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }} ({{ $company->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Branch Filter (3 cols on lg) --}}
                        <div class="lg:col-span-3">
                            <label class="text-[11px] font-semibold text-base-content/60 block mb-1">{{ __('Cabang') }}</label>
                            <select name="branch_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                                <option value="">{{ __('Semua Cabang') }}</option>
                                @foreach($availableBranches as $branch)
                                    <option value="{{ $branch->id }}" {{ $selectedBranchId == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} {{ $branch->is_pusat ? '(' . __('Pusat') . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Unit Kerja Filter (3 cols on lg) --}}
                        <div class="lg:col-span-3">
                            <label class="text-[11px] font-semibold text-base-content/60 block mb-1">{{ __('Unit Kerja') }}</label>
                            <select name="unit_kerja_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                                <option value="">{{ __('Semua Unit Kerja') }}</option>
                                @foreach($availableUnitKerjas as $uk)
                                    <option value="{{ $uk->id }}" {{ $selectedUnitKerjaId == $uk->id ? 'selected' : '' }}>
                                        {{ $uk->kode_unit_kerja }} - {{ $uk->nama_unit_kerja }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Document Type Filter (3 cols on lg) --}}
                        <div class="lg:col-span-3">
                            <label class="text-[11px] font-semibold text-base-content/60 block mb-1">{{ __('Tipe Dokumen') }}</label>
                            <select name="document_type_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                                <option value="">{{ __('Semua Tipe') }}</option>
                                @foreach($availableDocumentTypes as $dt)
                                    <option value="{{ $dt->id }}" {{ $selectedDocTypeId == $dt->id ? 'selected' : '' }}>
                                        {{ $dt->name }} ({{ $dt->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            {{-- 3. Bulk Actions Toolbar (Alpine.js managed) --}}
            <div x-data="directorDocManager()" class="space-y-5">

                {{-- Floating / Top Bulk Toolbar --}}
                <div x-show="selectedIds.length > 0" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="bg-base-100 border border-primary/30 rounded-2xl p-3.5 shadow-lg flex flex-wrap items-center justify-between gap-3 bg-primary/[0.03]"
                     x-cloak>
                    <div class="flex items-center gap-2.5">
                        <span class="badge badge-primary font-bold text-white text-xs px-2.5 py-1" x-text="selectedIds.length + ' {{ __('dokumen dipilih') }}'"></span>
                        <span class="text-xs text-base-content/70">{{ __('Aksi massal tinjauan dokumen:') }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="bulkAcknowledge('seen')" class="btn btn-secondary btn-sm text-white font-semibold gap-1.5 shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            <span>{{ __('Tandai Sudah Ditinjau') }}</span>
                        </button>
                        <button type="button" @click="bulkAcknowledge('unseen')" class="btn btn-outline btn-ghost border-base-300 btn-sm text-base-content/70 hover:bg-base-200 font-semibold gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            <span>{{ __('Tandai Belum Ditinjau') }}</span>
                        </button>
                        <button type="button" @click="clearSelection()" class="btn btn-ghost btn-sm text-base-content/60">
                            {{ __('Batal') }}
                        </button>
                    </div>
                </div>

                {{-- Section Header Banner --}}
                @if($isSpecificDateFilter)
                    {{-- Specific Date / Range Filter Banner --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-primary/15 via-secondary/10 to-transparent border-l-4 border-primary rounded-r-2xl p-3.5 sm:p-5 shadow-xs">
                        <div class="flex items-start sm:items-center gap-3 min-w-0">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-xs mt-0.5 sm:mt-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                    <h3 class="font-bold text-sm sm:text-base text-base-content">{{ __('Dokumen Aktif: :date', ['date' => $selectedActiveDateFormatted]) }}</h3>
                                    <span class="badge badge-secondary badge-outline badge-xs sm:badge-sm font-semibold shrink-0 whitespace-nowrap">{{ __('Filter Tanggal') }}</span>
                                    <span class="badge badge-ghost badge-xs sm:badge-sm font-bold border-base-300 shrink-0 whitespace-nowrap">{{ __(':count dokumen', ['count' => $documents->total()]) }}</span>
                                </div>
                                <p class="text-[11px] sm:text-xs text-base-content/70 mt-0.5">
                                    {{ __('Menampilkan semua dokumen yang aktif pada rentang :date.', ['date' => $selectedActiveDateFormatted]) }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-2 pt-1 sm:pt-0">
                            <a href="{{ route('director.active-documents.index', array_filter(['tab' => $tab, 'view_mode' => $viewMode, 'search' => $search, 'company_id' => $selectedCompanyId, 'branch_id' => $selectedBranchId, 'unit_kerja_id' => $selectedUnitKerjaId, 'document_type_id' => $selectedDocTypeId])) }}" class="btn btn-outline btn-xs sm:btn-sm rounded-xl gap-1.5 hover:btn-primary text-xs font-semibold shadow-xs border-base-300 whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>{{ __('Hapus Filter Tanggal') }}</span>
                            </a>
                        </div>
                    </div>
                @else
                    {{-- Default Chronological Header Banner --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-base-200/40 border-l-4 border-primary rounded-r-2xl p-3.5 sm:p-5 shadow-xs">
                        <div class="flex items-start sm:items-center gap-3 min-w-0">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-xs mt-0.5 sm:mt-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                    <h3 class="font-bold text-sm sm:text-base text-base-content">{{ __('Daftar Dokumen Aktif') }}</h3>
                                    <span class="badge badge-primary badge-outline badge-xs sm:badge-sm font-semibold shrink-0 whitespace-nowrap">{{ __(':count dokumen', ['count' => $documents->total()]) }}</span>
                                    @if($documents->hasPages())
                                        <span class="badge badge-ghost badge-xs sm:badge-sm font-medium border-base-300 shrink-0 whitespace-nowrap">{{ __('Halaman :current dari :total', ['current' => $documents->currentPage(), 'total' => $documents->lastPage()]) }}</span>
                                    @endif
                                </div>
                                <p class="text-[11px] sm:text-xs text-base-content/70 mt-0.5">
                                    {{ __('Menampilkan semua dokumen aktif yang telah disetujui, diurutkan dari yang terbaru.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Document Results --}}
                @if($documents->isNotEmpty())
                    <div x-data="{
                        openDays: {
                            @foreach($groupedDocuments as $dateKey => $groupDocs)
                                '{{ $dateKey }}': {{ $loop->first || \Carbon\Carbon::parse($dateKey)->isToday() ? 'true' : 'false' }},
                            @endforeach
                        },
                        expandAll() {
                            for (let k in this.openDays) this.openDays[k] = true;
                        },
                        collapseAll() {
                            for (let k in this.openDays) this.openDays[k] = false;
                        }
                    }" class="space-y-3.5 sm:space-y-4">
                        
                        {{-- Collapse / Expand Controls Bar if multiple days --}}
                        @if($groupedDocuments->count() > 1)
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 sm:gap-2 px-1 text-xs text-base-content/60">
                                <span class="font-medium flex items-center gap-1.5 truncate">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-base-content/50 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="truncate">{{ __(':count kelompok tanggal aktif', ['count' => $groupedDocuments->count()]) }}</span>
                                </span>
                                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 self-start sm:self-auto">
                                    <button type="button" @click="expandAll()" class="btn btn-ghost btn-xs text-[11px] font-semibold text-primary hover:bg-primary/10 gap-1 rounded-lg px-2 whitespace-nowrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        <span>{{ __('Buka Semua') }}</span>
                                    </button>
                                    <span class="text-base-content/20">•</span>
                                    <button type="button" @click="collapseAll()" class="btn btn-ghost btn-xs text-[11px] font-semibold text-base-content/60 hover:text-base-content hover:bg-base-200 gap-1 rounded-lg px-2 whitespace-nowrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                                        <span>{{ __('Tutup Semua') }}</span>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Day Cards Loop --}}
                        @foreach($groupedDocuments as $dateKey => $groupDocs)
                            @php
                                $groupDate = \Carbon\Carbon::parse($dateKey);
                                $unseenInGroup = $groupDocs->whereNull('director_read_at')->count();
                                $totalInGroup = $groupDocs->count();
                            @endphp
                            <div class="bg-base-100 border border-base-300 rounded-2xl p-3.5 sm:p-5 shadow-xs transition-all space-y-3 sm:space-y-4">
                                {{-- Card Header: Click to collapse/expand --}}
                                <div @click="openDays['{{ $dateKey }}'] = !openDays['{{ $dateKey }}']" 
                                     class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer select-none group/day">
                                    <div class="flex items-start sm:items-center gap-2.5 sm:gap-3 min-w-0">
                                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl {{ $unseenInGroup > 0 ? 'bg-primary/10 text-primary' : 'bg-success/10 text-success' }} flex items-center justify-center font-bold text-xs shadow-xs group-hover/day:bg-primary group-hover/day:text-white transition-colors shrink-0 mt-0.5 sm:mt-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                                <h4 class="font-bold text-xs sm:text-sm text-base-content group-hover/day:text-primary transition-colors whitespace-nowrap">
                                                    {{ $groupDate->translatedFormat('d F Y') }}
                                                </h4>
                                                @if($groupDate->isToday())
                                                    <span class="badge badge-xs sm:badge-sm badge-secondary text-white font-bold shrink-0 whitespace-nowrap">{{ __('Hari Ini') }}</span>
                                                @elseif($groupDate->isYesterday())
                                                    <span class="badge badge-xs sm:badge-sm badge-ghost text-xs font-semibold shrink-0 whitespace-nowrap">{{ __('Kemarin') }}</span>
                                                @endif

                                                {{-- Review Status Badge for this day --}}
                                                @if($unseenInGroup > 0)
                                                    <span class="badge badge-warning badge-xs sm:badge-sm font-semibold gap-1 text-[10px] sm:text-[11px] border border-warning/30 shrink-0 whitespace-nowrap">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-warning animate-pulse"></span>
                                                        {{ __(':count Belum Ditinjau', ['count' => $unseenInGroup]) }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-success/15 text-success border border-success/30 badge-xs sm:badge-sm font-semibold gap-1 text-[10px] sm:text-[11px] shrink-0 whitespace-nowrap">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                        {{ __('Semua Ditinjau') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-[11px] text-base-content/60 mt-0.5 truncate">
                                                {{ __(':count dokumen diaktifkan pada tanggal ini', ['count' => $totalInGroup]) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="w-full sm:w-auto flex items-center justify-between sm:justify-end gap-2 pt-2.5 sm:pt-0 border-t sm:border-t-0 border-base-200/60 shrink-0">
                                        {{-- 1-Click Day Review Button if there are unseen docs in this group --}}
                                        @if($unseenInGroup > 0)
                                            <button type="button" 
                                                    @click.stop="acknowledgeGroup(@json($groupDocs->whereNull('director_read_at')->pluck('id')))" 
                                                    class="btn btn-ghost btn-xs text-[10px] sm:text-[11px] text-primary hover:bg-primary/10 hover:border-primary/30 border border-transparent font-semibold gap-1 rounded-lg px-2 sm:px-2.5 shrink-0 whitespace-nowrap"
                                                    title="{{ __('Tandai semua dokumen pada tanggal ini sebagai sudah ditinjau') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                <span>{{ __('Tandai 1 Hari Selesai') }}</span>
                                            </button>
                                        @endif

                                        <div class="flex items-center gap-2 shrink-0 ml-auto sm:ml-0">
                                            <span class="badge badge-xs sm:badge-sm badge-ghost font-bold border-base-300 shrink-0 whitespace-nowrap leading-normal py-1 px-2">{{ $totalInGroup }} {{ __('Dokumen') }}</span>
                                            <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-base-200/60 flex items-center justify-center text-base-content/60 group-hover/day:bg-base-200 transition-transform duration-200 shrink-0" 
                                                 :class="{ 'rotate-180': openDays['{{ $dateKey }}'] }">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card Body (Collapsible Content) --}}
                                <div x-show="openDays['{{ $dateKey }}']" x-transition class="pt-3 border-t border-base-200/70">
                                    @if($viewMode === 'grid')
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 items-stretch">
                                            @foreach($groupDocs as $doc)
                                                @include('director.active_documents._card', ['doc' => $doc])
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="bg-base-100 border border-base-300 rounded-2xl overflow-hidden shadow-xs">
                                            <div class="overflow-x-auto">
                                                <table class="table table-zebra w-full text-xs">
                                                    <thead>
                                                        <tr class="bg-base-200/60 text-base-content/70">
                                                            <th class="w-10">
                                                                <input type="checkbox" 
                                                                       @change="toggleSelectAll($event)" 
                                                                       class="checkbox checkbox-primary checkbox-xs rounded"
                                                                       aria-label="{{ __('Pilih semua') }}">
                                                            </th>
                                                            <th>{{ __('Judul Dokumen') }}</th>
                                                            <th>{{ __('No. Dokumen') }}</th>
                                                            <th>{{ __('Tgl. Aktif') }}</th>
                                                            <th>{{ __('Cabang & Perusahaan') }}</th>
                                                            <th>{{ __('Unit Kerja') }}</th>
                                                            <th>{{ __('Pembuat') }}</th>
                                                            <th>{{ __('Status Tinjauan Direktur') }}</th>
                                                            <th class="text-right">{{ __('Aksi') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($groupDocs as $doc)
                                                            @include('director.active_documents._row', ['doc' => $doc])
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($documents->hasPages())
                        <div class="pt-2">
                            {{ $documents->links() }}
                        </div>
                    @endif
                @else
                    {{-- Overall Empty State --}}
                    <div class="bg-base-100 border border-base-300 rounded-2xl p-12 text-center shadow-xs">
                        <div class="w-16 h-16 rounded-2xl bg-base-200/80 text-base-content/30 flex items-center justify-center mx-auto mb-3.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h4 class="font-bold text-base text-base-content">{{ __('Tidak Ada Dokumen Aktif Ditemukan') }}</h4>
                        <p class="text-xs text-base-content/60 mt-1 max-w-md mx-auto">
                            @if($isSpecificDateFilter)
                                {{ __('Tidak ada dokumen yang aktif pada rentang :date.', ['date' => $selectedActiveDateFormatted]) }}
                            @elseif($tab === 'unseen')
                                {{ __('Luar biasa! Semua dokumen aktif telah Anda tinjau.') }}
                            @elseif($tab === 'seen')
                                {{ __('Belum ada dokumen aktif yang ditandai sudah ditinjau.') }}
                            @else
                                {{ __('Tidak ada dokumen aktif yang sesuai dengan kriteria filter atau pencarian Anda.') }}
                            @endif
                        </p>
                        @if($hasFilters || $tab !== 'all')
                            <a href="{{ route('director.active-documents.index') }}" class="btn btn-primary text-white btn-sm mt-4">
                                {{ __('Lihat Semua Dokumen Aktif') }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Interactive Alpine.js & AJAX Script --}}
    @push('scripts')
    <script>
        function dateRangeHelper() {
            return {
                startDate: @json($startDate ?? ''),
                endDate: @json($endDate ?? ''),
                preset: 'custom',
                init() {
                    this.detectPreset();
                },
                detectPreset() {
                    const now = new Date();
                    const formatYMD = (d) => {
                        const year = d.getFullYear();
                        const month = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };

                    const today = formatYMD(now);
                    const yestDate = new Date();
                    yestDate.setDate(yestDate.getDate() - 1);
                    const yest = formatYMD(yestDate);

                    const d7Date = new Date();
                    d7Date.setDate(d7Date.getDate() - 6);
                    const d7Str = formatYMD(d7Date);

                    const d30Date = new Date();
                    d30Date.setDate(d30Date.getDate() - 29);
                    const d30Str = formatYMD(d30Date);

                    const firstDayOfMonth = formatYMD(new Date(now.getFullYear(), now.getMonth(), 1));

                    if (this.startDate === today && this.endDate === today) {
                        this.preset = 'today';
                    } else if (this.startDate === yest && this.endDate === yest) {
                        this.preset = 'yesterday';
                    } else if (this.startDate === d7Str && this.endDate === today) {
                        this.preset = '7days';
                    } else if (this.startDate === d30Str && this.endDate === today) {
                        this.preset = '30days';
                    } else if (this.startDate === firstDayOfMonth && this.endDate === today) {
                        this.preset = 'this_month';
                    } else {
                        this.preset = 'custom';
                    }
                },
                applyPreset() {
                    const now = new Date();
                    const formatYMD = (d) => {
                        const year = d.getFullYear();
                        const month = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };

                    const todayStr = formatYMD(now);

                    if (this.preset === 'today') {
                        this.startDate = todayStr;
                        this.endDate = todayStr;
                    } else if (this.preset === 'yesterday') {
                        const yest = new Date();
                        yest.setDate(yest.getDate() - 1);
                        const yestStr = formatYMD(yest);
                        this.startDate = yestStr;
                        this.endDate = yestStr;
                    } else if (this.preset === '7days') {
                        const d7 = new Date();
                        d7.setDate(d7.getDate() - 6);
                        this.startDate = formatYMD(d7);
                        this.endDate = todayStr;
                    } else if (this.preset === '30days') {
                        const d30 = new Date();
                        d30.setDate(d30.getDate() - 29);
                        this.startDate = formatYMD(d30);
                        this.endDate = todayStr;
                    } else if (this.preset === 'this_month') {
                        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                        this.startDate = formatYMD(firstDay);
                        this.endDate = todayStr;
                    }
                }
            };
        }

        function directorDocManager() {
            return {
                selectedIds: [],
                allDocIds: @json($documents->pluck('id')->all()),

                toggleSelectAll(event) {
                    if (event.target.checked) {
                        this.selectedIds = [...this.allDocIds];
                    } else {
                        this.selectedIds = [];
                    }
                },

                clearSelection() {
                    this.selectedIds = [];
                },

                async toggleSeen(docId, action) {
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch(`/director/documents/${docId}/acknowledge`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ action: action })
                        });

                        const data = await response.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Gagal mengubah status');
                        }
                    } catch (error) {
                        console.error('Error updating acknowledgment status:', error);
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/director/documents/${docId}/acknowledge`;
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        form.appendChild(csrfInput);
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = action;
                        form.appendChild(actionInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                },

                async acknowledgeGroup(ids) {
                    if (!ids || ids.length === 0) return;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch('{{ route("director.documents.bulk-acknowledge") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                document_ids: ids,
                                action: 'seen'
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Gagal menandai dokumen');
                        }
                    } catch (error) {
                        console.error('Error acknowledging group:', error);
                        window.location.reload();
                    }
                },

                async bulkAcknowledge(action) {
                    if (this.selectedIds.length === 0) return;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch('{{ route("director.documents.bulk-acknowledge") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                document_ids: this.selectedIds,
                                action: action
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Gagal melakukan aksi massal');
                        }
                    } catch (error) {
                        console.error('Error bulk acknowledging:', error);
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route("director.documents.bulk-acknowledge") }}';
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        form.appendChild(csrfInput);
                        this.selectedIds.forEach(id => {
                            const idInput = document.createElement('input');
                            idInput.type = 'hidden';
                            idInput.name = 'document_ids[]';
                            idInput.value = id;
                            form.appendChild(idInput);
                        });
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = action;
                        form.appendChild(actionInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
