<x-app-layout>
    <x-slot name="header">{{ __('Master Cabang') }}</x-slot>

    <div class="py-6" x-data="{ allOpen: {{ $companyId ? 'true' : 'false' }} }">
        <div class="max-w-7xl mx-auto w-full">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.branches.create', ['company_id' => $companyId]) }}" class="btn btn-primary btn-sm gap-1.5 shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ __('Cabang Baru') }}
                    </a>

                    <button type="button" 
                            @click="allOpen = !allOpen; $dispatch('toggle-all-branches', { open: allOpen })" 
                            class="btn btn-outline btn-sm gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-200" :class="allOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span x-text="allOpen ? '{{ __('Tutup Semua') }}' : '{{ __('Buka Semua') }}'">{{ __('Buka Semua') }}</span>
                    </button>
                </div>

                {{-- Filter per Company --}}
                <form method="GET" action="{{ route('admin.branches.index') }}" class="flex items-center gap-2">
                    <select name="company_id" onchange="this.form.submit()" class="select select-bordered select-sm w-full sm:w-64">
                        <option value="">{{ __('Semua Perusahaan') }}</option>
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}" {{ $companyId == $comp->id ? 'selected' : '' }}>{{ $comp->name }} ({{ $comp->code }})</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4 text-sm">{{ session('error') }}</div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table min-w-[720px] w-full">
                        <thead class="bg-base-200/50">
                            <tr>
                                <th class="w-10 text-center pr-0"></th>
                                <th>{{ __('Perusahaan') }}</th>
                                <th>{{ __('Jumlah Cabang') }}</th>
                                <th>{{ __('Kode Cabang (Efektif)') }}</th>
                                <th>{{ __('Jumlah User') }}</th>
                                <th>{{ __('Jumlah Dokumen') }}</th>
                                <th class="text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>

                        @forelse($companyGroups as $company)
                            @php
                                $pusatBranch = $company->branches->firstWhere('is_pusat', true);
                                $otherBranches = $company->branches->where('is_pusat', false);
                            @endphp

                            <tbody x-data="{ 
                                open: {{ $companyId ? 'true' : 'false' }},
                                toggle() {
                                    this.open = !this.open;
                                }
                            }" 
                            @toggle-all-branches.window="open = $event.detail.open"
                            class="border-b border-base-200/80 last:border-b-0">
                                {{-- Parent Row: Kantor Pusat --}}
                                <tr @click="toggle()" 
                                    class="cursor-pointer transition-colors duration-150 select-none group border-b border-base-200/60"
                                    :class="open ? 'bg-primary/5 font-medium' : 'hover:bg-base-200/50'">
                                    
                                    {{-- Chevron Icon Indicator --}}
                                    <td class="w-10 text-center pr-0">
                                        <button type="button" 
                                                @click.stop="toggle()"
                                                class="btn btn-ghost btn-xs btn-circle transition-transform duration-200" 
                                                :class="open ? 'rotate-90 text-primary' : 'text-base-content/40 group-hover:text-base-content'">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </td>

                                    {{-- Perusahaan --}}
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-sm text-base-content">{{ $company->name }}</span>
                                            <span class="badge badge-outline badge-xs font-mono text-[10px]">{{ $company->code }}</span>
                                        </div>
                                    </td>

                                    {{-- Jumlah Cabang (Branches Count) --}}
                                    <td>
                                        @if($otherBranches->count() > 0)
                                            <span class="badge badge-neutral badge-sm text-xs font-normal" 
                                                  title="{{ $otherBranches->count() }} {{ __('Cabang di bawah') }} {{ $company->name }}">
                                                {{ $otherBranches->count() }} {{ __('Cabang') }}
                                            </span>
                                        @else
                                            <span class="badge badge-ghost badge-sm text-xs opacity-60" title="{{ $pusatBranch?->name }}">
                                                {{ __('Hanya Pusat') }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Kode Cabang (Efektif) --}}
                                    <td>
                                        <div class="flex items-center gap-1.5">
                                            <span class="badge badge-outline font-mono text-xs font-medium">
                                                {{ $pusatBranch?->effective_code ?? $company->code }}
                                            </span>
                                            <span class="text-[11px] text-base-content/50">({{ __('ikut PT') }})</span>
                                        </div>
                                    </td>

                                    {{-- Jumlah User --}}
                                    <td class="text-sm font-medium text-base-content/80">{{ $pusatBranch?->users_count ?? 0 }}</td>

                                    {{-- Jumlah Dokumen --}}
                                    <td class="text-sm font-medium text-base-content/80">{{ $pusatBranch?->documents_count ?? 0 }}</td>

                                    {{-- Aksi --}}
                                    <td class="text-right" @click.stop>
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($pusatBranch)
                                                <a href="{{ route('admin.branches.edit', $pusatBranch) }}" 
                                                   class="btn btn-ghost btn-xs btn-square text-primary hover:bg-primary/10" 
                                                   title="{{ __('Edit Pusat') }}: {{ $pusatBranch->name }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>
                                            @endif

                                            <button type="button" 
                                                    @click="toggle()" 
                                                    class="btn btn-ghost btn-xs text-xs gap-1 font-normal hover:bg-base-200"
                                                    :class="open ? 'text-primary font-medium' : 'text-base-content/60'">
                                                <span x-text="open ? '{{ __('Tutup') }}' : '{{ __('Cabang') }}'">{{ __('Cabang') }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-180 text-primary' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Child Accordion Row: Other Branches in this Company --}}
                                <tr x-show="open" x-cloak x-transition.opacity.duration.200ms class="bg-base-200/25">
                                    <td colspan="7" class="p-0 border-b border-base-300">
                                        <div class="px-4 py-4 sm:px-8 border-y border-base-200/80">
                                            <div class="flex items-center justify-between gap-3 mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                                                    <h4 class="text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                                        {{ __('Cabang Lain di') }} {{ $company->name }}
                                                    </h4>
                                                </div>
                                                <a href="{{ route('admin.branches.create', ['company_id' => $company->id]) }}" 
                                                   class="btn btn-xs btn-primary btn-outline gap-1.5 font-medium rounded-md">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    {{ __('Tambah Cabang') }}
                                                </a>
                                            </div>

                                            @if($otherBranches->isNotEmpty())
                                                <div class="grid grid-cols-1 gap-2">
                                                    @foreach($otherBranches as $branch)
                                                        <div class="flex items-center justify-between gap-4 px-4 py-3 bg-base-100 border border-base-300 rounded-xl hover:border-primary/40 hover:shadow-xs transition-all duration-150">
                                                            <div class="flex items-center gap-3 min-w-0">
                                                                <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center text-primary shrink-0">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                                    </svg>
                                                                </div>
                                                                <div class="min-w-0">
                                                                    <div class="flex items-center gap-2">
                                                                        <span class="font-semibold text-sm text-base-content truncate">{{ $branch->name }}</span>
                                                                        <span class="badge badge-outline badge-xs font-mono text-[10px]">{{ $branch->effective_code }}</span>
                                                                    </div>
                                                                    <div class="text-xs text-base-content/50 mt-0.5 flex items-center gap-3">
                                                                        <span>{{ $branch->users_count }} {{ __('User') }}</span>
                                                                        <span class="text-base-content/20">•</span>
                                                                        <span>{{ $branch->documents_count }} {{ __('Dokumen') }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="flex items-center gap-1 shrink-0">
                                                                <a href="{{ route('admin.branches.edit', $branch) }}" 
                                                                   class="btn btn-ghost btn-xs btn-square text-primary hover:bg-primary/10" 
                                                                   title="{{ __('Edit') }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                    </svg>
                                                                </a>
                                                                <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" class="inline" onsubmit="return confirm('{{ __('Hapus cabang ini?') }}')">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="btn btn-ghost btn-xs btn-square text-error hover:bg-error/10" title="{{ __('Hapus') }}">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                        </svg>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="flex flex-col sm:flex-row items-center justify-between p-4 bg-base-100 rounded-xl border border-base-300 border-dashed gap-3 text-center sm:text-left">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-lg bg-base-200 flex items-center justify-center shrink-0 text-base-content/40">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <p class="text-xs font-semibold text-base-content/80">{{ __('Hanya Pusat') }} — {{ __('Belum ada cabang tambahan') }}</p>
                                                            <p class="text-xs text-base-content/50">{{ __('Perusahaan ini saat ini hanya memiliki kantor Pusat.') }}</p>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('admin.branches.create', ['company_id' => $company->id]) }}" class="btn btn-xs btn-primary gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                        {{ __('Tambah Cabang Pertama') }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center text-base-content/60 py-8">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <p class="text-sm font-medium">{{ __('Belum ada data perusahaan atau cabang.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>

                @if($companyGroups->hasPages())
                    <div class="p-4 border-t border-base-200">{{ $companyGroups->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
