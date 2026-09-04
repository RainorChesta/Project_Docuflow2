<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-base-content leading-tight">
                    {{ __('Document Approval (Version)') }}
                </h2>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ __('Tinjau dan setujui pembaruan konten atau versi dokumen baru sebelum dipublikasikan.') }}
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="badge {{ ($counts['versions'] ?? $pendingVersions->total()) > 0 ? 'badge-primary' : 'badge-ghost' }} gap-1.5 text-xs py-2.5 px-3 font-semibold shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $counts['versions'] ?? $pendingVersions->total() }} {{ __('Menunggu') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6" x-data="{
        selectedVersions: [],
        allVersionIds: {{ json_encode($pendingVersions->pluck('id')->values()->all()) }},
        toggleAllVersions() {
            if (this.selectedVersions.length === this.allVersionIds.length) {
                this.selectedVersions = [];
            } else {
                this.selectedVersions = [...this.allVersionIds];
            }
        },
        isAllVersionsSelected() {
            return this.allVersionIds.length > 0 && this.selectedVersions.length === this.allVersionIds.length;
        },
        isVersionSelected(id) {
            return this.selectedVersions.includes(id);
        },
        toggleVersionRow(id) {
            if (this.selectedVersions.includes(id)) {
                this.selectedVersions = this.selectedVersions.filter(item => item !== id);
            } else {
                this.selectedVersions.push(id);
            }
        }
    }">
        @if(session('success'))
            <div class="alert alert-success shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error shadow-sm">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Filter and Search Controls Bar --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3.5">
                <div class="flex items-center gap-2.5 shrink-0">
                    <span class="text-sm font-semibold text-base-content/90 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('Daftar Versi Dokumen Menunggu Persetujuan') }}
                    </span>
                    <span class="badge badge-sm badge-primary font-bold">{{ $pendingVersions->total() }}</span>
                </div>

                {{-- Search Box & Per-Page Controls --}}
                <form method="GET" action="{{ route('approvals.versions') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full lg:w-auto justify-end">
                    <div class="relative w-full sm:w-80 md:w-96 min-w-[240px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="search" 
                               value="{{ $search ?? '' }}" 
                               placeholder="{{ __('Cari judul, nomor dokumen, penulis, versi...') }}" 
                               class="input input-sm input-bordered w-full pl-9 pr-8 text-sm rounded-xl bg-base-200/50 focus:bg-base-100 transition-colors" />
                        @if(!empty($search))
                            <a href="{{ route('approvals.versions', request()->except('search')) }}" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-base-content/40 hover:text-base-content transition-colors" title="{{ __('Hapus pencarian') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <select name="per_page" onchange="this.form.submit()" class="select select-sm select-bordered text-xs rounded-xl bg-base-200/50 shrink-0 w-auto font-medium">
                            <option value="10" {{ ($perPage ?? 15) == 10 ? 'selected' : '' }}>10 / hal</option>
                            <option value="15" {{ ($perPage ?? 15) == 15 ? 'selected' : '' }}>15 / hal</option>
                            <option value="25" {{ ($perPage ?? 15) == 25 ? 'selected' : '' }}>25 / hal</option>
                            <option value="50" {{ ($perPage ?? 15) == 50 ? 'selected' : '' }}>50 / hal</option>
                        </select>

                        <button type="submit" class="btn btn-sm btn-primary rounded-xl text-xs font-semibold px-4 shrink-0 shadow-xs flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            {{ __('Cari') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bulk Selection Actions Bar --}}
        <div x-show="selectedVersions.length > 0" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="card bg-primary/10 border border-primary/30 p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl shadow-xs"
             x-cloak>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary font-bold text-xs" x-text="selectedVersions.length"></span>
                <span class="text-xs font-semibold text-base-content">{{ __('versi dokumen dipilih') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="document.getElementById('bulk-reject-versions-modal').showModal()" class="btn btn-error btn-outline btn-xs sm:btn-sm gap-1 font-semibold">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    {{ __('Reject Selected Versions') }}
                </button>
                <button type="button" onclick="document.getElementById('bulk-approve-versions-modal').showModal()" class="btn btn-success btn-xs sm:btn-sm gap-1 font-semibold text-white shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ __('Approve Selected Versions') }}
                </button>
            </div>
        </div>

        {{-- Pending Document Versions Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-base-200/50 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Document Approval (Version)') }}
                            <span class="badge badge-primary badge-sm font-semibold">{{ $pendingVersions->total() }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Dokumen menunggu persetujuan versi sebelum dipublikasikan kepada pengguna lain.') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($pendingVersions->isEmpty())
                <div class="py-12 text-center text-base-content/50 space-y-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium">{{ __('Semua versi dokumen telah ditinjau') }}</p>
                    <p class="text-xs text-base-content/40">{{ __('Tidak ada versi dokumen yang sedang menunggu persetujuan.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[720px]">
                        <thead>
                            <tr class="bg-base-200/50 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-4 w-10 text-center">
                                    <input type="checkbox" 
                                           :checked="isAllVersionsSelected()" 
                                           @change="toggleAllVersions()" 
                                           :disabled="allVersionIds.length === 0"
                                           class="checkbox checkbox-xs checkbox-primary rounded" 
                                           title="{{ __('Pilih semua versi pada halaman ini') }}" />
                                </th>
                                <th class="py-3 px-4">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Versi') }}</th>
                                <th class="py-3 px-4">{{ __('Penulis') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu Pengajuan') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($pendingVersions as $version)
                                @if(!$version->document)
                                    @continue
                                @endif
                                <tr class="hover:bg-base-200/40 transition-colors">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" 
                                               :value="{{ $version->id }}" 
                                               :checked="isVersionSelected({{ $version->id }})" 
                                               @change="toggleVersionRow({{ $version->id }})"
                                               class="checkbox checkbox-xs checkbox-primary rounded" />
                                    </td>
                                    <td class="px-4 py-4 max-w-xs">
                                        <a href="{{ route('documents.show', $version->document) }}" class="font-semibold text-sm text-base-content hover:text-primary transition-colors block break-words">
                                            {{ $version->document->title }}
                                        </a>
                                        <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                            @if($version->document->document_number)
                                                <span class="text-[11px] font-mono text-base-content/60 bg-base-200 px-1.5 py-0.5 rounded">{{ $version->document->document_number }}</span>
                                            @endif
                                            @if($version->document->branch)
                                                <span class="text-[11px] text-base-content/60 bg-base-200/80 px-1.5 py-0.5 rounded">{{ $version->document->branch->name }}</span>
                                            @endif
                                            @if($version->document->division)
                                                <span class="text-[11px] text-base-content/60 bg-base-200/80 px-1.5 py-0.5 rounded">{{ $version->document->division->name }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="badge badge-warning badge-sm font-semibold">v{{ $version->version_number }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $version->author_name }}</div>
                                        <div class="text-xs text-base-content/50">{{ $version->author?->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        {{ $version->created_at ? $version->created_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Approve Version Action Button --}}
                                            <button type="button" 
                                                    onclick="document.getElementById('approve-doc-modal-{{ $version->id }}').showModal()" 
                                                    class="btn btn-success btn-xs gap-1 font-medium text-white shadow-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {{ __('Approve Version') }}
                                            </button>

                                            {{-- Custom Approve Version Modal --}}
                                            <dialog id="approve-doc-modal-{{ $version->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                                <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                    <div class="p-6 pb-4">
                                                        <div class="flex items-start justify-between gap-4">
                                                            <div class="flex items-center gap-3.5">
                                                                <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Versi Dokumen') }}</h3>
                                                                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Setujui versi ini (v:version) agar resmi dipublikasikan.', ['version' => $version->version_number]) }}</p>
                                                                </div>
                                                            </div>
                                                            <button type="button" onclick="document.getElementById('approve-doc-modal-{{ $version->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                ✕
                                                            </button>
                                                        </div>

                                                        <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                            <div class="min-w-0 flex-1">
                                                                <span class="font-semibold text-sm text-base-content break-words">{{ $version->document->title }}</span>
                                                                <p class="text-xs text-base-content/60 mt-1">
                                                                    {{ __('Penulis Versi') }}: <span class="font-medium text-base-content/80">{{ $version->author_name }}</span> &bull; <span class="badge badge-sm badge-ghost font-mono">v{{ $version->version_number }}</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <form method="POST" action="{{ route('approvals.approve', [$version->document, $version]) }}">
                                                        @csrf
                                                        <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                            <button type="button" onclick="document.getElementById('approve-doc-modal-{{ $version->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                {{ __('Batal') }}
                                                            </button>
                                                            <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                {{ __('Approve Version') }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <form method="dialog" class="modal-backdrop">
                                                    <button>{{ __('Batal') }}</button>
                                                </form>
                                            </dialog>

                                            {{-- Reject Version Action Button --}}
                                            <button type="button" 
                                                    onclick="document.getElementById('reject-doc-modal-{{ $version->id }}').showModal()" 
                                                    class="btn btn-error btn-outline btn-xs gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                {{ __('Reject Version') }}
                                            </button>

                                            {{-- Reject Reason Modal --}}
                                            <dialog id="reject-doc-modal-{{ $version->id }}" class="modal modal-bottom sm:modal-middle text-left backdrop-blur-xs text-base-content">
                                                <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg text-base-content">
                                                    <div class="p-6 pb-4">
                                                        <div class="flex items-start justify-between gap-4">
                                                            <div class="flex items-center gap-3.5">
                                                                <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Versi Dokumen') }}</h3>
                                                                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Tolak versi ini (v:version) dan berikan catatan kepada pembuat dokumen.', ['version' => $version->version_number]) }}</p>
                                                                </div>
                                                            </div>
                                                            <button type="button" onclick="document.getElementById('reject-doc-modal-{{ $version->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                ✕
                                                            </button>
                                                        </div>

                                                        <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60">
                                                            <span class="font-semibold text-sm text-base-content block break-words">{{ $version->document->title }}</span>
                                                            <p class="text-xs text-base-content/60 mt-1">
                                                                {{ __('Penulis') }}: <span class="font-medium text-base-content/80">{{ $version->author_name }}</span> &bull; <span class="badge badge-sm badge-ghost font-mono">v{{ $version->version_number }}</span>
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <form method="POST" action="{{ route('approvals.reject', [$version->document, $version]) }}">
                                                        @csrf
                                                        <div class="px-6 pb-4">
                                                            <label class="block text-xs font-semibold text-base-content/80 mb-1.5">
                                                                {{ __('Alasan Penolakan') }} <span class="text-base-content/40 font-normal">({{ __('Opsional') }})</span>
                                                            </label>
                                                            <textarea name="notes" rows="3" class="textarea textarea-bordered w-full text-xs text-base-content rounded-xl focus:textarea-primary bg-base-200/30" placeholder="{{ __('Jelaskan alasan penolakan agar penulis dapat melakukan revisi...') }}"></textarea>
                                                        </div>
                                                        <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                            <button type="button" onclick="document.getElementById('reject-doc-modal-{{ $version->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                {{ __('Batal') }}
                                                            </button>
                                                            <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                                {{ __('Reject Version') }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <form method="dialog" class="modal-backdrop">
                                                    <button>{{ __('Batal') }}</button>
                                                </form>
                                            </dialog>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($pendingVersions->hasPages())
                    <div class="px-5 py-4 border-t border-base-200">
                        {{ $pendingVersions->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- Bulk Approve Modal --}}
        <dialog id="bulk-approve-versions-modal" class="modal modal-bottom sm:modal-middle text-left backdrop-blur-xs">
            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Approve Selected Versions') }}</h3>
                                <p class="text-xs text-base-content/60 mt-0.5">
                                    {{ __('Setujui sekaligus semua versi dokumen yang Anda pilih.') }}
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('bulk-approve-versions-modal').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content">✕</button>
                    </div>

                    <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60">
                        <p class="text-xs text-base-content/80">
                            {{ __('Total versi dokumen yang dipilih:') }} <strong class="text-primary font-bold" x-text="selectedVersions.length"></strong>
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('approvals.bulk-approve-versions') }}">
                    @csrf
                    <template x-for="id in selectedVersions" :key="id">
                        <input type="hidden" name="version_ids[]" :value="id" />
                    </template>
                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="document.getElementById('bulk-approve-versions-modal').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Approve Selected Versions') }}
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>{{ __('Batal') }}</button>
            </form>
        </dialog>

        {{-- Bulk Reject Modal --}}
        <dialog id="bulk-reject-versions-modal" class="modal modal-bottom sm:modal-middle text-left backdrop-blur-xs text-base-content">
            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg text-base-content">
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Reject Selected Versions') }}</h3>
                                <p class="text-xs text-base-content/60 mt-0.5">
                                    {{ __('Tolak sekaligus versi dokumen yang dipilih dan berikan alasan penolakan.') }}
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('bulk-reject-versions-modal').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content">✕</button>
                    </div>

                    <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60">
                        <p class="text-xs text-base-content/80">
                            {{ __('Total versi dokumen yang dipilih:') }} <strong class="text-error font-bold" x-text="selectedVersions.length"></strong>
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('approvals.bulk-reject-versions') }}">
                    @csrf
                    <template x-for="id in selectedVersions" :key="id">
                        <input type="hidden" name="version_ids[]" :value="id" />
                    </template>
                    <div class="px-6 pb-4">
                        <label class="block text-xs font-semibold text-base-content/80 mb-1.5">
                            {{ __('Alasan Penolakan Massal') }}
                        </label>
                        <textarea name="notes" rows="3" class="textarea textarea-bordered w-full text-xs text-base-content rounded-xl focus:textarea-error bg-base-200/30" placeholder="{{ __('Jelaskan alasan penolakan massal...') }}" required></textarea>
                    </div>
                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="document.getElementById('bulk-reject-versions-modal').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ __('Reject Selected Versions') }}
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>{{ __('Batal') }}</button>
            </form>
        </dialog>
    </div>
</x-app-layout>
