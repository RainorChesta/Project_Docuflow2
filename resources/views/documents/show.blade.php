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
                                    $pendingVersion = $document->versions->firstWhere('status', 'pending');
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
                                    <span class="text-success">General</span>
                                @elseif($document->isPersonal())
                                    <span class="text-info">Personal</span>
                                @else
                                    <span>{{ $document->division?->code ?? 'Division' }} only</span>
                                @endif
                            </p>
                            @can('update', $document)
                                <div class="mt-2">
                                    <details class="dropdown">
                                        <summary class="btn btn-ghost btn-xs">Change scope</summary>
                                        <form method="POST" action="{{ route('documents.update-visibility', $document) }}"
                                              class="menu p-4 bg-base-100 border border-base-300 shadow-lg rounded-box w-64 space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="visibility" class="select select-bordered select-sm w-full">
                                                <option value="general" {{ $document->isGeneral() ? 'selected' : '' }}>General (public)</option>
                                                <option value="division" {{ $document->isDivision() ? 'selected' : '' }}>Division</option>
                                                <option value="personal" {{ $document->isPersonal() ? 'selected' : '' }}>Personal</option>
                                            </select>
                                            <select name="division_id" class="select select-bordered select-sm w-full"
                                                    {{ $document->isDivision() ? '' : 'disabled' }}>
                                                <option value="">Select division...</option>
                                                @foreach($divisions ?? [] as $div)
                                                    <option value="{{ $div->id }}" {{ $document->division_id === $div->id ? 'selected' : '' }}>
                                                        {{ $div->code }} - {{ $div->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-xs w-full">Save</button>
                                        </form>
                                    </details>
                                </div>
                            @endcan
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
                    @if($document->currentVersion)
                        <div class="prose max-w-none">
                            {!! $document->currentVersion->content !!}
                        </div>
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

            <!-- Version History -->
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">Version History</h3>
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
            </div>
        </div>
    </div>
</x-app-layout>
