<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-base-content leading-tight">
                    {{ __('Rename Approval') }}
                </h2>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ __('Tinjau dan setujui permintaan perubahan nama atau judul dokumen dari staf.') }}
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="badge {{ ($counts['renames'] ?? $pendingRenames->total()) > 0 ? 'badge-warning font-bold text-amber-900' : 'badge-ghost' }} gap-1.5 text-xs py-2.5 px-3 font-semibold shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ $counts['renames'] ?? $pendingRenames->total() }} {{ __('Menunggu') }}
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ __('Daftar Permintaan Ubah Nama Dokumen') }}
                    </span>
                    <span class="badge badge-sm badge-warning font-bold text-amber-900">{{ $pendingRenames->total() }}</span>
                </div>

                {{-- Search Box & Per-Page Controls --}}
                <form method="GET" action="{{ route('approvals.renames') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full lg:w-auto justify-end">
                    <div class="relative w-full sm:w-80 md:w-96 min-w-[240px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="search" 
                               value="{{ $search ?? '' }}" 
                               placeholder="{{ __('Cari judul saat ini, judul baru, pemohon...') }}" 
                               class="input input-sm input-bordered w-full pl-9 pr-8 text-sm rounded-xl bg-base-200/50 focus:bg-base-100 transition-colors" />
                        @if(!empty($search))
                            <a href="{{ route('approvals.renames', request()->except('search')) }}" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-base-content/40 hover:text-base-content transition-colors" title="{{ __('Hapus pencarian') }}">
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

        {{-- Pending Document Rename Requests Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-base-200/50 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Rename Approval') }}
                            <span class="badge badge-warning font-semibold text-amber-900 badge-sm">{{ $pendingRenames->total() }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Permintaan dari staf untuk memperbarui nama atau judul dokumen resmi.') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($pendingRenames->isEmpty())
                <div class="py-12 text-center text-base-content/50 space-y-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium">{{ __('Tidak ada permintaan ubah nama dokumen') }}</p>
                    <p class="text-xs text-base-content/40">{{ __('Semua permintaan perubahan nama dokumen telah ditinjau atau belum ada pengajuan baru.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[720px]">
                        <thead>
                            <tr class="bg-base-200/50 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-5">{{ __('Dokumen Saat Ini') }}</th>
                                <th class="py-3 px-4">{{ __('Nama Baru yang Diajukan') }}</th>
                                <th class="py-3 px-4">{{ __('Diajukan Oleh') }}</th>
                                <th class="py-3 px-4">{{ __('Alasan Perubahan') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu Pengajuan') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($pendingRenames as $doc)
                                <tr class="hover:bg-base-200/40 transition-colors">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('documents.show', $doc) }}" class="font-semibold text-sm text-base-content hover:text-primary transition-colors block break-words">
                                            {{ $doc->title }}
                                        </a>
                                        <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                            @if($doc->document_number)
                                                <span class="text-[11px] font-mono text-base-content/60 bg-base-200 px-1.5 py-0.5 rounded">{{ $doc->document_number }}</span>
                                            @endif
                                            @if($doc->branch)
                                                <span class="text-[11px] text-base-content/60 bg-base-200/80 px-1.5 py-0.5 rounded">{{ $doc->branch->name }}</span>
                                            @endif
                                            @if($doc->division)
                                                <span class="text-[11px] text-base-content/60 bg-base-200/80 px-1.5 py-0.5 rounded">{{ $doc->division->name }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 max-w-xs">
                                        <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-900 font-semibold text-xs break-words leading-relaxed max-h-24 overflow-y-auto" title="{{ $doc->pending_title }}">
                                            <div class="flex items-start gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>{{ $doc->pending_title }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $doc->renameRequestedBy?->name ?? '—' }}</div>
                                        <div class="text-xs text-base-content/50">{{ $doc->renameRequestedBy?->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/70 max-w-xs break-words">
                                        {{ $doc->rename_request_notes ?? '—' }}
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        {{ $doc->rename_requested_at ? $doc->rename_requested_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @can('approveRename', $doc)
                                                {{-- Approve Name Action Button --}}
                                                <button type="button" 
                                                        onclick="document.getElementById('approve-rename-modal-{{ $doc->id }}').showModal()" 
                                                        class="btn btn-success btn-xs gap-1 font-medium text-white shadow-xs">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    {{ __('Approve Name') }}
                                                </button>

                                                {{-- Approve Rename Modal --}}
                                                <dialog id="approve-rename-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                        <div class="p-6 pb-4">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div class="flex items-center gap-3.5">
                                                                    <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                        </svg>
                                                                    </div>
                                                                    <div>
                                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Perubahan Nama') }}</h3>
                                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Ubah judul dokumen ini secara resmi menjadi judul baru.') }}</p>
                                                                    </div>
                                                                </div>
                                                                <button type="button" onclick="document.getElementById('approve-rename-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                    ✕
                                                                </button>
                                                            </div>

                                                            <div class="mt-4 space-y-2.5">
                                                                <div class="p-3 rounded-xl bg-base-200/60 border border-base-300/60">
                                                                    <span class="text-[11px] font-semibold text-base-content/50 uppercase tracking-wider block mb-1">{{ __('Judul Saat Ini') }}</span>
                                                                    <span class="font-medium text-sm text-base-content/70 line-through block break-words">{{ $doc->title }}</span>
                                                                </div>
                                                                <div class="p-3 rounded-xl bg-success/10 border border-success/30">
                                                                    <span class="text-[11px] font-bold text-success uppercase tracking-wider block mb-1">{{ __('Judul Baru yang Disetujui') }}</span>
                                                                    <span class="font-bold text-sm text-base-content block break-words">{{ $doc->pending_title }}</span>
                                                                </div>
                                                                <p class="text-xs text-base-content/60 pt-1">
                                                                    {{ __('Diajukan oleh') }}: <span class="font-medium text-base-content/80">{{ $doc->renameRequestedBy?->name ?? '—' }}</span>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <form method="POST" action="{{ route('approvals.rename-request.approve', $doc) }}">
                                                            @csrf
                                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                                <button type="button" onclick="document.getElementById('approve-rename-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                    {{ __('Batal') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                    {{ __('Approve Name') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <form method="dialog" class="modal-backdrop">
                                                        <button>{{ __('Batal') }}</button>
                                                    </form>
                                                </dialog>

                                                {{-- Reject Name Action Button --}}
                                                <button type="button" 
                                                        onclick="document.getElementById('reject-rename-modal-{{ $doc->id }}').showModal()" 
                                                        class="btn btn-error btn-outline btn-xs gap-1 font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    {{ __('Reject Name') }}
                                                </button>

                                                {{-- Reject Rename Modal --}}
                                                <dialog id="reject-rename-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs text-base-content">
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
                                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Perubahan Nama') }}</h3>
                                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Tolak permintaan ini dan berikan alasan kepada pemohon.') }}</p>
                                                                    </div>
                                                                </div>
                                                                <button type="button" onclick="document.getElementById('reject-rename-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                    ✕
                                                                </button>
                                                            </div>

                                                            <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 space-y-1">
                                                                <div class="text-xs text-base-content/60">
                                                                    {{ __('Judul baru yang ditolak:') }} <strong class="text-error font-semibold">{{ $doc->pending_title }}</strong>
                                                                </div>
                                                                <div class="text-xs text-base-content/60">
                                                                    {{ __('Pemohon:') }} <span class="font-medium text-base-content/80">{{ $doc->renameRequestedBy?->name ?? '—' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <form method="POST" action="{{ route('approvals.rename-request.reject', $doc) }}">
                                                            @csrf
                                                            <div class="px-6 pb-4">
                                                                <label class="block text-xs font-semibold text-base-content/80 mb-1.5">
                                                                    {{ __('Alasan Penolakan') }} <span class="text-base-content/40 font-normal">({{ __('Opsional') }})</span>
                                                                </label>
                                                                <textarea name="notes" rows="3" class="textarea textarea-bordered w-full text-xs text-base-content rounded-xl focus:textarea-primary bg-base-200/30" placeholder="{{ __('Jelaskan alasan penolakan perubahan nama...') }}"></textarea>
                                                            </div>
                                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                                <button type="button" onclick="document.getElementById('reject-rename-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                    {{ __('Batal') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                    {{ __('Reject Name') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <form method="dialog" class="modal-backdrop">
                                                        <button>{{ __('Batal') }}</button>
                                                    </form>
                                                </dialog>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($pendingRenames->hasPages())
                    <div class="px-5 py-4 border-t border-base-200">
                        {{ $pendingRenames->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
