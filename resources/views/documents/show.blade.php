<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ $document->title }}</span>
            <span class="text-sm font-normal text-gray-500">{{ $document->document_number }}</span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <!-- Metadata -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Division</span>
                        <p class="font-medium">{{ $document->division->code }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Owner</span>
                        <p class="font-medium">{{ $document->owner->name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Status</span>
                        <p class="font-medium">
                            @if($document->currentVersion)
                                Active (v{{ $document->currentVersion->version_number }})
                            @else
                                <span class="text-yellow-600">Pending first approval</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500">Visibility</span>
                        <p class="font-medium">
                            @can('update', $document)
                                <form method="POST" action="{{ route('documents.toggle-public', $document) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="{{ $document->is_public ? 'text-green-600' : 'text-gray-600' }} hover:underline">
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

            <!-- Pending Banner -->
            @php $pendingVersion = $document->versions->firstWhere('status', 'pending'); @endphp
            @if($pendingVersion)
                <div class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded flex justify-between items-center">
                    <span>Pending approval (v{{ $pendingVersion->version_number }})</span>
                    @can('approve', $document)
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('approvals.approve', [$document, $pendingVersion]) }}" class="inline">
                            @csrf
                            <button class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('approvals.reject', [$document, $pendingVersion]) }}" class="inline">
                            @csrf
                            <button class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700">Reject</button>
                        </form>
                    </div>
                    @endcan
                </div>
            @endif

            <!-- Content -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    @if($document->currentVersion)
                        <div class="prose max-w-none">
                            {!! $document->currentVersion->content !!}
                        </div>
                    @else
                        <p class="text-gray-500 italic">No approved content yet.</p>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mb-6">
                @can('update', $document)
                    <a href="{{ route('documents.edit', $document) }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                        Edit Document
                    </a>
                @endcan

                @can('update', $document)
                    <button onclick="document.getElementById('link-form').classList.toggle('hidden')" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">
                        Share Link
                    </button>
                @endcan
            </div>

            <!-- Share Link Form -->
            <div id="link-form" class="hidden bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Generate Share Link</h3>
                    <form method="POST" action="{{ route('links.store', $document) }}" class="flex gap-2 items-end">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-700">Role</label>
                            <select name="role" class="rounded-md border-gray-300" required>
                                <option value="viewer">Viewer</option>
                                <option value="editor">Editor</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">Expires (optional)</label>
                            <input type="date" name="expires_at" class="rounded-md border-gray-300" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Generate</button>
                    </form>

                    @if($document->accessLinks->count())
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Active Links</h4>
                            @foreach($document->accessLinks as $link)
                                <div class="flex justify-between items-center py-2 border-b text-sm">
                                    <span class="text-gray-600 truncate max-w-md">{{ route('shared.documents', $link->token) }}</span>
                                    <div class="flex gap-2 items-center">
                                        <span class="text-xs px-2 py-1 rounded {{ $link->role === 'editor' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $link->role }}
                                        </span>
                                        @if($link->expires_at)
                                            <span class="text-xs text-gray-500">until {{ $link->expires_at->format('Y-m-d') }}</span>
                                        @else
                                            <span class="text-xs text-gray-500">never</span>
                                        @endif
                                        <form method="POST" action="{{ route('links.destroy', [$document, $link]) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline text-xs">Revoke</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Version History -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold mb-4">Version History</h3>
                    @forelse($document->versions->sortByDesc('version_number') as $version)
                        <div class="flex items-center justify-between py-2 border-b text-sm">
                            <div>
                                <span class="font-medium">v{{ $version->version_number }}</span>
                                <span class="text-gray-500">by {{ $version->author_name }}</span>
                                <span class="text-gray-400">{{ $version->created_at->format('M d, Y H:i') }}</span>
                                @if($version->id === $document->current_version_id)
                                    <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs">Active</span>
                                @elseif($version->status === 'pending')
                                    <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs">Pending</span>
                                @elseif($version->status === 'rejected')
                                    <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-800 rounded text-xs">Rejected</span>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                @can('update', $document)
                                    @if($version->status === 'active' && $version->id !== $document->current_version_id)
                                        <form method="POST" action="{{ route('approvals.rollback', [$document, $version]) }}" class="inline">
                                            @csrf
                                            <button class="text-blue-600 hover:underline">Rollback</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No versions yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
