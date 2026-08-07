<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ $document->title }}</span>
            <span class="text-sm font-normal text-base-content/60">{{ $document->document_number }}</span>
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
        <div class="max-w-7xl mx-auto">
            @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Pending Banner (paling atas) -->
            @php $pendingVersion = $document->versions->firstWhere('status', 'pending'); @endphp
            @if($pendingVersion)
                <div class="alert alert-warning mb-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
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
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-base-content/60">Division</span>
                            <p class="font-medium">{{ $document->division?->code ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="text-base-content/60">Owner</span>
                            <p class="font-medium">{{ $document->owner->name }}</p>
                        </div>
                        <div>
                            <span class="text-base-content/60">Status</span>
                            <p class="font-medium">
                                @php
                                    $hasDraft = $document->versions->contains('status', 'draft');
                                @endphp
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
                            <span class="text-base-content/60">Visibility</span>
                            <p class="font-medium">
                                @if($document->isGeneral())
                                    <span class="badge badge-success badge-sm">General</span>
                                @elseif($document->isPersonal())
                                    <span class="badge badge-info badge-sm">Personal</span>
                                @else
                                    <span class="badge badge-neutral badge-sm">{{ $document->division?->code ?? 'Division' }} only</span>
                                @endif
                            </p>
                            @can('update', $document)
                                <div class="mt-2">
                                    <button type="button" class="btn btn-ghost btn-xs" onclick="document.getElementById('scope-modal').showModal()">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        Change scope
                                    </button>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body p-0">
                    @php $display = $document->displayVersion(); @endphp
                    @if($display)
                        @include('documents._paper', ['content' => $display->content])
                    @else
                        <p class="text-base-content/60 italic">No approved content yet.</p>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mb-6">
                @can('update', $document)
                    @if($hasDraft && !$pendingVersion && !$document->currentVersion)
                        <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Edit Draft
                        </a>
                    @else
                        <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Edit Document
                        </a>
                    @endif
                @endcan

                @can('update', $document)
                    <button onclick="document.getElementById('link-form').classList.toggle('hidden')" class="btn btn-neutral btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.656 0l2 2a4 4 0 01-5.656 5.656l-1.414-1.414M10.172 13.828a4 4 0 01-5.656 0l-2-2a4 4 0 015.656-5.656l1.414 1.414" /></svg>
                        Share Link
                    </button>
                @endcan

                    <button
                        type="button"
                        class="btn btn-ghost btn-sm"
                        onclick="document.getElementById('version-modal').showModal()"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Lihat Versi ({{ $document->versions->count() }})
                    </button>
            </div>

            <!-- Share Link Form -->
            <div id="link-form" class="hidden card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">Generate Share Link</h3>
                    <form method="POST" action="{{ route('links.store', $document) }}" class="flex gap-2 items-end">
                        @csrf
                        <div class="form-control">
                            <label class="label"><span class="label-text">Role</span></label>
                            <select name="role" class="select select-bordered" required>
                                <option value="viewer">Viewer</option>
                                <option value="editor">Editor</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Expires (optional)</span></label>
                            <input type="date" name="expires_at" class="input input-bordered" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Generate
                        </button>
                    </form>

                    @if($document->accessLinks->count())
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-base-content/70 mb-2">Active Links</h4>
                            @foreach($document->accessLinks as $link)
                                <div class="flex justify-between items-center py-2 border-b border-base-200 text-sm">
                                    <button type="button"
                                            class="text-base-content/60 truncate max-w-md text-left hover:underline"
                                            onclick="openShareLinkModal('{{ route('shared.documents', $link->token) }}')"
                                            title="Klik untuk salin link">
                                        {{ route('shared.documents', $link->token) }}
                                    </button>
                                    <div class="flex gap-2 items-center">
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
            </div>

        </div>
    </div>

    {{-- Share link modal (reusable) --}}
    <style>
        #share-link-modal::backdrop { background: rgba(0, 0, 0, 0.5); }
    </style>
    <dialog id="share-link-modal" class="modal">
        <div class="modal-box max-w-md">
            <div class="flex items-center justify-between mb-4">
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
    </script>

    {{-- Version History modal --}}
    <dialog id="version-modal" class="modal">
        <div class="modal-box max-w-2xl max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold">Version History</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('version-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @forelse($document->versions->sortByDesc('version_number') as $version)
                <div class="flex items-center justify-between py-2 border-b border-base-200 text-sm">
                    <div>
                        <span class="font-medium">v{{ $version->version_number }}</span>
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
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('documents.preview-version', [$document, $version]) }}"
                           class="btn btn-ghost btn-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Preview
                        </a>
                        @can('update', $document)
                            @if($version->id !== $document->current_version_id
                                && $version->status !== 'pending'
                                && !($version->status === 'discarded' || $version->discarded_at))
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
        <div class="modal-box max-w-sm">
            <div class="flex items-center justify-between mb-4">
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
                    <span class="block">
                        <span class="block font-medium text-sm">General (public)</span>
                        <span class="block text-xs text-base-content/60">Terlihat oleh semua pengguna.</span>
                    </span>
                </label>
                <label class="label cursor-pointer justify-start gap-3 rounded-lg border border-base-300 p-3 hover:bg-base-200/50">
                    <input type="radio" name="visibility" value="division" class="radio radio-sm radio-primary"
                           {{ $document->isDivision() ? 'checked' : '' }}>
                    <span class="block">
                        <span class="block font-medium text-sm">Division only</span>
                        <span class="block text-xs text-base-content/60">Hanya divisi {{ $document->division?->code ?? '' }} yang bisa melihat.</span>
                    </span>
                </label>
                <label class="label cursor-pointer justify-start gap-3 rounded-lg border border-base-300 p-3 hover:bg-base-200/50">
                    <input type="radio" name="visibility" value="personal" class="radio radio-sm radio-primary"
                           {{ $document->isPersonal() ? 'checked' : '' }}>
                    <span class="block">
                        <span class="block font-medium text-sm">Personal</span>
                        <span class="block text-xs text-base-content/60">Hanya kamu yang bisa melihat.</span>
                    </span>
                </label>
                <div class="flex justify-end gap-2 pt-2">
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
</x-app-layout>
