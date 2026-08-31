<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-xl font-bold text-base-content leading-tight">
                    {{ __('Persetujuan Dokumen') }}
                </h2>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ __('Kelola permintaan persetujuan versi dokumen dan rollback divisi') }}
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="badge badge-primary badge-outline gap-1 text-xs py-2.5 px-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $pendingVersions->count() + $pendingRollbacks->count() }} {{ __('Menunggu') }}
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

        {{-- Pending Rollback Requests Section --}}
        @if($pendingRollbacks->count())
            <div class="card bg-base-100 border border-warning/30 shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-warning/10 border-b border-warning/20 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-warning/20 text-warning flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                                {{ __('Permintaan Rollback Dokumen') }}
                                <span class="badge badge-warning badge-sm font-semibold">{{ $pendingRollbacks->count() }}</span>
                            </h3>
                            <p class="text-xs text-base-content/60">
                                {{ __('Permintaan dari staf untuk mengembalikan dokumen ke versi sebelumnya.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[640px]">
                        <thead>
                            <tr class="bg-base-200/40 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-5">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Target Versi') }}</th>
                                <th class="py-3 px-4">{{ __('Diajukan Oleh') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($pendingRollbacks as $doc)
                                <tr class="hover:bg-base-200/40 transition-colors">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('documents.show', $doc) }}" class="font-semibold text-sm text-base-content hover:text-primary transition-colors block break-words">
                                            {{ $doc->title }}
                                        </a>
                                        @if($doc->document_number)
                                            <span class="text-xs font-mono text-base-content/50 block mt-0.5">{{ $doc->document_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="badge badge-warning badge-sm font-semibold">v{{ $doc->pendingRollbackVersion->version_number }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $doc->rollbackRequestedBy?->name ?? '—' }}</div>
                                        <div class="text-xs text-base-content/50">{{ $doc->rollbackRequestedBy?->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        {{ $doc->rollback_requested_at ? $doc->rollback_requested_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <form method="POST" action="{{ route('approvals.rollback-request.approve', $doc) }}" class="inline">
                                                @csrf
                                                <button class="btn btn-success btn-xs gap-1 font-medium" onclick="return confirm('{{ __('Setujui permintaan rollback ke versi v:version? Semua versi setelahnya akan dihapus permanen.', ['version' => $doc->pendingRollbackVersion->version_number]) }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    {{ __('Approve') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('approvals.rollback-request.reject', $doc) }}" class="inline">
                                                @csrf
                                                <button class="btn btn-error btn-outline btn-xs gap-1 font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    {{ __('Reject') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Pending Document Versions Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Menunggu Persetujuan Versi Dokumen') }}
                            <span class="badge badge-primary badge-sm font-semibold">{{ $pendingVersions->count() }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Daftar pembaruan konten dan draf revisi yang diajukan untuk disetujui.') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($pendingVersions->isEmpty())
                <div class="py-12 text-center text-base-content/50 space-y-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium">{{ __('Semua dokumen telah ditinjau') }}</p>
                    <p class="text-xs text-base-content/40">{{ __('Tidak ada versi dokumen yang sedang menunggu persetujuan.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[640px]">
                        <thead>
                            <tr class="bg-base-200/40 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-5">{{ __('Dokumen') }}</th>
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
                                    <td class="px-5 py-4">
                                        <a href="{{ route('documents.show', $version->document) }}" class="font-semibold text-sm text-base-content hover:text-primary transition-colors block break-words">
                                            {{ $version->document->title }}
                                        </a>
                                        @if($version->document->document_number)
                                            <span class="text-xs font-mono text-base-content/50 block mt-0.5">{{ $version->document->document_number }}</span>
                                        @endif
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
                                            <form method="POST" action="{{ route('approvals.approve', [$version->document, $version]) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-xs gap-1 font-medium" onclick="return confirm('{{ __('Setujui versi ini (v:version)?', ['version' => $version->version_number]) }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    {{ __('Approve') }}
                                                </button>
                                            </form>

                                            <button type="button" onclick="document.getElementById('reject-doc-modal-{{ $version->id }}').showModal()" class="btn btn-error btn-outline btn-xs gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                {{ __('Reject') }}
                                            </button>
                                        </div>

                                        {{-- Reject Reason Modal --}}
                                        <dialog id="reject-doc-modal-{{ $version->id }}" class="modal text-left">
                                            <div class="modal-box">
                                                <h3 class="font-bold text-lg text-base-content">{{ __('Tolak Versi Dokumen') }}</h3>
                                                <p class="py-2 text-sm text-base-content/70">
                                                    {!! __('Tolak versi :version dari dokumen :doc.', ['version' => '<strong>v'.$version->version_number.'</strong>', 'doc' => '<strong>'.$version->document->title.'</strong>']) !!}
                                                </p>
                                                <form method="POST" action="{{ route('approvals.reject', [$version->document, $version]) }}">
                                                    @csrf
                                                    <div class="form-control mb-4">
                                                        <label class="label">
                                                            <span class="label-text font-medium">{{ __('Catatan / Alasan Penolakan (Opsional)') }}</span>
                                                        </label>
                                                        <textarea name="notes" class="textarea textarea-bordered w-full text-sm" rows="3" placeholder="{{ __('Tuliskan alasan penolakan atau catatan revisi...') }}"></textarea>
                                                    </div>
                                                    <div class="modal-action">
                                                        <button type="button" onclick="document.getElementById('reject-doc-modal-{{ $version->id }}').close()" class="btn btn-ghost">{{ __('Batal') }}</button>
                                                        <button type="submit" class="btn btn-error">{{ __('Tolak Versi') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                            <form method="dialog" class="modal-backdrop">
                                                <button>close</button>
                                            </form>
                                        </dialog>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
