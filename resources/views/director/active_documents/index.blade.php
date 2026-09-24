<x-app-layout>
    <x-slot name="header">{{ __('Tinjauan Dokumen Aktif') }}</x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- 1. Metric Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Total Active Docs --}}
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}" 
                   class="bg-base-100 border {{ $tab === 'all' ? 'border-primary ring-2 ring-primary/25 bg-primary/[0.03] shadow-xs' : 'border-base-300 hover:border-primary/40' }} rounded-2xl p-5 shadow-xs flex flex-col justify-between transition-all duration-200 hover:shadow-md group">
                    {{-- Top Row: Label & Icon --}}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-base-content/60 group-hover:text-primary transition-colors">
                            {{ __('Total Dokumen Aktif') }}
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-primary group-hover:text-white transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Bottom Row: Value & Status Pill --}}
                    <div class="mt-4 flex items-end justify-between gap-2 pt-2 border-t border-base-200/60">
                        <div>
                            <span class="text-3xl font-black tracking-tight text-base-content leading-none block">
                                {{ number_format($totalActiveCount) }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-200 text-base-content/70 border border-base-300 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"></span>
                            {{ __('Semua Cabang') }}
                        </span>
                    </div>
                </a>

                {{-- Unseen by Director (Belum Ditinjau) --}}
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'unseen', 'page' => 1]) }}" 
                   class="bg-base-100 border {{ $tab === 'unseen' ? 'border-primary ring-2 ring-primary/25 bg-primary/[0.03] shadow-xs' : 'border-base-300 hover:border-primary/40' }} rounded-2xl p-5 shadow-xs flex flex-col justify-between transition-all duration-200 hover:shadow-md group">
                    {{-- Top Row: Label & Icon --}}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-base-content/60 group-hover:text-primary transition-colors">
                            {{ __('Belum Ditinjau') }}
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-primary group-hover:text-white transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Bottom Row: Value & Status Pill --}}
                    <div class="mt-4 flex items-end justify-between gap-2 pt-2 border-t border-base-200/60">
                        <div>
                            <span class="text-3xl font-black tracking-tight text-base-content leading-none block">
                                {{ number_format($unseenActiveCount) }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-200 text-base-content/70 border border-base-300 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse shrink-0"></span>
                            {{ __('Perlu Ditinjau') }}
                        </span>
                    </div>
                </a>

                {{-- Seen by Director (Sudah Ditinjau) --}}
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'seen', 'page' => 1]) }}" 
                   class="bg-base-100 border {{ $tab === 'seen' ? 'border-secondary ring-2 ring-secondary/25 bg-secondary/[0.03] shadow-xs' : 'border-base-300 hover:border-secondary/40' }} rounded-2xl p-5 shadow-xs flex flex-col justify-between transition-all duration-200 hover:shadow-md group">
                    {{-- Top Row: Label & Icon --}}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-base-content/60 group-hover:text-secondary transition-colors">
                            {{ __('Sudah Ditinjau') }}
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-secondary group-hover:text-white transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Bottom Row: Value & Status Pill --}}
                    <div class="mt-4 flex items-end justify-between gap-2 pt-2 border-t border-base-200/60">
                        <div>
                            <span class="text-3xl font-black tracking-tight text-base-content leading-none block">
                                {{ number_format($seenActiveCount) }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-200 text-base-content/70 border border-base-300 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Terkonfirmasi') }}
                        </span>
                    </div>
                </a>
            </div>

            {{-- 2. Filter Bar and Tabs Container --}}
            <div class="bg-base-100 border border-base-300 rounded-2xl shadow-xs p-4 sm:p-5 space-y-4">
                
                {{-- Tabs & View Mode / Action Row --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-base-200 pb-4">
                    {{-- Status Tabs --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}" 
                           class="btn btn-sm rounded-xl gap-2 font-semibold transition-all {{ $tab === 'all' ? 'btn-primary text-white shadow-xs' : 'btn-ghost text-base-content/70 hover:bg-base-200' }}">
                            <span>{{ __('Semua Dokumen Aktif') }}</span>
                            <span class="badge {{ $tab === 'all' ? 'badge-primary-content text-primary' : 'badge-ghost' }} badge-sm font-bold">{{ $totalActiveCount }}</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'unseen', 'page' => 1]) }}" 
                           class="btn btn-sm rounded-xl gap-2 font-semibold transition-all {{ $tab === 'unseen' ? 'btn-primary text-white shadow-xs' : 'btn-ghost text-base-content/70 hover:bg-base-200' }}">
                            <span class="w-2 h-2 rounded-full {{ $tab === 'unseen' ? 'bg-white' : 'bg-primary animate-pulse' }}"></span>
                            <span>{{ __('Belum Ditinjau') }}</span>
                            <span class="badge {{ $tab === 'unseen' ? 'badge-primary-content text-primary' : 'badge-ghost' }} badge-sm font-bold">{{ $unseenActiveCount }}</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'seen', 'page' => 1]) }}" 
                           class="btn btn-sm rounded-xl gap-2 font-semibold transition-all {{ $tab === 'seen' ? 'btn-secondary text-white shadow-xs' : 'btn-ghost text-base-content/70 hover:bg-base-200' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            <span>{{ __('Sudah Ditinjau') }}</span>
                            <span class="badge {{ $tab === 'seen' ? 'badge-secondary-content text-secondary' : 'badge-ghost' }} badge-sm font-bold">{{ $seenActiveCount }}</span>
                        </a>
                    </div>

                    {{-- Actions: Apply Filter Button & View Mode Toggle --}}
                    <div class="flex items-center gap-2.5 shrink-0 self-start sm:self-center">
                        @php
                            $hasFilters = filled($search) || filled($selectedCompanyId) || filled($selectedBranchId) || filled($selectedUnitKerjaId) || filled($selectedDocTypeId) || filled($selectedOwnerId) || filled($selectedFormatChoice) || filled($selectedActiveDate);
                        @endphp
                        @if($hasFilters)
                            <a href="{{ route('director.active-documents.index', ['tab' => $tab, 'view_mode' => $viewMode]) }}" 
                               class="btn btn-ghost btn-sm text-base-content/60 hover:text-error hover:bg-error/10 font-medium gap-1 rounded-xl px-2.5"
                               title="{{ __('Hapus semua filter aktif') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span>{{ __('Reset Filter') }}</span>
                            </a>
                        @endif

                        {{-- Apply Filter Button --}}
                        <button type="submit" form="director-filter-form" class="btn btn-primary btn-sm rounded-xl px-3.5 gap-1.5 shadow-xs font-semibold text-white">
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
                                <span>{{ __('Grid') }}</span>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'list']) }}" 
                                class="join-item btn btn-xs {{ $viewMode === 'list' ? 'btn-primary shadow-xs text-white' : 'btn-ghost text-base-content/70' }}"
                                title="{{ __('Tampilan List') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <span>{{ __('List') }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Filter Form --}}
                <form id="director-filter-form" method="GET" action="{{ route('director.active-documents.index') }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="view_mode" value="{{ $viewMode }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-12 gap-2.5">
                        {{-- Search Input (2 cols on lg) --}}
                        <div class="sm:col-span-2 md:col-span-3 lg:col-span-2 relative">
                            <input type="text" name="search" value="{{ $search }}" 
                                   placeholder="{{ __('Cari judul/no...') }}" 
                                   class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 shadow-xs focus:border-primary transition-all">
                            <svg class="w-4 h-4 absolute left-3 top-2.5 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            @if($search)
                                <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="absolute right-2.5 top-2 text-base-content/40 hover:text-base-content">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </a>
                            @endif
                        </div>

                        {{-- Tanggal Aktif Filter (2 cols on lg) --}}
                        <div class="lg:col-span-2">
                            <select name="active_date" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                                <option value="">{{ __('Semua Tanggal') }}</option>
                                <option value="{{ now()->format('Y-m-d') }}" {{ $selectedActiveDate === now()->format('Y-m-d') ? 'selected' : '' }}>
                                    {{ __('Hari Ini') }} — {{ now()->translatedFormat('d M Y') }}
                                </option>
                                @foreach($availableActiveDates as $dateVal)
                                    @if($dateVal !== now()->format('Y-m-d'))
                                        @php
                                            $dateObj = \Carbon\Carbon::parse($dateVal);
                                        @endphp
                                        <option value="{{ $dateVal }}" {{ $selectedActiveDate === $dateVal ? 'selected' : '' }}>
                                            {{ $dateObj->translatedFormat('d F Y') }}
                                        </option>
                                    @endif
                                @endforeach
                                @if(!empty($selectedActiveDate) && $selectedActiveDate !== now()->format('Y-m-d') && !$availableActiveDates->contains($selectedActiveDate))
                                    <option value="{{ $selectedActiveDate }}" selected>
                                        {{ \Carbon\Carbon::parse($selectedActiveDate)->translatedFormat('d F Y') }}
                                    </option>
                                @endif
                            </select>
                        </div>

                        {{-- Company Filter (2 cols on lg) --}}
                        <div class="lg:col-span-2">
                            <select name="company_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                                <option value="">{{ __('Semua Perusahaan') }}</option>
                                @foreach($availableCompanies as $company)
                                    <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }} ({{ $company->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Branch Filter (2 cols on lg) --}}
                        <div class="lg:col-span-2">
                            <select name="branch_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                                <option value="">{{ __('Semua Cabang') }}</option>
                                @foreach($availableBranches as $branch)
                                    <option value="{{ $branch->id }}" {{ $selectedBranchId == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} {{ $branch->is_pusat ? '(' . __('Pusat') . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Unit Kerja Filter (2 cols on lg) --}}
                        <div class="lg:col-span-2">
                            <select name="unit_kerja_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-xs focus:border-primary">
                                <option value="">{{ __('Semua Unit Kerja') }}</option>
                                @foreach($availableUnitKerjas as $uk)
                                    <option value="{{ $uk->id }}" {{ $selectedUnitKerjaId == $uk->id ? 'selected' : '' }}>
                                        {{ $uk->kode_unit_kerja }} - {{ $uk->nama_unit_kerja }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Document Type Filter (2 cols on lg) --}}
                        <div class="lg:col-span-2">
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
                @if($isSpecificDateFilter && !$isTodayPage)
                    {{-- Specific Historical Date View --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-primary/15 via-secondary/10 to-transparent border-l-4 border-primary rounded-r-2xl p-4 sm:p-5 shadow-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-bold text-base text-base-content">{{ __('Dokumen Aktif: :date', ['date' => $selectedActiveDateFormatted]) }}</h3>
                                    <span class="badge badge-secondary badge-outline badge-sm font-semibold">{{ __('Filter Tanggal') }}</span>
                                    <span class="badge badge-ghost badge-sm font-bold border-base-300">{{ __(':count dokumen', ['count' => $documents->total()]) }}</span>
                                </div>
                                <p class="text-xs text-base-content/70 mt-0.5">
                                    {{ __('Menampilkan semua dokumen yang aktif pada tanggal :date.', ['date' => $selectedActiveDateFormatted]) }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-2">
                            <a href="{{ route('director.active-documents.index', array_filter(['tab' => $tab, 'view_mode' => $viewMode])) }}" class="btn btn-outline btn-sm rounded-xl gap-1.5 hover:btn-primary text-xs font-semibold shadow-xs border-base-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>{{ __('Kembali ke Dokumen Hari Ini') }}</span>
                            </a>
                        </div>
                    </div>
                @elseif($isTodayPage)
                    {{-- Today's Documents View --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-primary/15 via-secondary/10 to-transparent border-l-4 border-primary rounded-r-2xl p-4 sm:p-5 shadow-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-bold text-base text-base-content">{{ __('Dokumen Aktif Hari Ini') }}</h3>
                                    <span class="badge badge-secondary badge-sm font-bold text-white">{{ __('Prioritas Utama') }}</span>
                                    <span class="badge badge-outline badge-sm text-xs font-semibold text-base-content/70">{{ now()->translatedFormat('l, d F Y') }}</span>
                                </div>
                                <p class="text-xs text-base-content/70 mt-0.5">
                                    {{ __('Dokumen yang baru disetujui & diaktifkan hari ini dan memerlukan peninjauan Direktur.') }}
                                </p>
                            </div>
                        </div>

                        @if($previousCount > 0)
                            <div class="shrink-0">
                                <a href="{{ request()->fullUrlWithQuery(['page' => 2, 'active_date' => null]) }}" class="btn btn-outline btn-sm rounded-xl gap-1.5 hover:btn-primary text-xs font-semibold shadow-xs border-base-300">
                                    <span>{{ __('Lihat Dokumen Sebelumnya') }}</span>
                                    <span class="badge badge-sm badge-ghost font-bold border-base-300">({{ $previousCount }})</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Historical Previous Documents View (Grouped by Date) --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-base-200/40 border-l-4 border-secondary/50 rounded-r-2xl p-4 sm:p-5 shadow-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center shrink-0 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-bold text-base text-base-content">{{ __('Dokumen Aktif Sebelumnya') }}</h3>
                                    <span class="badge badge-ghost badge-sm font-semibold">{{ __('Riwayat Aktif') }}</span>
                                    <span class="badge badge-ghost badge-sm font-bold border-base-300">{{ __('Halaman :current dari :total', ['current' => $documents->currentPage(), 'total' => $documents->lastPage()]) }}</span>
                                </div>
                                <p class="text-xs text-base-content/70 mt-0.5">
                                    {{ __('Dokumen yang telah aktif sebelum hari ini, dikelompokkan berdasarkan tanggal aktif.') }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <a href="{{ request()->fullUrlWithQuery(['page' => 1, 'active_date' => null]) }}" class="btn btn-outline btn-sm rounded-xl gap-1.5 hover:btn-primary text-xs font-semibold shadow-xs border-base-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>{{ __('Kembali ke Dokumen Hari Ini') }}</span>
                                @if($todayCount > 0)
                                    <span class="badge badge-sm badge-secondary text-white font-bold">({{ $todayCount }})</span>
                                @endif
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Document Results --}}
                @if($documents->isNotEmpty())
                    @if($isTodayPage || $isSpecificDateFilter)
                        {{-- Single Date / Today Document Listing --}}
                        @if($viewMode === 'grid')
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 items-stretch">
                                @foreach($documents as $doc)
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
                                            @foreach($documents as $doc)
                                                @include('director.active_documents._row', ['doc' => $doc])
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @else
                        {{-- Historical Multi-Date Grouped Document Listing --}}
                        <div class="space-y-8">
                            @foreach($groupedDocuments as $dateKey => $groupDocs)
                                @php
                                    $groupDate = \Carbon\Carbon::parse($dateKey);
                                @endphp
                                <div class="space-y-3.5">
                                    {{-- Group Date Subheading --}}
                                    <div class="flex items-center justify-between gap-3 border-b border-base-200 pb-2.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shadow-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-sm text-base-content flex items-center gap-2">
                                                    <span>{{ $groupDate->translatedFormat('d F Y') }}</span>
                                                    @if($groupDate->isYesterday())
                                                        <span class="badge badge-sm badge-ghost text-xs font-semibold">{{ __('Kemarin') }}</span>
                                                    @endif
                                                </h4>
                                                <p class="text-[11px] text-base-content/60">
                                                    {{ __(':count dokumen diaktifkan pada tanggal ini', ['count' => $groupDocs->count()]) }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="badge badge-sm badge-ghost font-bold border-base-300">{{ $groupDocs->count() }} {{ __('Dokumen') }}</span>
                                    </div>

                                    @if($viewMode === 'grid')
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 items-stretch mb-6">
                                            @foreach($groupDocs as $doc)
                                                @include('director.active_documents._card', ['doc' => $doc])
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="bg-base-100 border border-base-300 rounded-2xl overflow-hidden shadow-xs mb-6">
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
                            @endforeach
                        </div>
                    @endif

                    {{-- Pagination --}}
                    @if($documents->hasPages())
                        <div class="pt-2">
                            {{ $documents->links() }}
                        </div>
                    @endif
                @else
                    @if($isTodayPage && $previousCount > 0 && !$isSpecificDateFilter)
                        {{-- Today is empty, but previous active documents exist --}}
                        <div class="bg-base-100 border border-base-300 rounded-2xl p-8 sm:p-12 text-center space-y-4 shadow-xs">
                            <div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mx-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="max-w-md mx-auto space-y-1.5">
                                <h4 class="font-bold text-lg text-base-content">{{ __('Tidak Ada Dokumen Aktif Hari Ini') }}</h4>
                                <p class="text-xs text-base-content/60 leading-relaxed">
                                    {{ __('Belum ada dokumen baru yang diaktifkan pada tanggal :date. Semua dokumen aktif sebelumnya dapat diakses di halaman berikutnya.', ['date' => now()->translatedFormat('d F Y')]) }}
                                </p>
                            </div>
                            <div class="pt-2">
                                <a href="{{ request()->fullUrlWithQuery(['page' => 2, 'active_date' => null]) }}" class="btn btn-primary btn-sm rounded-xl px-5 gap-2 font-semibold shadow-xs text-white">
                                    <span>{{ __('Buka Dokumen Aktif Sebelumnya') }}</span>
                                    <span class="badge badge-primary-content text-primary badge-sm font-bold">{{ $previousCount }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        {{-- Pagination on empty page 1 so user can easily navigate to page 2 --}}
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
                                    {{ __('Tidak ada dokumen yang aktif pada tanggal :date.', ['date' => $selectedActiveDateFormatted]) }}
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
                @endif
            </div>

        </div>
    </div>

    {{-- Interactive Alpine.js & AJAX Script --}}
    @push('scripts')
    <script>
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
