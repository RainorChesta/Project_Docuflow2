<x-app-layout>
    <x-slot name="header">{{ __('Semua Dokumen') }}</x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- Page Title & Subtitle --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        {{ __('Daftar Semua Dokumen') }}
                    </h2>
                    <p class="text-sm text-base-content/60 mt-1">
                        {{ __('Kelola seluruh dokumen organisasi secara langsung tanpa masuk folder bertingkat, dengan dukungan unduh arsip ZIP dan penghapusan massal.') }}
                    </p>
                </div>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success shadow-xs border border-success/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-sm">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error shadow-xs border border-error/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-sm">{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error shadow-xs border border-error/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm">
                        <div class="font-bold">{{ __('Terjadi kesalahan validasi:') }}</div>
                        <ul class="list-disc list-inside mt-1 text-xs">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Stats Bar --}}
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-5 gap-3">
                {{-- Total Dokumen --}}
                <div class="bg-base-100 border border-base-300 rounded-2xl p-4 shadow-xs flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-base-content/50 uppercase tracking-wider">{{ __('Total Dokumen') }}</div>
                        <div class="text-xl font-black text-base-content">{{ number_format($stats['total'] ?? 0) }}</div>
                    </div>
                </div>

                {{-- Dokumen Aktif --}}
                <div class="bg-base-100 border border-base-300 rounded-2xl p-4 shadow-xs flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-success/10 text-success flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-base-content/50 uppercase tracking-wider">{{ __('Aktif') }}</div>
                        <div class="text-xl font-black text-success">{{ number_format($stats['active'] ?? 0) }}</div>
                    </div>
                </div>

                {{-- Pending --}}
                <div class="bg-base-100 border border-base-300 rounded-2xl p-4 shadow-xs flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-warning/10 text-warning flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-base-content/50 uppercase tracking-wider">{{ __('Pending') }}</div>
                        <div class="text-xl font-black text-warning">{{ number_format($stats['pending'] ?? 0) }}</div>
                    </div>
                </div>

                {{-- Draf --}}
                <div class="bg-base-100 border border-base-300 rounded-2xl p-4 shadow-xs flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-base-200 text-base-content/70 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-base-content/50 uppercase tracking-wider">{{ __('Draf') }}</div>
                        <div class="text-xl font-black text-base-content/80">{{ number_format($stats['draft'] ?? 0) }}</div>
                    </div>
                </div>

                {{-- Kedaluwarsa --}}
                <div class="bg-base-100 border border-base-300 rounded-2xl p-4 shadow-xs flex items-center gap-3.5 col-span-2 sm:col-span-1 md:col-span-1">
                    <div class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-base-content/50 uppercase tracking-wider">{{ __('Kedaluwarsa') }}</div>
                        <div class="text-xl font-black text-error">{{ number_format($stats['expired'] ?? 0) }}</div>
                    </div>
                </div>
            </div>

            {{-- Floating/Sticky Bulk Actions Bar --}}
            <div id="floating-bulk-bar"
                 class="hidden sticky top-4 z-40 bg-neutral text-neutral-content p-3 sm:p-4 rounded-2xl shadow-2xl border border-neutral/30 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div id="selected-count-badge" class="w-8 h-8 rounded-xl bg-primary text-primary-content flex items-center justify-center font-bold text-sm shadow-xs">0</div>
                    <span class="font-bold text-sm tracking-tight text-white">{{ __('Dokumen Terpilih') }}</span>
                    <button type="button" onclick="clearAllCheckboxes()" class="btn btn-ghost btn-xs text-neutral-content/70 hover:text-white hover:bg-white/10 rounded-lg">
                        {{ __('Batalkan Pilihan') }}
                    </button>
                </div>

                <div class="flex items-center flex-wrap gap-2 w-full sm:w-auto justify-end">
                    {{-- Bulk Download Button --}}
                    <button type="button"
                            onclick="submitBulkDownload()"
                            class="btn btn-primary btn-sm gap-1.5 shadow-sm font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>{{ __('Unduh Terpilih (.ZIP)') }}</span>
                    </button>

                    {{-- Bulk Move to Trash Button --}}
                    <button type="button"
                            onclick="openBulkTrashModal()"
                            class="btn btn-warning btn-sm gap-1.5 shadow-sm text-neutral font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>{{ __('Ke Sampah') }}</span>
                    </button>

                    {{-- Bulk Force Delete Button --}}
                    <button type="button"
                            onclick="openBulkForceDeleteModal()"
                            class="btn btn-error btn-sm text-white gap-1.5 shadow-sm font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>{{ __('Hapus Permanen') }}</span>
                    </button>
                </div>
            </div>

            {{-- Hidden Download Form --}}
            <form id="bulk_download_form" method="POST" action="{{ route('admin.documents.bulk-download') }}" class="hidden">
                @csrf
                <div id="bulk_download_inputs"></div>
            </form>

            {{-- Filter & Search Card --}}
            <div class="bg-base-100 border border-base-300 rounded-2xl shadow-xs p-4 sm:p-5 space-y-4">
                <form method="GET" action="{{ route('admin.documents.index') }}" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                        
                        {{-- Search Input (Title, Number, Creator) --}}
                        <div class="col-span-1 sm:col-span-2 relative">
                            <label class="label py-1">
                                <span class="label-text text-xs font-bold text-base-content/70">{{ __('Pencarian Dokumen') }}</span>
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       name="search" 
                                       value="{{ $search }}" 
                                       placeholder="{{ __('Cari judul, nomor dokumen, nama pembuat...') }}" 
                                       class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 shadow-2xs focus:border-primary transition-all">
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
                        </div>

                        {{-- Company Filter --}}
                        <div>
                            <label class="label py-1">
                                <span class="label-text text-xs font-bold text-base-content/70">{{ __('Perusahaan') }}</span>
                            </label>
                            <select name="company_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-2xs">
                                <option value="">{{ __('Semua Perusahaan') }}</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ ($selectedCompanyId == $company->id) ? 'selected' : '' }}>
                                        {{ $company->name }} {{ $company->code ? '('.$company->code.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Branch Filter --}}
                        <div>
                            <label class="label py-1">
                                <span class="label-text text-xs font-bold text-base-content/70">{{ __('Cabang') }}</span>
                            </label>
                            <select name="branch_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-2xs">
                                <option value="">{{ __('Semua Cabang') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ ($selectedBranchId == $branch->id) ? 'selected' : '' }}>
                                        {{ $branch->name }} {{ $branch->code ? '('.$branch->code.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Unit Kerja Filter --}}
                        <div>
                            <label class="label py-1">
                                <span class="label-text text-xs font-bold text-base-content/70">{{ __('Unit Kerja') }}</span>
                            </label>
                            <select name="unit_kerja_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-2xs">
                                <option value="">{{ __('Semua Unit Kerja') }}</option>
                                @foreach($unitKerjas as $uk)
                                    <option value="{{ $uk->id }}" {{ ($selectedUnitKerjaId == $uk->id) ? 'selected' : '' }}>
                                        {{ $uk->name }} {{ $uk->code ? '('.$uk->code.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Document Type Filter --}}
                        <div>
                            <label class="label py-1">
                                <span class="label-text text-xs font-bold text-base-content/70">{{ __('Jenis Dokumen') }}</span>
                            </label>
                            <select name="document_type_id" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-2xs">
                                <option value="">{{ __('Semua Jenis') }}</option>
                                @foreach($documentTypes as $dt)
                                    <option value="{{ $dt->id }}" {{ ($selectedDocTypeId == $dt->id) ? 'selected' : '' }}>
                                        {{ $dt->code ? '[' . $dt->code . '] ' : '' }}{{ $dt->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status Filter --}}
                        <div>
                            <label class="label py-1">
                                <span class="label-text text-xs font-bold text-base-content/70">{{ __('Status Dokumen') }}</span>
                            </label>
                            <select name="status" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-2xs">
                                <option value="">{{ __('Semua Status') }}</option>
                                <option value="active" {{ ($selectedStatus === 'active') ? 'selected' : '' }}>{{ __('Aktif') }}</option>
                                <option value="pending" {{ ($selectedStatus === 'pending') ? 'selected' : '' }}>{{ __('Menunggu Review') }}</option>
                                <option value="draft" {{ ($selectedStatus === 'draft') ? 'selected' : '' }}>{{ __('Draf') }}</option>
                                <option value="rejected" {{ ($selectedStatus === 'rejected') ? 'selected' : '' }}>{{ __('Ditolak') }}</option>
                                <option value="expired" {{ ($selectedStatus === 'expired') ? 'selected' : '' }}>{{ __('Kedaluwarsa') }}</option>
                            </select>
                        </div>

                        {{-- Format Choice Filter --}}
                        <div>
                            <label class="label py-1">
                                <span class="label-text text-xs font-bold text-base-content/70">{{ __('Format Dokumen') }}</span>
                            </label>
                            <select name="format_choice" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-2xs">
                                <option value="">{{ __('Semua Format') }}</option>
                                <option value="baru" {{ ($selectedFormatChoice === 'baru') ? 'selected' : '' }}>{{ __('Format Baru') }}</option>
                                <option value="lama" {{ ($selectedFormatChoice === 'lama') ? 'selected' : '' }}>{{ __('Format Lama') }}</option>
                            </select>
                        </div>

                        {{-- Per Page --}}
                        <div>
                            <label class="label py-1">
                                <span class="label-text text-xs font-bold text-base-content/70">{{ __('Tampilkan') }}</span>
                            </label>
                            <select name="per_page" class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-2xs">
                                <option value="10" {{ ($perPage == 10) ? 'selected' : '' }}>10 / hal</option>
                                <option value="20" {{ ($perPage == 20) ? 'selected' : '' }}>20 / hal</option>
                                <option value="50" {{ ($perPage == 50) ? 'selected' : '' }}>50 / hal</option>
                                <option value="100" {{ ($perPage == 100) ? 'selected' : '' }}>100 / hal</option>
                            </select>
                        </div>

                        {{-- Submit and Reset Button --}}
                        <div class="flex items-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-1 font-semibold gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>{{ __('Filter') }}</span>
                            </button>
                            <a href="{{ route('admin.documents.index') }}" class="btn btn-ghost btn-sm text-base-content/60 hover:text-error" title="{{ __('Reset Filter') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </form>

                {{-- Active Filter Badges --}}
                @php
                    $hasActiveFilters = $search || $selectedCompanyId || $selectedBranchId || $selectedUnitKerjaId || $selectedDocTypeId || $selectedStatus || $selectedFormatChoice;
                @endphp
                @if($hasActiveFilters)
                    <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-base-200 text-xs">
                        <span class="text-base-content/50 font-medium">{{ __('Filter Aktif:') }}</span>
                        
                        @if($search)
                            <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                {{ __('Cari: ') }} "{{ $search }}"
                                <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="hover:text-error">✕</a>
                            </span>
                        @endif

                        @if($selectedCompanyId)
                            @php $comp = $companies->firstWhere('id', $selectedCompanyId); @endphp
                            @if($comp)
                                <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                    {{ __('Perusahaan: ') }} {{ $comp->name }}
                                    <a href="{{ request()->fullUrlWithQuery(['company_id' => null]) }}" class="hover:text-error">✕</a>
                                </span>
                            @endif
                        @endif

                        @if($selectedBranchId)
                            @php $br = $branches->firstWhere('id', $selectedBranchId); @endphp
                            @if($br)
                                <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                    {{ __('Cabang: ') }} {{ $br->name }}
                                    <a href="{{ request()->fullUrlWithQuery(['branch_id' => null]) }}" class="hover:text-error">✕</a>
                                </span>
                            @endif
                        @endif

                        @if($selectedUnitKerjaId)
                            @php $uk = $unitKerjas->firstWhere('id', $selectedUnitKerjaId); @endphp
                            @if($uk)
                                <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                    {{ __('Unit Kerja: ') }} {{ $uk->name }}
                                    <a href="{{ request()->fullUrlWithQuery(['unit_kerja_id' => null]) }}" class="hover:text-error">✕</a>
                                </span>
                            @endif
                        @endif

                        @if($selectedDocTypeId)
                            @php $dt = $documentTypes->firstWhere('id', $selectedDocTypeId); @endphp
                            @if($dt)
                                <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                    {{ __('Jenis: ') }} {{ $dt->code ? '[' . $dt->code . '] ' : '' }}{{ $dt->name }}
                                    <a href="{{ request()->fullUrlWithQuery(['document_type_id' => null]) }}" class="hover:text-error">✕</a>
                                </span>
                            @endif
                        @endif

                        @if($selectedStatus)
                            @php
                                $statusLabels = [
                                    'active' => __('Aktif'),
                                    'pending' => __('Menunggu Review'),
                                    'draft' => __('Draf'),
                                    'rejected' => __('Ditolak'),
                                    'expired' => __('Kedaluwarsa'),
                                ];
                            @endphp
                            <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                {{ __('Status: ') }} {{ $statusLabels[$selectedStatus] ?? ucfirst($selectedStatus) }}
                                <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="hover:text-error">✕</a>
                            </span>
                        @endif

                        @if($selectedFormatChoice)
                            <span class="badge badge-sm badge-outline gap-1 bg-base-200/50">
                                {{ __('Format: ') }} {{ $selectedFormatChoice === 'baru' ? __('Format Baru') : __('Format Lama') }}
                                <a href="{{ request()->fullUrlWithQuery(['format_choice' => null]) }}" class="hover:text-error">✕</a>
                            </span>
                        @endif

                        <a href="{{ route('admin.documents.index') }}" class="btn btn-ghost btn-xs text-error hover:bg-error/10 ml-auto font-medium">
                            {{ __('Bersihkan Semua Filter') }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- Documents Table Card --}}
            <div class="bg-base-100 border border-base-300 rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="table w-full text-xs">
                        <thead>
                            <tr class="bg-base-200/60 text-base-content/70 border-b border-base-300">
                                {{-- Master Checkbox --}}
                                <th class="w-10 text-center py-3.5">
                                    <input type="checkbox"
                                           id="master-checkbox"
                                           class="checkbox checkbox-sm checkbox-primary rounded-md"
                                           onchange="toggleAllCheckboxes(this)"
                                           title="{{ __('Pilih Semua di Halaman Ini') }}">
                                </th>
                                <th class="w-12 text-center py-3.5">#</th>
                                <th class="py-3.5">{{ __('Dokumen') }}</th>
                                <th class="py-3.5">{{ __('Perusahaan & Cabang') }}</th>
                                <th class="py-3.5">{{ __('Unit Kerja') }}</th>
                                <th class="py-3.5">{{ __('Jenis Dokumen') }}</th>
                                <th class="py-3.5">{{ __('Pembuat & Tanggal') }}</th>
                                <th class="py-3.5">{{ __('Status') }}</th>
                                <th class="text-right py-3.5">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200/60">
                            @forelse($documents as $doc)
                                @php
                                    $displayVer = $doc->displayVersion();
                                    $hasDraft = $doc->versions->contains('status', 'draft');
                                    $hasPending = $doc->versions->contains('status', 'pending');
                                    $hasRejected = $doc->versions->contains('status', 'rejected');
                                    $isActive = $displayVer && $displayVer->status === 'active' && !$doc->is_expired;
                                @endphp
                                <tr class="hover:bg-base-200/40 transition-colors" id="doc-row-{{ $doc->id }}">
                                    {{-- Row Checkbox --}}
                                    <td class="text-center py-3">
                                        <input type="checkbox"
                                               class="doc-checkbox checkbox checkbox-sm checkbox-primary rounded-md"
                                               value="{{ $doc->id }}"
                                               onchange="updateSelectedCount()">
                                    </td>

                                    {{-- Index --}}
                                    <td class="text-center text-base-content/40 font-mono py-3">
                                        {{ $documents->firstItem() + $loop->index }}
                                    </td>

                                    {{-- Document Info --}}
                                    <td class="py-3">
                                        <div class="flex items-start gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="space-y-1 min-w-0">
                                                <a href="{{ route('documents.show', $doc) }}" 
                                                   class="font-bold text-sm text-base-content hover:text-primary transition-colors line-clamp-1 break-words" 
                                                   title="{{ $doc->title }}">
                                                    {{ $doc->title }}
                                                </a>
                                                <div class="flex items-center flex-wrap gap-1.5 text-[11px] text-base-content/60">
                                                    @if($doc->document_number)
                                                        <span class="font-mono bg-base-200/80 px-1.5 py-0.5 rounded border border-base-300 font-medium">
                                                            {{ $doc->document_number }}
                                                        </span>
                                                    @endif

                                                    @if($displayVer)
                                                        <span class="badge badge-ghost badge-xs font-mono font-bold">
                                                            v{{ $displayVer->version_number }}
                                                        </span>
                                                    @endif

                                                    @if($doc->format_choice === 'baru')
                                                        <span class="badge badge-xs bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-0 font-semibold">{{ __('Format Baru') }}</span>
                                                    @elseif($doc->format_choice === 'lama')
                                                        <span class="badge badge-xs bg-amber-500/10 text-amber-700 dark:text-amber-300 border-0 font-semibold">{{ __('Format Lama') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Company & Branch --}}
                                    <td class="py-3 whitespace-nowrap">
                                        @php
                                            $companyName = $doc->branch?->company?->name ?? $doc->company?->name;
                                            $branch = $doc->branch;
                                            $isPusat = $branch && ($branch->is_pusat || strcasecmp(trim($branch->name), 'pusat') === 0);
                                        @endphp
                                        @if($companyName || $branch)
                                            <div class="inline-flex items-center gap-1.5 text-xs">
                                                <span class="font-semibold text-base-content">{{ $companyName ?? '—' }}</span>
                                                @if($isPusat)
                                                    <span class="badge badge-primary badge-xs font-bold leading-none">{{ __('Pusat') }}</span>
                                                @elseif($branch)
                                                    <span class="text-base-content/30">•</span>
                                                    <span class="text-base-content/70 text-[11px] font-medium">{{ $branch->name }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-base-content/40 font-mono text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- Unit Kerja --}}
                                    <td class="py-3 whitespace-nowrap">
                                        @if($doc->unitKerja)
                                            <div class="inline-flex items-center gap-1.5 text-xs">
                                                <span class="font-semibold text-base-content">{{ $doc->unitKerja->name }}</span>
                                                @if($doc->unitKerja->code)
                                                    <span class="badge badge-ghost badge-xs font-mono font-semibold text-base-content/70 border border-base-300/80">
                                                        {{ $doc->unitKerja->code }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-base-content/40 font-mono text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- Document Type --}}
                                    <td class="py-3">
                                        @if($doc->documentType)
                                            <div class="space-y-1">
                                                <div class="font-semibold text-xs text-base-content leading-tight">
                                                    {{ $doc->documentType->name }}
                                                </div>
                                                <div class="flex items-center gap-1.5 text-[11px] text-base-content/60 flex-wrap">
                                                    @if($doc->documentType->code)
                                                        <span class="badge badge-primary/10 text-primary border-primary/20 font-mono font-bold badge-xs">
                                                            {{ $doc->documentType->code }}
                                                        </span>
                                                    @endif
                                                    @if($doc->documentType->category)
                                                        <span class="text-[10.5px] text-base-content/50 font-medium">
                                                            {{ $doc->documentType->category === 'naskah_dinas' ? __('Naskah Dinas') : ($doc->documentType->category === 'akreditasi' ? __('Akreditasi') : ucwords(str_replace('_', ' ', $doc->documentType->category))) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-base-content/40 font-mono text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- Owner & Created At --}}
                                    <td class="py-3 whitespace-nowrap">
                                        <div class="space-y-1">
                                            <div class="font-semibold text-xs text-base-content flex items-center gap-1.5 truncate max-w-[140px]" title="{{ $doc->owner?->name ?? '—' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span class="truncate">{{ $doc->owner?->name ?? '—' }}</span>
                                            </div>
                                            <div class="text-[11px] text-base-content/50 flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>{{ $doc->created_at ? $doc->created_at->format('d M Y, H:i') : '—' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="py-3 whitespace-nowrap">
                                        @if($doc->is_expired)
                                            <span class="badge badge-error badge-sm text-white font-medium gap-1.5 shadow-2xs leading-none">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                                {{ __('Kedaluwarsa') }}
                                            </span>
                                        @elseif($hasPending)
                                            <span class="badge badge-warning badge-sm text-neutral font-medium gap-1.5 shadow-2xs leading-none">
                                                <span class="w-1.5 h-1.5 rounded-full bg-neutral/80"></span>
                                                {{ __('Menunggu Review') }}
                                            </span>
                                        @elseif($isActive)
                                            <span class="badge badge-success badge-sm text-white font-medium gap-1.5 shadow-2xs leading-none">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                                {{ __('Aktif') }}
                                            </span>
                                        @elseif($hasRejected)
                                            <span class="badge badge-error/15 text-error border border-error/30 badge-sm font-medium gap-1.5 leading-none">
                                                <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                                {{ __('Ditolak') }}
                                            </span>
                                        @elseif($hasDraft)
                                            <span class="badge badge-ghost badge-sm font-medium border border-base-300 gap-1.5 text-base-content/80 leading-none">
                                                <span class="w-1.5 h-1.5 rounded-full bg-base-content/40"></span>
                                                {{ __('Draf') }}
                                            </span>
                                        @else
                                            <span class="badge badge-ghost badge-sm border border-base-300 font-medium leading-none">{{ ucfirst($displayVer->status ?? 'Draft') }}</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            {{-- Preview / Detail --}}
                                            <a href="{{ route('documents.show', $doc) }}" 
                                               class="btn btn-ghost btn-xs btn-square text-base-content/70 hover:text-primary hover:bg-primary/10" 
                                               title="{{ __('Buka Dokumen') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>

                                            {{-- Edit Document Info (Admin Direct Edit) --}}
                                            <a href="{{ route('admin.documents.edit', $doc) }}" 
                                               class="btn btn-ghost btn-xs btn-square text-base-content/70 hover:text-warning hover:bg-warning/10" 
                                               title="{{ __('Edit Informasi Dokumen') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>

                                            {{-- Download Button --}}
                                            <a href="{{ route('admin.documents.download', $doc) }}" 
                                               class="btn btn-ghost btn-xs btn-square text-base-content/70 hover:text-success hover:bg-success/10" 
                                               title="{{ __('Unduh Dokumen') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </a>

                                            {{-- Delete Button (Modal trigger) --}}
                                            <button type="button" 
                                                    onclick="document.getElementById('confirm_delete_modal_{{ $doc->id }}').showModal()" 
                                                    class="btn btn-ghost btn-xs btn-square text-base-content/70 hover:text-error hover:bg-error/10" 
                                                    title="{{ __('Hapus Dokumen') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-base-content/60 py-16">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <div class="w-14 h-14 rounded-2xl bg-base-200/80 flex items-center justify-center text-base-content/30 shadow-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <p class="font-bold text-base text-base-content">{{ __('Tidak Ada Dokumen Ditemukan') }}</p>
                                            <p class="text-xs text-base-content/50 max-w-sm">
                                                {{ __('Tidak ada data dokumen yang sesuai dengan kata kunci pencarian atau filter yang dipilih.') }}
                                            </p>
                                            @if($hasActiveFilters)
                                                <a href="{{ route('admin.documents.index') }}" class="btn btn-primary btn-sm mt-2">
                                                    {{ __('Bersihkan Filter Pencarian') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                @if($documents->hasPages())
                    <div class="p-4 border-t border-base-200 bg-base-100/50">
                        {{ $documents->links() }}
                    </div>
                @endif
            </div>

        </div>

        {{-- Single Delete Confirmation Modals --}}
        @foreach($documents as $doc)
            <dialog id="confirm_delete_modal_{{ $doc->id }}" class="modal">
                <div class="modal-box max-w-md">
                    <div class="flex items-center gap-3 text-warning mb-2">
                        <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-base-content">{{ __('Pindahkan ke Tempat Sampah?') }}</h3>
                    </div>
                    <p class="text-xs text-base-content/70 mt-1">
                        {{ __('Apakah Anda yakin ingin memindahkan dokumen') }} <span class="font-bold text-base-content">"{{ $doc->title }}"</span> {{ __('ke tempat sampah?') }}
                    </p>
                    <div class="modal-action mt-6 flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('confirm_delete_modal_{{ $doc->id }}').close()">
                            {{ __('Batal') }}
                        </button>
                        <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-warning btn-sm text-neutral font-semibold gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ __('Pindahkan ke Sampah') }}
                            </button>
                        </form>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        @endforeach

        {{-- Bulk Move to Trash Confirmation Modal (DaisyUI native dialog) --}}
        <dialog id="confirm_bulk_trash_modal" class="modal">
            <div class="modal-box max-w-md">
                <div class="flex items-center gap-3 text-warning mb-2">
                    <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-base-content">{{ __('Pindahkan Dokumen ke Sampah?') }}</h3>
                </div>
                <p class="text-xs text-base-content/70 mt-1">
                    {{ __('Apakah Anda yakin ingin memindahkan') }} <span class="font-bold text-base-content modal-selected-count">0</span> {{ __('dokumen yang dipilih ke tempat sampah?') }}
                </p>
                
                {{-- Native Form for Bulk Trash --}}
                <form id="bulk_trash_form" method="POST" action="{{ route('admin.documents.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="force" value="0">
                    <div id="bulk_trash_inputs"></div>

                    <div class="modal-action mt-6 flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('confirm_bulk_trash_modal').close()">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" class="btn btn-warning btn-sm text-neutral font-semibold gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            {{ __('Pindahkan ke Sampah') }}
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        {{-- Bulk Force Delete Confirmation Modal (DaisyUI native dialog) --}}
        <dialog id="confirm_bulk_force_delete_modal" class="modal">
            <div class="modal-box max-w-md">
                <div class="flex items-center gap-3 text-error mb-2">
                    <div class="w-10 h-10 rounded-xl bg-error/10 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-base-content">{{ __('Hapus Dokumen Secara Permanen?') }}</h3>
                </div>
                <p class="text-xs text-base-content/70 mt-1">
                    <span class="font-bold text-error">{{ __('PERINGATAN:') }}</span> {{ __('Tindakan ini akan menghapus') }} <span class="font-bold text-base-content modal-selected-count">0</span> {{ __('dokumen terpilih dan seluruh file fisiknya secara PERMANEN. Tindakan ini tidak dapat dipulihkan!') }}
                </p>

                {{-- Native Form for Bulk Force Delete --}}
                <form id="bulk_force_delete_form" method="POST" action="{{ route('admin.documents.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="force" value="1">
                    <div id="bulk_force_delete_inputs"></div>

                    <div class="modal-action mt-6 flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('confirm_bulk_force_delete_modal').close()">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" class="btn btn-error btn-sm text-white font-semibold gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            {{ __('Hapus Permanen') }}
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

    </div>

    {{-- Standard, Fail-Safe JavaScript Handler for Checkbox & Bulk Actions --}}
    <script>
        function toggleAllCheckboxes(masterCheckbox) {
            const checkboxes = document.querySelectorAll('input.doc-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = masterCheckbox.checked;
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('input.doc-checkbox:checked');
            const allBoxes = document.querySelectorAll('input.doc-checkbox');
            const checkedCount = checkedBoxes.length;

            const bar = document.getElementById('floating-bulk-bar');
            const badge = document.getElementById('selected-count-badge');
            const modalCounts = document.querySelectorAll('.modal-selected-count');
            const master = document.getElementById('master-checkbox');

            if (badge) badge.innerText = checkedCount;
            modalCounts.forEach(el => el.innerText = checkedCount);

            if (master && allBoxes.length > 0) {
                master.checked = (checkedCount === allBoxes.length);
            }

            if (bar) {
                if (checkedCount > 0) {
                    bar.classList.remove('hidden');
                } else {
                    bar.classList.add('hidden');
                }
            }

            // Update row background
            allBoxes.forEach(cb => {
                const row = document.getElementById('doc-row-' + cb.value);
                if (row) {
                    if (cb.checked) {
                        row.classList.add('bg-primary/5');
                    } else {
                        row.classList.remove('bg-primary/5');
                    }
                }
            });
        }

        function clearAllCheckboxes() {
            const checkboxes = document.querySelectorAll('input.doc-checkbox');
            checkboxes.forEach(cb => { cb.checked = false; });
            const master = document.getElementById('master-checkbox');
            if (master) master.checked = false;
            updateSelectedCount();
        }

        function submitBulkDownload() {
            const checkedBoxes = document.querySelectorAll('input.doc-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('{{ __('Pilih setidaknya satu dokumen terlebih dahulu.') }}');
                return;
            }

            const container = document.getElementById('bulk_download_inputs');
            container.innerHTML = '';

            checkedBoxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            document.getElementById('bulk_download_form').submit();
        }

        function openBulkTrashModal() {
            const checkedBoxes = document.querySelectorAll('input.doc-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('{{ __('Pilih setidaknya satu dokumen terlebih dahulu.') }}');
                return;
            }

            const container = document.getElementById('bulk_trash_inputs');
            container.innerHTML = '';

            checkedBoxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            document.querySelectorAll('.modal-selected-count').forEach(el => el.innerText = checkedBoxes.length);
            document.getElementById('confirm_bulk_trash_modal').showModal();
        }

        function openBulkForceDeleteModal() {
            const checkedBoxes = document.querySelectorAll('input.doc-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('{{ __('Pilih setidaknya satu dokumen terlebih dahulu.') }}');
                return;
            }

            const container = document.getElementById('bulk_force_delete_inputs');
            container.innerHTML = '';

            checkedBoxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            document.querySelectorAll('.modal-selected-count').forEach(el => el.innerText = checkedBoxes.length);
            document.getElementById('confirm_bulk_force_delete_modal').showModal();
        }
    </script>
</x-app-layout>
