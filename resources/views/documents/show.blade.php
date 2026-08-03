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

            <!-- Metadata -->
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-base-content/60">Division</span>
                            <p class="font-medium">{{ $document->division->code }}</p>
                        </div>
                        <div>
                            <span class="text-base-content/60">Owner</span>
                            <p class="font-medium">{{ $document->owner->name }}</p>
                        </div>
                        <div>
                            <span class="text-base-content/60">Status</span>
                            <p class="font-medium">
                                @if($document->currentVersion)
                                    Active (v{{ $document->currentVersion->version_number }})
                                @else
                                    <span class="text-warning">Pending first approval</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <span class="text-base-content/60">Visibility</span>
                            <p class="font-medium">
                                @can('update', $document)
                                    <form method="POST" action="{{ route('documents.toggle-public', $document) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="{{ $document->is_public ? 'text-success' : 'text-base-content/60' }} hover:underline">
                                            {{ $document->is_public ? 'Public' : 'Division only' }}
                                        </button>
                                    </form>
                                @else
                                    {{ $document->is_public ? 'Public' : 'Division only' }}
                                @endcan
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Banner -->
            @php $pendingVersion = $document->versions->firstWhere('status', 'pending'); @endphp
            @if($pendingVersion)
                <div class="alert alert-warning mb-4 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <span>Pending approval (v{{ $pendingVersion->version_number }})</span>
                        @can('update', $document)
                            <form method="POST" action="{{ route('documents.discard', $document) }}" class="inline">
                                @csrf
                                <button class="btn btn-outline btn-warning btn-xs">Discard</button>
                            </form>
                        @endcan
                    </div>
                    @can('approve', $document)
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('approvals.approve', [$document, $pendingVersion]) }}" class="inline">
                            @csrf
                            <button class="btn btn-success btn-sm">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('approvals.reject', [$document, $pendingVersion]) }}" class="inline">
                            @csrf
                            <button class="btn btn-error btn-sm">Reject</button>
                        </form>
                    </div>
                    @endcan
                </div>
            @endif

            <!-- Content -->
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body">
                    @php $display = $document->displayVersion(); @endphp
                    @if($display)
                        <div class="prose max-w-none">
                            {!! $display->content !!}
                        </div>
                    @else
                        <p class="text-base-content/60 italic">No approved content yet.</p>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mb-6">
                @can('update', $document)
                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary btn-sm">
                        Edit Document
                    </a>
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
</x-app-layout>
