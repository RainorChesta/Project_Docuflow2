<x-app-layout>
    <x-slot name="header">{{ __('Dokumen Seluruh Perusahaan & Cabang') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full">
            {{-- Header info --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-base-content">{{ __('Direktori Dokumen Perusahaan') }}</h2>
                    <p class="text-xs text-base-content/60 mt-1">
                        {{ __('Role Direktur memiliki akses peninjauan seluruh dokumen lintas Company & Cabang.') }}
                    </p>
                </div>

                {{-- Global Search Bar for Director --}}
                <form method="GET" action="{{ route('director.documents.index') }}" class="flex items-center gap-2">
                    <div class="relative w-full sm:w-80">
                        <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('Cari judul atau nomor dokumen...') }}" 
                               class="input input-bordered input-sm w-full pl-8">
                        <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    @if($selectedCompanyId)<input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">@endif
                    @if($selectedBranchId)<input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">@endif
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Cari') }}</button>
                    @if($search || $selectedBranchId)
                        <a href="{{ route('director.documents.index') }}" class="btn btn-ghost btn-sm">{{ __('Reset') }}</a>
                    @endif
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- Left Column: Accordion Company -> Cabang (4 cols) --}}
                <div class="lg:col-span-4 space-y-3">
                    <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
                        <h3 class="font-semibold text-sm mb-3 flex items-center gap-2 text-base-content">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            {{ __('Struktur Perusahaan & Cabang') }}
                        </h3>

                        <div class="space-y-2" x-data="{ openCompany: {{ $selectedCompanyId ?? ($companies->first()?->id ?? 0) }} }">
                            @forelse($companies as $company)
                                <div class="border border-base-300 rounded-lg overflow-hidden">
                                    {{-- Level 1: Company Header --}}
                                    <button type="button" 
                                            @click="openCompany = (openCompany === {{ $company->id }} ? null : {{ $company->id }})"
                                            class="w-full flex items-center justify-between p-3 text-left font-medium text-sm transition-colors"
                                            :class="openCompany === {{ $company->id }} ? 'bg-primary/10 text-primary font-semibold' : 'bg-base-100 hover:bg-base-200/50'">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <svg class="w-4 h-4 shrink-0 transition-transform duration-200" 
                                                 :class="openCompany === {{ $company->id }} ? 'rotate-90 text-primary' : 'text-base-content/40'"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                            <span class="truncate">{{ $company->name }}</span>
                                        </div>
                                        <span class="badge badge-sm badge-outline shrink-0">{{ $company->code }}</span>
                                    </button>

                                    {{-- Level 2: Branches under Company --}}
                                    <div x-show="openCompany === {{ $company->id }}" x-cloak class="divide-y divide-base-200 bg-base-100 border-t border-base-200">
                                        @forelse($company->branches as $branch)
                                            <a href="{{ route('director.documents.index', ['company_id' => $company->id, 'branch_id' => $branch->id, 'search' => $search]) }}"
                                               class="flex items-center justify-between p-2.5 pl-8 text-xs hover:bg-base-200 transition-colors {{ $selectedBranchId == $branch->id ? 'bg-primary/15 text-primary font-bold' : 'text-base-content/80' }}">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 {{ $selectedBranchId == $branch->id ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                                    </svg>
                                                    <span class="truncate">{{ $branch->name }} @if($branch->is_pusat)<span class="text-primary font-semibold">(Pusat)</span>@endif</span>
                                                </div>
                                                <span class="badge badge-ghost badge-xs">{{ $branch->documents_count }} {{ __('dok') }}</span>
                                            </a>
                                        @empty
                                            <div class="p-3 text-xs text-base-content/50 italic text-center">{{ __('Belum ada cabang.') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-xs text-base-content/50 text-center">{{ __('Belum ada perusahaan terdaftar.') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Right Column: Document List (8 cols) --}}
                <div class="lg:col-span-8">
                    <div class="card bg-base-100 border border-base-300 shadow-sm">
                        @if($selectedBranchId)
                            @php
                                $currentBranch = \App\Models\Branch::with('company')->find($selectedBranchId);
                            @endphp
                            <div class="p-4 border-b border-base-200 bg-base-200/30 flex items-center justify-between">
                                <div>
                                    <h4 class="font-bold text-sm text-base-content">
                                        {{ $currentBranch?->company?->name }} — {{ $currentBranch?->name }}
                                    </h4>
                                    <p class="text-xs text-base-content/60">
                                        {{ __('Kode Efektif:') }} <span class="font-semibold">{{ $currentBranch?->effective_code }}</span> · {{ __('Menampilkan dokumen di cabang ini') }}
                                    </p>
                                </div>
                                <span class="badge badge-primary badge-sm">{{ $documents->total() }} {{ __('Dokumen') }}</span>
                            </div>

                            <div class="card-body p-0">
                                <div class="divide-y divide-base-200">
                                    @forelse($documents as $doc)
                                        @include('documents._list', ['doc' => $doc])
                                    @empty
                                        <div class="p-8 text-center text-base-content/60">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-2 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p>{{ __('Tidak ada dokumen di cabang ini.') }}</p>
                                        </div>
                                    @endforelse
                                </div>

                                @if($documents->hasPages())
                                    <div class="p-4 border-t border-base-200">
                                        {{ $documents->withQueryString()->links() }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="p-12 text-center text-base-content/60">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto mb-3 text-primary/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <h3 class="text-base font-semibold text-base-content">{{ __('Pilih Cabang untuk Menampilkan Dokumen') }}</h3>
                                <p class="text-xs text-base-content/60 mt-1 max-w-sm mx-auto">
                                    {{ __('Klik salah satu cabang pada panel accordion di sebelah kiri untuk melihat daftar dokumen milik cabang tersebut.') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
