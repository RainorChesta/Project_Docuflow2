<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ $document->title }}</span>
            <span class="text-sm font-normal text-base-content/60">{{ $document->document_number }}</span>
        </div>
    </x-slot>

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
                                <form method="POST" action="{{ route('documents.discard', $document) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-outline btn-warning btn-xs">Discard</button>
                                </form>
                            @endcan
                            @can('approve', $document)
                                <form method="POST" action="{{ route('approvals.approve', [$document, $pendingVersion]) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('approvals.reject', [$document, $pendingVersion]) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-error btn-sm">Reject</button>
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
                            Edit Draft
                        </a>
                    @else
                        <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary btn-sm">
                            Edit Document
                        </a>
                    @endif
                @endcan

                @can('update', $document)
                    <button onclick="document.getElementById('link-form').classList.toggle('hidden')" class="btn btn-neutral btn-sm">
                        Share Link
                    </button>
                @endcan

                <button
                    type="button"
                    class="btn btn-ghost btn-sm"
                    onclick="document.getElementById('version-modal').showModal()"
                >
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
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </form>

                    @if($document->accessLinks->count())
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-base-content/70 mb-2">Active Links</h4>
                            @foreach($document->accessLinks as $link)
                                <div class="flex justify-between items-center py-2 border-b border-base-200 text-sm">
                                    <span class="text-base-content/60 truncate max-w-md">{{ route('shared.documents', $link->token) }}</span>
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
                                            <button class="text-error hover:underline text-xs">Revoke</button>
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
                        @elseif($version->status === 'pending')
                            <span class="badge badge-warning badge-sm ml-2">Pending</span>
                        @elseif($version->status === 'discarded' || $version->discarded_at)
                            <span class="badge badge-neutral badge-sm ml-2">Discarded</span>
                        @elseif($version->status === 'rejected')
                            <span class="badge badge-error badge-sm ml-2">Rejected</span>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        @can('update', $document)
                            @if($version->status === 'active' && $version->id !== $document->current_version_id)
                                <form method="POST" action="{{ route('approvals.rollback', [$document, $version]) }}" class="inline">
                                    @csrf
                                    <button class="link link-primary">Rollback</button>
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
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('scope-modal').close()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</x-app-layout>
