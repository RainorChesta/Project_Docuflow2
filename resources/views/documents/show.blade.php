<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-2 justify-between">
            <span class="min-w-0 truncate">{{ $document->title }}</span>
            <span class="text-sm font-normal text-base-content/60 shrink-0">{{ $document->document_number }}</span>
        </div>
    </x-slot>

    @if(!auth()->user()->isAdmin() && !auth()->user()->isHead())
        <x-confirm-modal
            name="confirm-discard-{{ $document->id }}"
            title="Discard Document?"
            message="Are you sure you want to discard this document?"
            :action="route('documents.discard', $document)"
            method="POST"
            confirmLabel="Discard"
        />
    @endif

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full">
            @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Pending Rollback Banner -->
            @if($document->hasPendingRollback())
                <div class="alert alert-warning mb-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start sm:items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                            <div>
                                <p class="font-semibold text-sm">Permintaan rollback ke v{{ $document->pendingRollbackVersion->version_number }}</p>
                                <p class="text-xs text-base-content/70">
                                    Diajukan oleh {{ $document->rollbackRequestedBy?->name ?? '—' }}.
                                    Versi setelah v{{ $document->pendingRollbackVersion->version_number }} akan dihapus permanen jika disetujui.
                                </p>
                            </div>
                        </div>
                        @can('approve', $document)
                            <div class="flex flex-wrap gap-2 shrink-0">
                                <form method="POST" action="{{ route('approvals.rollback-request.approve', $document) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-success btn-sm" onclick="return confirm('Yakin? Versi setelah v{{ $document->pendingRollbackVersion->version_number }} akan dihapus permanen dan tidak bisa dikembalikan.')">Approve Rollback</button>
                                </form>
                                <form method="POST" action="{{ route('approvals.rollback-request.reject', $document) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-error btn-sm">Reject</button>
                                </form>
                            </div>
                        @endcan
                    </div>
                </div>
            @endif

            <!-- Pending Banner (paling atas) -->
            @php $pendingVersion = $document->versions->firstWhere('status', 'pending'); @endphp
            @if($pendingVersion)
                <div class="alert alert-warning mb-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start sm:items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <p class="font-semibold text-sm">Pending approval (v{{ $pendingVersion->version_number }})</p>
                                <p class="text-xs text-base-content/70">Versi menunggu review oleh kepala divisi.</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 shrink-0">
                            @can('update', $document)
                                @if(auth()->user()->isAdmin() || auth()->user()->isHead())
                                    <form method="POST" action="{{ route('documents.discard', $document) }}" class="inline">
                                        @csrf
                                        <button class="btn btn-outline btn-warning btn-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            Discard
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-outline btn-warning btn-xs" x-on:click="$dispatch('open-modal', 'confirm-discard-{{ $document->id }}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        Discard
                                    </button>
                                @endif
                            @endcan
                            @can('approve', $document)
                                <form method="POST" action="{{ route('approvals.approve', [$document, $pendingVersion]) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-success btn-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('approvals.reject', [$document, $pendingVersion]) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-error btn-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        Reject
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @endif

            <!-- Metadata -->
            @php
                $hasDraft = $document->versions->contains('status', 'draft');
            @endphp
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-200 pb-4">
                        <h1 class="text-xl font-bold text-base-content truncate min-w-0">{{ $document->title }}</h1>
                        <span class="badge badge-outline badge-sm shrink-0">{{ $document->document_number }}</span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-5 pt-4 text-sm">
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">Division</span>
                            <p class="font-medium mt-1">{{ $document->division?->code ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">Owner</span>
                            <p class="font-medium mt-1">{{ $document->owner->name }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">Status</span>
                            <p class="font-medium mt-1">
                                @if($document->currentVersion)
                                    Active (v{{ $document->currentVersion->version_number }})
                                @elseif($pendingVersion)
                                    <span class="text-warning">Pending approval (v{{ $pendingVersion->version_number }})</span>
                                @elseif($hasDraft)
                                    <span class="text-warning">Draft</span>
                                @else
                                    <span class="text-warning">Pending first approval</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">Visibility</span>
                            <p class="font-medium mt-1">
                                @if($document->isGeneral())
                                    <span class="badge badge-success badge-sm">General</span>
                                @elseif($document->isPersonal())
                                    <span class="badge badge-info badge-sm">Personal</span>
                                @else
                                    <span class="badge badge-neutral badge-sm">{{ $document->division?->code ?? 'Division' }} only</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Actions (di bawah keterangan, sejajar menyamping) --}}
                    @php $isFileBased = $document->displayVersion()?->file_path; @endphp
                    <div class="flex flex-wrap items-center gap-2 mt-5 pt-4 border-t border-base-200">
                        @can('update', $document)
                            @if($isFileBased)
                                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('edit-restricted-modal').showModal()">
                                    Edit Document
                                </button>
                            @elseif($hasDraft && !$pendingVersion && !$document->currentVersion)
                                <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary btn-sm">
                                    Edit Draft
                                </a>
                            @else
                                <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary btn-sm">
                                    Edit Document
                                </a>
                            @endif
                        @endcan
                        @can('update', $document)
                            <button type="button" onclick="document.getElementById('link-form').showModal()" class="btn btn-outline btn-primary btn-sm">
                                Share Link
                            </button>
                        @endcan
                        @can('manageAccess', $document)
                            <button type="button" onclick="openShareModal()" class="btn btn-outline btn-primary btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 0a3 3 0 11-5.367 2.684 3 3 0 015.367-2.684z" /></svg>
                                Bagikan
                            </button>
                        @endcan

                        <button
                            type="button"
                            class="btn btn-ghost btn-sm border border-base-300"
                            onclick="document.getElementById('version-modal').showModal()"
                        >
                            Lihat Versi ({{ $document->versions->count() }})
                        </button>

                        @can('update', $document)
                            <button type="button" class="btn btn-ghost btn-sm border border-base-300" onclick="document.getElementById('scope-modal').showModal()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                Change scope
                            </button>
                        @endcan

                        {{-- Export to PDF (hanya untuk dokumen hasil editor) —
                             buka modal supaya ukuran kertas bisa dipilih dulu
                             sebelum export, terpisah dari paper_size tersimpan
                             di dokumen (lihat #export-pdf-modal). --}}
                        @if(!$isFileBased)
                            <button type="button" class="btn btn-ghost btn-sm border border-base-300"
                                    onclick="document.getElementById('export-pdf-modal').showModal()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Export PDF
                            </button>
                        @endif
                    </div>

                    @if(session('pdf_export'))
                        <div class="alert alert-success mt-3">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 w-full">
                                <span>PDF berhasil dibuat. <span class="font-medium">{{ session('pdf_export.filename') }}</span></span>
                                <a href="{{ session('pdf_export.url') }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm shrink-0">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Content -->
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body p-0">
                    @php $display = $document->displayVersion(); @endphp
                    @if($display && $display->file_path)
                        @include('documents._file-preview', ['document' => $document, 'version' => $display])
                    @elseif($display)
                        @include('documents._paper', [
                            'content' => $display->content,
                            'liveStorage' => 'doc-preview-' . $document->id,
                            'paperSize' => $document->paper_size ?? 'A4',
                            'paperMargin' => $document->paper_margin,
                        ])
                    @else
                        <p class="text-base-content/60 italic p-4 sm:p-6">No approved content yet.</p>
                    @endif
                </div>
            </div>

            @if($errors->has('export'))
                <div class="alert alert-error mb-6">
                    <span>{{ $errors->first('export') }} Silakan coba lagi.</span>
                </div>
            @endif
            <!-- Share Link Modal -->
            <dialog id="link-form" class="modal">
                <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
                    <div class="flex flex-wrap items-center justify-between mb-4">
                        <h3 class="font-semibold">Generate Share Link</h3>
                        <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('link-form').close()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    @if(session('notice'))
                        <div class="alert alert-warning mb-4">
                            <span>{{ session('notice') }}</span>
                        </div>
                    @endif

                    @php
                        $activeLinks = $document->accessLinks->filter(fn($l) => !$l->isExpired());
                        $activeRole = fn($r) => $activeLinks->firstWhere('role', $r);
                    @endphp

                    <form method="POST" action="{{ route('links.store', $document) }}" class="flex flex-col sm:flex-row gap-3 sm:gap-2 sm:items-end">
                        @csrf
                        <div class="form-control">
                            <label class="label"><span class="label-text">Role</span></label>
                            <select name="role" class="select select-bordered w-full" required>
                                <option value="viewer" @disabled($activeRole('viewer'))>Viewer {{ $activeRole('viewer') ? '(active)' : '' }}</option>
                                <option value="editor" @disabled($activeRole('editor'))>Editor {{ $activeRole('editor') ? '(active)' : '' }}</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Expires (optional)</span></label>
                            <input type="date" name="expires_at" class="input input-bordered w-full" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Generate
                        </button>
                    </form>

                    @if($activeRole('viewer') || $activeRole('editor'))
                        <p class="mt-3 text-xs text-base-content/50">
                            Hanya satu link aktif per role. Role dengan link aktif tidak bisa digenerate lagi sampai link-nya dicabut (Revoke) atau kedaluwarsa.
                        </p>
                    @endif

                    @if($document->accessLinks->count())
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-base-content/70 mb-2">Active Links</h4>
                            @foreach($document->accessLinks as $link)
                                <div class="flex flex-wrap justify-between items-center gap-2 py-2 border-b border-base-200 text-sm">
                                    <button type="button"
                                            class="text-base-content/60 break-all max-w-full min-w-0 text-left hover:underline"
                                            onclick="openShareLinkModal('{{ route('shared.documents', $link->token) }}')"
                                            title="Klik untuk salin link">
                                        {{ route('shared.documents', $link->token) }}
                                    </button>
                                    <div class="flex flex-wrap gap-2 items-center shrink-0">
                                        <span class="badge {{ $link->role === 'editor' ? 'badge-primary' : 'badge-ghost' }} badge-sm">
                                            {{ $link->role }}
                                        </span>
                                        @if($link->expires_at)
                                            <span class="text-xs text-base-content/50">until {{ $link->expires_at->format('Y-m-d') }}</span>
                                        @else
                                            <span class="text-xs text-base-content/50">never</span>
                                        @endif
                                        <form method="POST" action="{{ route('links.destroy', [$document, $link]) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="text-error hover:underline text-xs inline-flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                Revoke
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>

            {{-- Bagikan Modal (Google Docs model) --}}
            <dialog id="share-modal" class="modal">
                <div class="modal-box max-w-xl max-h-[85vh] overflow-y-auto">
                    <div class="flex flex-wrap items-center justify-between mb-4">
                        <h3 class="font-semibold">Bagikan "{{ $document->title }}"</h3>
                        <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('share-modal').close()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Invite search --}}
                    <div class="form-control mb-2 relative">
                        <input id="share-search-input" type="text" placeholder="Cari nama pengguna atau divisi&hellip;"
                               class="input input-bordered w-full" autocomplete="off">
                        <div id="share-search-results" class="hidden absolute top-full left-0 right-0 z-10 mt-1 bg-base-100 border border-base-300 rounded-box shadow-lg max-h-64 overflow-y-auto"></div>
                    </div>
                    <p id="share-search-hint" class="text-xs text-base-content/50 mb-4">Tambahkan orang atau divisi untuk mengakses dokumen ini.</p>

                    {{-- People with access --}}
                    <div class="mb-5">
                        <h4 class="text-sm font-medium text-base-content/70 mb-2">Orang dengan akses</h4>
                        <div id="share-list" class="space-y-2 text-sm">
                            <div class="text-base-content/50 italic">Memuat&hellip;</div>
                        </div>
                    </div>

                    {{-- General access --}}
                    <div class="border-t border-base-200 pt-4">
                        <h4 class="text-sm font-medium text-base-content/70 mb-3">Akses umum</h4>
                        <div class="flex flex-col gap-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="general_access" value="restricted" class="radio radio-sm" onchange="updateGeneralAccess()">
                                <span class="text-sm">Restricted — hanya orang yang diundang</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="general_access" value="anyone_with_link" class="radio radio-sm" onchange="updateGeneralAccess()">
                                <span class="text-sm">Siapa saja yang punya link</span>
                            </label>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <button type="button" class="btn btn-outline btn-primary btn-sm" onclick="copyShareUrl()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    Salin Link
                                </button>
                                <button type="button" class="btn btn-ghost btn-sm" onclick="regenerateToken()">Buat link baru</button>
                            </div>
                        </div>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>

            {{-- Export PDF Modal — pilih ukuran kertas HANYA untuk export ini
                 (tidak mengubah paper_size tersimpan di dokumen). Margin ikut
                 margin dokumen; kalau tidak muat di kertas yang dipilih,
                 PdfExportService akan meng-clamp-nya otomatis (lihat
                 clampMarginToPage() di resources/js/jodit.js — logikanya
                 sengaja dibuat identik dengan PdfExportService::buildHtml()). --}}
            @if(!$isFileBased)
                <dialog id="export-pdf-modal" class="modal">
                <div class="modal-box max-w-sm max-h-[85vh] overflow-y-auto">
                        <div class="flex flex-wrap items-center justify-between mb-4">
                            <h3 class="font-semibold">Export ke PDF</h3>
                            <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('export-pdf-modal').close()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('documents.export-pdf', $document) }}"
                              onsubmit="this.querySelector('button[type=submit]').disabled = true;
                                        this.querySelector('button[type=submit]').classList.add('loading');
                                        this.querySelector('button[type=submit]').innerHTML = 'Membuat PDF&hellip;';
                                        return true;">
                            @csrf
                            <div class="form-control w-full mb-2">
                                <label class="label"><span class="label-text font-medium">Ukuran Kertas</span></label>
                                <select name="paper_size" class="select select-bordered w-full">
                                    @foreach(['A4','A5','A3','Letter','Legal'] as $size)
                                        <option value="{{ $size }}" {{ ($document->paper_size ?? 'A4') === $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-xs text-base-content/50 mb-4">
                                Margin tetap mengikuti margin dokumen saat ini; kalau tidak muat di kertas yang dipilih, margin akan disesuaikan otomatis.
                            </p>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('export-pdf-modal').close()">Batal</button>
                                <button type="submit" class="btn btn-primary btn-sm">Export</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
            @endif

        </div>
    </div>

    {{-- Share link modal (reusable) --}}
    <style>
        #share-link-modal::backdrop { background: rgba(0, 0, 0, 0.5); }
    </style>
    <dialog id="share-link-modal" class="modal">
        <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="font-semibold">Link Berhasil Dibuat</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('share-link-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <input id="share-link-input" type="text" readonly
                   class="input input-bordered w-full mb-4" onclick="this.select()">

            <div class="flex flex-wrap gap-2">
                <button type="button" id="share-link-copy-btn" class="btn btn-primary btn-sm" onclick="shareLinkCopy()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    Copy Link
                </button>
                <a id="share-link-email" href="mailto:?subject={{ rawurlencode('Link Dokumen: ' . $document->title) }}&body="
                   class="btn btn-neutral btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    Share Email
                </a>
                <a id="share-link-wa" href="https://wa.me/?text=" target="_blank" rel="noopener"
                   class="btn btn-success btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    Share WhatsApp
                </a>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <script>
        function openShareLinkModal(url) {
            document.getElementById('share-link-input').value = url;
            document.getElementById('share-link-email').href = 'mailto:?subject=' + encodeURIComponent('Link Dokumen: {{ $document->title }}') + '&body=' + encodeURIComponent(url);
            document.getElementById('share-link-wa').href = 'https://wa.me/?text=' + encodeURIComponent(url);
            document.getElementById('share-link-modal').showModal();
        }

        @if(session('share_link'))
            openShareLinkModal('{{ session('share_link') }}');
        @endif

        function shareLinkCopy() {
            const input = document.getElementById('share-link-input');
            const btn = document.getElementById('share-link-copy-btn');
            navigator.clipboard.writeText(input.value).then(() => {
                const original = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => { btn.textContent = original; }, 2000);
            });
        }

        @if($errors->has('file'))
            document.getElementById('upload-version-modal').showModal();
        @endif

        // ---- Bagikan modal (Google Docs model) ----
        const shareDataUrl = @json(route('shares.data', $document));
        const shareStoreUrl = @json(route('shares.store', $document));
        const shareSearchUrl = @json(route('shares.search', $document));
        const shareGeneralUrl = @json(route('shares.general-access.update', $document));
        const shareRegenUrl = @json(route('shares.regenerate-token', $document));
        let shareState = null;

        async function openShareModal() {
            document.getElementById('share-modal').showModal();
            await loadShareData();
        }

        async function loadShareData() {
            const list = document.getElementById('share-list');
            list.innerHTML = '<div class="text-base-content/50 italic">Memuat&hellip;</div>';
            try {
                const res = await fetch(shareDataUrl, { headers: { 'Accept': 'application/json' } });
                shareState = await res.json();
                renderShareList();
                renderGeneralAccess();
            } catch (e) {
                list.innerHTML = '<div class="text-error">Gagal memuat data akses.</div>';
            }
        }

        function renderShareList() {
            const list = document.getElementById('share-list');
            const rows = [];

            rows.push(`<div class="flex items-center justify-between gap-2 py-1">
                <div class="min-w-0">
                    <p class="font-medium truncate">${escapeHtml(shareState.owner.name)}</p>
                    <p class="text-xs text-base-content/50">Pemilik</p>
                </div>
                <span class="badge badge-primary badge-sm shrink-0">owner</span>
            </div>`);

            shareState.shares.forEach(s => {
                rows.push(`<div class="flex items-center justify-between gap-2 py-1">
                    <div class="min-w-0">
                        <p class="font-medium truncate">${escapeHtml(s.name)}</p>
                        <p class="text-xs text-base-content/50 truncate">${escapeHtml(s.email)}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <select class="select select-bordered select-xs" onchange="updateUserShare(${s.id}, this.value)">
                            <option value="viewer" ${s.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                            <option value="editor" ${s.role === 'editor' ? 'selected' : ''}>Editor</option>
                        </select>
                        <button type="button" class="text-error hover:underline text-xs" onclick="removeUserShare(${s.id})">Hapus</button>
                    </div>
                </div>`);
            });

            shareState.division_shares.forEach(s => {
                rows.push(`<div class="flex items-center justify-between gap-2 py-1">
                    <div class="min-w-0">
                        <p class="font-medium truncate">${escapeHtml(s.name)}</p>
                        <p class="text-xs text-base-content/50">Divisi</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <select class="select select-bordered select-xs" onchange="updateDivisionShare(${s.id}, this.value)">
                            <option value="viewer" ${s.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                            <option value="editor" ${s.role === 'editor' ? 'selected' : ''}>Editor</option>
                        </select>
                        <button type="button" class="text-error hover:underline text-xs" onclick="removeDivisionShare(${s.id})">Hapus</button>
                    </div>
                </div>`);
            });

            list.innerHTML = rows.join('') || '<div class="text-base-content/50 italic">Belum ada akses lain.</div>';
        }

        function renderGeneralAccess() {
            const restricted = document.querySelector('input[name="general_access"][value="restricted"]');
            const anyone = document.querySelector('input[name="general_access"][value="anyone_with_link"]');
            if (shareState.general_access === 'anyone_with_link') {
                anyone.checked = true;
            } else {
                restricted.checked = true;
            }
        }

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        async function postForm(url, data) {
            const body = new URLSearchParams(data);
            body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                body,
            });
            if (!res.ok) throw new Error('Request failed');
        }

        async function updateUserShare(id, role) {
            const url = @json(route('shares.update', [$document, '__id__'])).replace('__id__', id);
            await postForm(url, { _method: 'PATCH', role });
            await loadShareData();
        }

        async function removeUserShare(id) {
            const url = @json(route('shares.destroy', [$document, '__id__'])).replace('__id__', id);
            await postForm(url, { _method: 'DELETE' });
            await loadShareData();
        }

        async function updateDivisionShare(id, role) {
            const url = @json(route('shares.division.update', [$document, '__id__'])).replace('__id__', id);
            await postForm(url, { _method: 'PATCH', role });
            await loadShareData();
        }

        async function removeDivisionShare(id) {
            const url = @json(route('shares.division.destroy', [$document, '__id__'])).replace('__id__', id);
            await postForm(url, { _method: 'DELETE' });
            await loadShareData();
        }

        async function updateGeneralAccess() {
            const access = document.querySelector('input[name="general_access"]:checked').value;
            await postForm(shareGeneralUrl, { _method: 'PATCH', general_access: access });
            await loadShareData();
        }

        async function regenerateToken() {
            await postForm(shareRegenUrl, {});
            await loadShareData();
        }

        function copyShareUrl() {
            if (!shareState?.share_url) return;
            navigator.clipboard.writeText(shareState.share_url);
        }

        // Invite autocomplete
        const searchInput = document.getElementById('share-search-input');
        const searchResults = document.getElementById('share-search-results');
        let searchTimer = null;

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(async () => {
                const q = searchInput.value.trim();
                if (q.length < 1) { searchResults.classList.add('hidden'); return; }
                const res = await fetch(shareSearchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                renderSearchResults(data);
            }, 250);
        });

        function renderSearchResults(data) {
            const items = [];
            data.users.forEach(u => {
                items.push(`<button type="button" class="w-full text-left px-3 py-2 hover:bg-base-200 flex items-center justify-between gap-2"
                    onclick="inviteUser(${u.id}, '${escapeHtml(u.name).replace(/'/g, "\\'")}')">
                    <span class="min-w-0"><span class="font-medium">${escapeHtml(u.name)}</span>
                    <span class="text-xs text-base-content/50 block truncate">${escapeHtml(u.email)}</span></span>
                    <span class="badge badge-ghost badge-sm shrink-0">Pengguna</span>
                </button>`);
            });
            data.divisions.forEach(d => {
                items.push(`<button type="button" class="w-full text-left px-3 py-2 hover:bg-base-200 flex items-center justify-between gap-2"
                    onclick="inviteDivision(${d.id}, '${escapeHtml(d.name).replace(/'/g, "\\'")}')">
                    <span class="font-medium">${escapeHtml(d.name)}</span>
                    <span class="badge badge-ghost badge-sm shrink-0">Divisi</span>
                </button>`);
            });
            searchResults.innerHTML = items.join('') || '<div class="px-3 py-2 text-base-content/50">Tidak ditemukan.</div>';
            searchResults.classList.remove('hidden');
        }

        async function inviteUser(id, name) {
            await postForm(shareStoreUrl, { type: 'user', user_id: id, role: 'viewer' });
            searchInput.value = '';
            searchResults.classList.add('hidden');
            await loadShareData();
        }

        async function inviteDivision(id, name) {
            await postForm(shareStoreUrl, { type: 'division', division_id: id, role: 'viewer' });
            searchInput.value = '';
            searchResults.classList.add('hidden');
            await loadShareData();
        }

        document.addEventListener('click', (e) => {
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.classList.add('hidden');
            }
        });
    </script>

    {{-- Version History modal --}}
    <dialog id="version-modal" class="modal">
        <div class="modal-box max-w-2xl max-h-[85vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="font-semibold">Version History</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('version-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @if($document->hasPendingRollback())
                <div class="alert alert-warning alert-sm mb-3 text-xs">
                    Rollback ke v{{ $document->pendingRollbackVersion->version_number }} sedang menunggu approval — opsi rollback lain dinonaktifkan sementara.
                </div>
            @endif
            @forelse($document->versions->sortByDesc('version_number') as $version)
                <div class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-base-200 text-sm">
                    <div class="min-w-0">
                        <span class="font-medium">v{{ $version->version_number }}</span>
                        @if($version->file_path)
                            <span class="badge badge-ghost badge-sm ml-1">Berkas</span>
                        @endif
                        <span class="text-base-content/60">by {{ $version->author_name }}</span>
                        <span class="text-base-content/40">{{ $version->created_at->format('M d, Y H:i') }}</span>
                        @if($version->id === $document->current_version_id)
                            <span class="badge badge-success badge-sm ml-2">Active</span>
                        @elseif($version->status === 'inactive')
                            <span class="badge badge-neutral badge-sm ml-2">Inactive</span>
                        @elseif($version->status === 'pending')
                            <span class="badge badge-warning badge-sm ml-2">Pending</span>
                        @elseif($version->status === 'discarded' || $version->discarded_at)
                            <span class="badge badge-neutral badge-sm ml-2">Discarded</span>
                        @elseif($version->status === 'rejected')
                            <span class="badge badge-error badge-sm ml-2">Rejected</span>
                        @endif
                        @if($document->hasPendingRollback() && $document->pending_rollback_version_id === $version->id)
                            <span class="badge badge-warning badge-sm ml-2">Target Rollback</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <a href="{{ route('documents.preview-version', [$document, $version]) }}"
                           class="btn btn-ghost btn-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Preview
                        </a>
                        @can('update', $document)
                            @if($version->id !== $document->current_version_id
                                && $version->status !== 'pending'
                                && !($version->status === 'discarded' || $version->discarded_at)
                                && !$document->hasPendingRollback())
                                <form method="POST" action="{{ route('approvals.rollback', [$document, $version]) }}" class="inline">
                                    @csrf
                                    <button class="link link-primary inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                        Rollback
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty
                <p class="text-base-content/60 text-sm">No versions yet.</p>
            @endforelse
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Change scope modal --}}
    <dialog id="scope-modal" class="modal">
        <div class="modal-box max-w-sm max-h-[85vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="font-semibold">Change Scope</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('scope-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('documents.update-visibility', $document) }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <label class="label cursor-pointer justify-start gap-3 rounded-lg border border-base-300 p-3 hover:bg-base-200/50">
                    <input type="radio" name="visibility" value="general" class="radio radio-sm radio-primary"
                           {{ $document->isGeneral() ? 'checked' : '' }}>
                    <span class="block min-w-0">
                        <span class="block font-medium text-sm">General (public)</span>
                        <span class="block text-xs text-base-content/60">Terlihat oleh semua pengguna.</span>
                    </span>
                </label>
                <label class="label cursor-pointer justify-start gap-3 rounded-lg border border-base-300 p-3 hover:bg-base-200/50">
                    <input type="radio" name="visibility" value="division" class="radio radio-sm radio-primary"
                           {{ $document->isDivision() ? 'checked' : '' }}>
                    <span class="block min-w-0">
                        <span class="block font-medium text-sm">Division only</span>
                        <span class="block text-xs text-base-content/60">Hanya divisi {{ $document->division?->code ?? '' }} yang bisa melihat.</span>
                    </span>
                </label>
                <label class="label cursor-pointer justify-start gap-3 rounded-lg border border-base-300 p-3 hover:bg-base-200/50">
                    <input type="radio" name="visibility" value="personal" class="radio radio-sm radio-primary"
                           {{ $document->isPersonal() ? 'checked' : '' }}>
                    <span class="block min-w-0">
                        <span class="block font-medium text-sm">Personal</span>
                        <span class="block text-xs text-base-content/60">Hanya kamu yang bisa melihat.</span>
                    </span>
                </label>
                <div class="flex flex-wrap justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('scope-modal').close()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Save
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Edit restricted modal (dokumen berbasis unggahan) --}}
    <dialog id="edit-restricted-modal" class="modal">
        <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="font-semibold">Dokumen Tidak Dapat Diedit Langsung</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('edit-restricted-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p class="text-sm text-base-content/70 mb-5">
                Dokumen ini berasal dari berkas yang diunggah, bukan ditulis melalui editor, sehingga isinya tidak dapat diedit secara langsung.
                Terdapat dua cara untuk memperbarui dokumen:
            </p>
            <ul class="text-sm space-y-2 mb-5 list-disc list-inside text-base-content/80">
                <li><span class="font-medium">Rollback</span> ke versi sebelumnya yang masih tersimpan.</li>
                <li><span class="font-medium">Unggah versi terbaru</span> untuk menggantikan isi dokumen saat ini.</li>
            </ul>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('edit-restricted-modal').close(); document.getElementById('version-modal').showModal();">
                    Lihat Versi
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('edit-restricted-modal').close(); document.getElementById('upload-version-modal').showModal();">
                    Unggah Versi Terbaru
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Upload new version modal --}}
    <dialog id="upload-version-modal" class="modal">
        <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="font-semibold">Unggah Versi Terbaru</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('upload-version-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('documents.upload-version', $document) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-control w-full mb-4">
                    <label for="upload-version-file" class="label">
                        <span class="label-text font-medium">Berkas Pengganti</span>
                    </label>
                    <input type="file" name="file" id="upload-version-file" accept=".pdf,.docx" class="file-input file-input-bordered w-full" required>
                    <p class="text-xs text-base-content/50 mt-1">Hanya PDF atau DOCX, maksimal 10MB. Versi baru akan menunggu approval kepala divisi.</p>
                    @error('file') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('upload-version-modal').close()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</x-app-layout>