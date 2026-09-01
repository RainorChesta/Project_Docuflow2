<x-app-layout>
    <x-slot name="header">{{ __('Master Unit Kerja') }}</x-slot>

    <div class="py-6" x-data="{
        openBranches: {{ json_encode($initialOpenBranchIds) }},
        allBranchIds: {{ json_encode($allBranchIds) }},

        isOpen(id) {
            return this.openBranches.includes(String(id));
        },

        toggle(id) {
            const sid = String(id);
            if (this.isOpen(sid)) {
                this.openBranches = this.openBranches.filter(i => i !== sid);
            } else {
                this.openBranches.push(sid);
            }
        },

        expandAll() {
            this.openBranches = [...this.allBranchIds];
        },

        collapseAll() {
            this.openBranches = [];
        }
    }">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- Top Header & Actions --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-base-content">{{ __('Master Unit Kerja') }}</h2>
                    <p class="text-xs text-base-content/60 mt-0.5">
                        {{ __('Kelola unit kerja dan kode penomoran surat SOP untuk masing-masing cabang perusahaan.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.unit-kerja.create', ['cabang_id' => $cabangId]) }}" class="btn btn-primary btn-sm gap-2 shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('Unit Kerja Baru') }}
                    </a>
                </div>
            </div>

            {{-- Filter & Search Toolbar --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
                <form method="GET" action="{{ route('admin.unit-kerja.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5 flex-1">
                        {{-- Search Input --}}
                        <div class="relative min-w-[220px] flex-1 max-w-sm">
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="{{ __('Cari kode / nama unit kerja...') }}"
                                   class="input input-bordered input-sm w-full pl-8 pr-7 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            @if($search)
                                <a href="{{ route('admin.unit-kerja.index', array_filter(['company_id' => $companyId, 'cabang_id' => $cabangId])) }}"
                                   class="absolute right-2 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content text-xs p-1">
                                    ✕
                                </a>
                            @endif
                        </div>

                        {{-- Filter Perusahaan --}}
                        <select name="company_id" onchange="this.form.submit()" class="select select-bordered select-sm text-xs">
                            <option value="">{{ __('Semua Perusahaan') }}</option>
                            @foreach($allCompanies as $comp)
                                <option value="{{ $comp->id }}" {{ $companyId == $comp->id ? 'selected' : '' }}>
                                    {{ $comp->name }} ({{ $comp->code }})
                                </option>
                            @endforeach
                        </select>

                        {{-- Filter Cabang --}}
                        <select name="cabang_id" onchange="this.form.submit()" class="select select-bordered select-sm text-xs">
                            <option value="">{{ __('Semua Cabang') }}</option>
                            @foreach($allBranches as $b)
                                <option value="{{ $b->id }}" {{ $cabangId == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }} ({{ $b->effective_code }})
                                </option>
                            @endforeach
                        </select>

                        @if($search || $cabangId || $companyId)
                            <a href="{{ route('admin.unit-kerja.index') }}" class="btn btn-ghost btn-sm text-xs gap-1 text-base-content/70 hover:text-base-content">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Reset Filter') }}
                            </a>
                        @endif
                    </div>

                    {{-- Accordion Quick Controls (Expand / Collapse All) --}}
                    <div class="flex items-center gap-1.5 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-base-200">
                        <button type="button"
                                @click="expandAll()"
                                class="btn btn-ghost btn-xs text-xs gap-1 hover:bg-base-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            {{ __('Buka Semua') }}
                        </button>
                        <button type="button"
                                @click="collapseAll()"
                                class="btn btn-ghost btn-xs text-xs gap-1 hover:bg-base-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                            {{ __('Tutup Semua') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert alert-success text-sm py-2 shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-error text-sm py-2 shadow-xs">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Accordion List Grouped by Company & Branch --}}
            @php($hasAnyCompanies = $companies->isNotEmpty() || $orphanBranches->isNotEmpty())

            @if($hasAnyCompanies)
                <div class="space-y-6">
                    @foreach($companies as $company)
                        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                            {{-- Company Header Banner --}}
                            <div class="bg-base-200/50 border-b border-base-300 px-5 py-3.5 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-bold text-base text-base-content truncate">{{ $company->name }}</h3>
                                            <span class="badge badge-outline badge-xs font-mono font-bold">{{ $company->code }}</span>
                                        </div>
                                        <p class="text-xs text-base-content/50 mt-0.5">
                                            {{ $company->branches->count() }} {{ __('Cabang') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Branches Accordion Items --}}
                            <div class="divide-y divide-base-200">
                                @forelse($company->branches as $branch)
                                    <div class="transition-colors">
                                        {{-- Branch Accordion Header (Clickable) --}}
                                        <div class="px-5 py-4 flex items-center justify-between gap-4 cursor-pointer select-none hover:bg-base-200/40 transition-colors"
                                             @click="toggle('{{ $branch->id }}')">
                                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                                {{-- Accordion Indicator Arrow --}}
                                                <div class="w-6 h-6 rounded-lg bg-base-200 flex items-center justify-center shrink-0 text-base-content/60 transition-transform duration-200"
                                                     :class="isOpen('{{ $branch->id }}') ? 'rotate-180 bg-primary/10 text-primary' : ''">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="font-semibold text-sm text-base-content">{{ $branch->name }}</span>
                                                        <span class="badge badge-ghost badge-xs font-mono font-bold">{{ $branch->effective_code }}</span>
                                                        @if($branch->is_pusat)
                                                            <span class="badge badge-primary badge-xs">{{ __('Pusat') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-3 shrink-0" @click.stop>
                                                {{-- Unit Kerja Count Badge --}}
                                                <span class="badge badge-sm font-medium"
                                                      :class="{{ $branch->unitKerjas->count() }} > 0 ? 'badge-primary badge-outline' : 'badge-ghost text-base-content/40'">
                                                    {{ $branch->unitKerjas->count() }} {{ __('Unit Kerja') }}
                                                </span>

                                                {{-- Quick Add Unit Kerja in this Branch --}}
                                                <a href="{{ route('admin.unit-kerja.create', ['cabang_id' => $branch->id]) }}"
                                                   class="btn btn-ghost btn-xs text-primary hover:bg-primary/10 gap-1"
                                                   title="{{ __('Tambah unit kerja untuk cabang ini') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    <span class="hidden sm:inline text-xs">{{ __('Tambah') }}</span>
                                                </a>
                                            </div>
                                        </div>

                                        {{-- Branch Accordion Content --}}
                                        <div x-show="isOpen('{{ $branch->id }}')"
                                             x-cloak
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 translate-y-0"
                                             x-transition:leave-end="opacity-0 -translate-y-1"
                                             class="bg-base-200/20 border-t border-base-200 px-5 py-4">
                                            @if($branch->unitKerjas->isNotEmpty())
                                                <div class="overflow-x-auto rounded-xl border border-base-300/80 bg-base-100 shadow-2xs">
                                                    <table class="table table-sm min-w-[600px] w-full">
                                                        <thead class="bg-base-200/50 text-xs font-semibold uppercase text-base-content/60">
                                                            <tr>
                                                                <th class="w-32">{{ __('Kode Unit') }}</th>
                                                                <th>{{ __('Nama Unit Kerja') }}</th>
                                                                <th>{{ __('Contoh Format SOP') }}</th>
                                                                <th class="text-center w-28">{{ __('Dokumen SOP') }}</th>
                                                                <th class="text-right w-24">{{ __('Aksi') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-base-200/70 text-xs">
                                                            @foreach($branch->unitKerjas as $unit)
                                                                <tr class="hover:bg-base-200/40 transition-colors">
                                                                    <td>
                                                                        <span class="badge badge-primary font-mono font-bold text-xs">
                                                                            {{ $unit->kode_unit_kerja }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="font-medium text-base-content text-sm">
                                                                        {{ $unit->nama_unit_kerja }}
                                                                    </td>
                                                                    <td>
                                                                        <code class="text-[11px] px-2 py-0.5 rounded bg-base-200 font-mono text-base-content/70">
                                                                            001/SOP-{{ $unit->kode_unit_kerja }}/{{ $branch->effective_code }}/...
                                                                        </code>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge badge-ghost badge-sm text-xs font-medium">
                                                                            {{ $unit->documents_count }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-right">
                                                                        <div class="flex items-center justify-end gap-1">
                                                                            <a href="{{ route('admin.unit-kerja.edit', $unit) }}"
                                                                               class="btn btn-ghost btn-xs btn-square text-primary hover:bg-primary/10"
                                                                               title="{{ __('Edit Unit Kerja') }}">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                                </svg>
                                                                            </a>

                                                                            @if($unit->documents_count === 0)
                                                                                <form method="POST"
                                                                                      action="{{ route('admin.unit-kerja.destroy', $unit) }}"
                                                                                      class="inline"
                                                                                      onsubmit="return confirm('{{ __('Hapus unit kerja :code - :name?', ['code' => $unit->kode_unit_kerja, 'name' => $unit->nama_unit_kerja]) }}')">
                                                                                    @csrf
                                                                                    @method('DELETE')
                                                                                    <button type="submit"
                                                                                            class="btn btn-ghost btn-xs btn-square text-error hover:bg-error/10"
                                                                                            title="{{ __('Hapus Unit Kerja') }}">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                                        </svg>
                                                                                    </button>
                                                                                </form>
                                                                            @else
                                                                                <span class="tooltip tooltip-left" data-tip="{{ __('Tidak dapat dihapus karena telah digunakan pada :count dokumen SOP.', ['count' => $unit->documents_count]) }}">
                                                                                    <button type="button" class="btn btn-ghost btn-xs btn-square text-base-content/20 cursor-not-allowed" disabled>
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                                        </svg>
                                                                                    </button>
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="text-center py-6 px-4 bg-base-100 rounded-xl border border-dashed border-base-300 text-base-content/50">
                                                    <p class="text-xs mb-2">{{ __('Belum ada unit kerja yang didaftarkan untuk cabang :name.', ['name' => $branch->name]) }}</p>
                                                    <a href="{{ route('admin.unit-kerja.create', ['cabang_id' => $branch->id]) }}"
                                                       class="btn btn-primary btn-xs gap-1.5 font-medium">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                        {{ __('Tambah Unit Kerja Sekarang') }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-6 text-center text-xs text-base-content/50">
                                        {{ __('Perusahaan ini belum memiliki cabang terdaftar.') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach

                    {{-- Orphan Branches if any --}}
                    @if($orphanBranches->isNotEmpty())
                        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                            <div class="bg-base-200/50 border-b border-base-300 px-5 py-3.5 flex items-center justify-between gap-4">
                                <h3 class="font-bold text-base text-base-content">{{ __('Cabang Lainnya') }}</h3>
                            </div>
                            <div class="divide-y divide-base-200">
                                @foreach($orphanBranches as $branch)
                                    <div>
                                        <div class="px-5 py-4 flex items-center justify-between gap-4 cursor-pointer select-none hover:bg-base-200/40 transition-colors"
                                             @click="toggle('{{ $branch->id }}')">
                                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                                <div class="w-6 h-6 rounded-lg bg-base-200 flex items-center justify-center shrink-0 text-base-content/60 transition-transform duration-200"
                                                     :class="isOpen('{{ $branch->id }}') ? 'rotate-180 bg-primary/10 text-primary' : ''">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-semibold text-sm text-base-content">{{ $branch->name }}</span>
                                                    <span class="badge badge-ghost badge-xs font-mono font-bold">{{ $branch->effective_code }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3 shrink-0" @click.stop>
                                                <span class="badge badge-sm font-medium" :class="{{ $branch->unitKerjas->count() }} > 0 ? 'badge-primary badge-outline' : 'badge-ghost'">
                                                    {{ $branch->unitKerjas->count() }} {{ __('Unit Kerja') }}
                                                </span>
                                                <a href="{{ route('admin.unit-kerja.create', ['cabang_id' => $branch->id]) }}" class="btn btn-ghost btn-xs text-primary">
                                                    {{ __('Tambah') }}
                                                </a>
                                            </div>
                                        </div>

                                        <div x-show="isOpen('{{ $branch->id }}')" x-cloak class="bg-base-200/20 border-t border-base-200 px-5 py-4">
                                            @if($branch->unitKerjas->isNotEmpty())
                                                <div class="overflow-x-auto rounded-xl border border-base-300/80 bg-base-100 shadow-2xs">
                                                    <table class="table table-sm min-w-[600px] w-full">
                                                        <thead class="bg-base-200/50 text-xs uppercase text-base-content/60">
                                                            <tr>
                                                                <th class="w-32">{{ __('Kode Unit') }}</th>
                                                                <th>{{ __('Nama Unit Kerja') }}</th>
                                                                <th>{{ __('Contoh Format SOP') }}</th>
                                                                <th class="text-center w-28">{{ __('Dokumen SOP') }}</th>
                                                                <th class="text-right w-24">{{ __('Aksi') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-base-200/70 text-xs">
                                                            @foreach($branch->unitKerjas as $unit)
                                                                <tr class="hover:bg-base-200/40 transition-colors">
                                                                    <td>
                                                                        <span class="badge badge-primary font-mono font-bold text-xs">{{ $unit->kode_unit_kerja }}</span>
                                                                    </td>
                                                                    <td class="font-medium text-base-content text-sm">{{ $unit->nama_unit_kerja }}</td>
                                                                    <td>
                                                                        <code class="text-[11px] px-2 py-0.5 rounded bg-base-200 font-mono text-base-content/70">
                                                                            001/SOP-{{ $unit->kode_unit_kerja }}/{{ $branch->effective_code }}/...
                                                                        </code>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge badge-ghost badge-sm text-xs font-medium">{{ $unit->documents_count }}</span>
                                                                    </td>
                                                                    <td class="text-right">
                                                                        <div class="flex items-center justify-end gap-1">
                                                                            <a href="{{ route('admin.unit-kerja.edit', $unit) }}" class="btn btn-ghost btn-xs btn-square text-primary">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                                            </a>
                                                                            @if($unit->documents_count === 0)
                                                                                <form method="POST" action="{{ route('admin.unit-kerja.destroy', $unit) }}" class="inline" onsubmit="return confirm('{{ __('Hapus unit kerja?') }}')">
                                                                                    @csrf @method('DELETE')
                                                                                    <button type="submit" class="btn btn-ghost btn-xs btn-square text-error">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                                                    </button>
                                                                                </form>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="text-center py-4 text-xs text-base-content/50">{{ __('Belum ada unit kerja.') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="card bg-base-100 border border-base-300 p-12 text-center shadow-sm">
                    <div class="w-16 h-16 rounded-2xl bg-base-200 flex items-center justify-center mx-auto text-base-content/40 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-base-content">{{ __('Tidak ada unit kerja atau cabang ditemukan') }}</h3>
                    <p class="text-xs text-base-content/60 mt-1 max-w-sm mx-auto">
                        {{ __('Tidak ada data yang cocok dengan kriteria pencarian atau filter yang Anda pilih.') }}
                    </p>
                    <div class="mt-4 flex items-center justify-center gap-2">
                        <a href="{{ route('admin.unit-kerja.index') }}" class="btn btn-ghost btn-sm text-xs">
                            {{ __('Bersihkan Filter') }}
                        </a>
                        <a href="{{ route('admin.unit-kerja.create') }}" class="btn btn-primary btn-sm text-xs gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('Tambah Unit Kerja Baru') }}
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
