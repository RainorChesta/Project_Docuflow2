<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-base-content leading-tight">
                    {{ __('Rollback Approval') }}
                </h2>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ __('Tinjau dan setujui permintaan pengembalian versi dokumen dari staf.') }}
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="badge {{ ($counts['rollbacks'] ?? $pendingRollbacks->total()) > 0 ? 'badge-secondary font-bold text-white' : 'badge-ghost' }} gap-1.5 text-xs py-2.5 px-3 font-semibold shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ $counts['rollbacks'] ?? $pendingRollbacks->total() }} {{ __('Menunggu') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ __('Daftar Permintaan Rollback Dokumen') }}
                    </span>
                    <span class="badge badge-sm badge-secondary font-bold text-white">{{ $pendingRollbacks->total() }}</span>
                </div>

                {{-- Search Box & Per-Page Controls --}}
                <form method="GET" action="{{ route('approvals.rollbacks') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full lg:w-auto justify-end">
                    <div class="relative w-full sm:w-80 md:w-96 min-w-[240px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="search" 
                               value="{{ $search ?? '' }}" 
                               placeholder="{{ __('Cari judul, nomor dokumen, pemohon, versi...') }}" 
                               class="input input-sm input-bordered w-full pl-9 pr-8 text-sm rounded-xl bg-base-200/50 focus:bg-base-100 transition-colors" />
                        @if(!empty($search))
                            <a href="{{ route('approvals.rollbacks', request()->except('search')) }}" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-base-content/40 hover:text-base-content transition-colors" title="{{ __('Hapus pencarian') }}">
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

        {{-- Pending Document Rollback Requests Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-base-200/50 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Permintaan Rollback Dokumen') }}
                            <span class="badge badge-secondary badge-sm font-semibold text-white">{{ $pendingRollbacks->total() }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Permintaan dari staf untuk mengembalikan dokumen ke versi sebelumnya.') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($pendingRollbacks->isEmpty())
                <div class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-3xl bg-base-200/70 border border-base-300/60 flex items-center justify-center mx-auto mb-3.5 shadow-2xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-sm text-base-content mb-1">{{ __('Semua Permintaan Rollback Selesai!') }}</h4>
                    <p class="text-xs text-base-content/50 max-w-sm mx-auto leading-relaxed">
                        @if(!empty($search))
                            {{ __('Tidak ada permintaan rollback yang cocok dengan kata kunci pencarian Anda.') }}
                        @else
                            {{ __('Saat ini tidak ada permintaan rollback yang menunggu persetujuan Anda.') }}
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full text-sm">
                        <thead>
                            <tr class="bg-base-200/40 border-b border-base-300 text-base-content/70 text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Versi Target') }}</th>
                                <th class="py-3 px-4">{{ __('Pemohon') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu Pengajuan') }}</th>
                                <th class="py-3 px-4 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-300">
                            @foreach($pendingRollbacks as $doc)
                                <tr class="hover:bg-base-200/30 transition-colors">
                                    {{-- Document Info --}}
                                    <td class="py-3.5 px-4 min-w-[260px]">
                                        <div class="font-bold text-base-content leading-snug hover:text-primary transition-colors">
                                            <a href="{{ route('documents.show', $doc) }}">
                                                {{ $doc->title }}
                                            </a>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                            <span class="badge badge-ghost badge-xs font-mono font-medium text-base-content/70 border-base-300">
                                                {{ $doc->document_number ?? '—' }}
                                            </span>
                                            @if($doc->division)
                                                <span class="badge badge-ghost badge-xs text-base-content/60">
                                                    {{ $doc->division->name }}
                                                </span>
                                            @endif
                                            @if($doc->branch)
                                                <span class="badge badge-outline badge-xs text-base-content/50">
                                                    {{ $doc->branch->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Target Version --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="badge badge-secondary font-bold text-xs text-white">
                                                v{{ $doc->pendingRollbackVersion?->version_number ?? '?' }}
                                            </span>
                                            @if($doc->currentVersion)
                                                <span class="text-xs text-base-content/50">
                                                    ({{ __('dari') }} <span class="font-semibold text-base-content/70">v{{ $doc->currentVersion->version_number }}</span>)
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Requester --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-medium text-base-content text-xs">
                                            {{ $doc->rollbackRequestedBy?->name ?? '—' }}
                                        </div>
                                        <div class="text-[11px] text-base-content/50 mt-0.5">
                                            {{ $doc->rollbackRequestedBy?->email ?? '' }}
                                        </div>
                                    </td>

                                    {{-- Submission Time --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap text-xs text-base-content/60">
                                        {{ $doc->rollback_requested_at ? $doc->rollback_requested_at->diffForHumans() : ($doc->updated_at ? $doc->updated_at->diffForHumans() : '—') }}
                                    </td>

                                    {{-- Action Buttons --}}
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Preview Document Button --}}
                                            <a href="{{ route('documents.show', $doc) }}" 
                                               class="btn btn-ghost btn-xs text-base-content/70 hover:text-base-content" 
                                               title="{{ __('Lihat Detail') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                {{ __('Detail') }}
                                            </a>

                                            {{-- Approve Button --}}
                                            <button type="button" onclick="document.getElementById('approve-rollback-modal-{{ $doc->id }}').showModal()" class="btn btn-success btn-xs text-white gap-1 font-semibold shadow-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {{ __('Approve') }}
                                            </button>

                                            {{-- Approve Rollback Modal --}}
                                            <dialog id="approve-rollback-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                                <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                    <div class="p-6 pb-4">
                                                        <div class="flex items-start justify-between gap-4">
                                                            <div class="flex items-center gap-3.5">
                                                                <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Permintaan Rollback') }}</h3>
                                                                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Kembalikan dokumen ke versi v:version.', ['version' => $doc->pendingRollbackVersion?->version_number ?? '']) }}</p>
                                                                </div>
                                                            </div>
                                                            <button type="button" onclick="document.getElementById('approve-rollback-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                ✕
                                                            </button>
                                                        </div>

                                                        <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                            <div class="min-w-0 flex-1">
                                                                <span class="font-semibold text-sm text-base-content break-words">{{ $doc->title }}</span>
                                                                <p class="text-xs text-base-content/60 mt-1">
                                                                    {{ __('Pemohon') }}: <span class="font-medium text-base-content/80">{{ $doc->rollbackRequestedBy?->name ?? '—' }}</span>
                                                                </p>
                                                                <p class="text-xs text-error mt-2 font-medium">
                                                                    ⚠️ {{ __('Peringatan: Seluruh versi setelah v:version akan dihapus secara permanen jika disetujui.', ['version' => $doc->pendingRollbackVersion?->version_number ?? '']) }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <form method="POST" action="{{ route('approvals.rollback-request.approve', $doc) }}">
                                                        @csrf
                                                        <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                            <button type="button" onclick="document.getElementById('approve-rollback-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                {{ __('Batal') }}
                                                            </button>
                                                            <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                {{ __('Ya, Setujui Rollback') }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <form method="dialog" class="modal-backdrop">
                                                    <button>{{ __('Batal') }}</button>
                                                </form>
                                            </dialog>

                                            {{-- Reject Button --}}
                                            <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $doc->id }}').showModal()" class="btn btn-error btn-outline btn-xs gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                {{ __('Reject') }}
                                            </button>

                                            {{-- Reject Rollback Modal --}}
                                            <dialog id="reject-rollback-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs text-base-content">
                                                <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg text-base-content">
                                                    <div class="p-6 pb-4">
                                                        <div class="flex items-start justify-between gap-4">
                                                            <div class="flex items-center gap-3.5">
                                                                <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Permintaan Rollback') }}</h3>
                                                                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Dokumen akan tetap berada pada versi terkini.') }}</p>
                                                                </div>
                                                            </div>
                                                            <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                ✕
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <form method="POST" action="{{ route('approvals.rollback-request.reject', $doc) }}">
                                                        @csrf
                                                        <div class="px-6 pb-5 space-y-2">
                                                            <label for="reject-rollback-notes-{{ $doc->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                                {{ __('Alasan Penolakan (Wajib)') }} <span class="text-error">*</span>
                                                            </label>
                                                            <textarea 
                                                                id="reject-rollback-notes-{{ $doc->id }}" 
                                                                name="notes" 
                                                                rows="3" 
                                                                required 
                                                                placeholder="{{ __('Tuliskan alasan mengapa permintaan rollback ini ditolak...') }}"
                                                                class="textarea textarea-bordered w-full text-xs rounded-xl focus:outline-hidden focus:border-error focus:ring-1 focus:ring-error transition-all"></textarea>
                                                        </div>

                                                        <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                            <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                {{ __('Batal') }}
                                                            </button>
                                                            <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                                {{ __('Tolak Rollback') }}
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

                {{-- Pagination Footer --}}
                @if($pendingRollbacks->hasPages())
                    <div class="px-5 py-4 border-t border-base-300 bg-base-100 flex items-center justify-between">
                        {{ $pendingRollbacks->links('vendor.pagination.dokuflow') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
