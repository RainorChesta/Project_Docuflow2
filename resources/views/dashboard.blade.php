<x-app-layout>
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Search -->
        <form method="GET" action="{{ route('dashboard') }}" class="card bg-base-100 border border-base-300 rounded-box p-4">
            <div class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari dokumen berdasarkan judul atau nomor..."
                       class="input input-bordered w-full">
                <button type="submit" class="btn btn-primary">Search</button>
                @if(request('search'))
                    <a href="{{ route('dashboard') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>

        @if(request('search') && $results)
            <!-- Search Results -->
            <div class="bg-base-100 border border-base-300 rounded-box">
                <div class="px-5 py-4 border-b border-base-300 flex items-center justify-between">
                    <h2 class="font-semibold text-base-content">Search Results</h2>
                    <span class="text-sm text-base-content/50">{{ $results->total() }} found</span>
                </div>
                <div class="divide-y divide-base-200">
                    @forelse($results as $doc)
                        <div class="px-5 py-3.5 flex items-center justify-between hover:bg-base-50 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-8 h-8 shrink-0 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <div class="min-w-0">
                                    <a href="{{ route('documents.show', $doc) }}" class="text-sm font-medium text-base-content hover:text-primary truncate block">{{ $doc->title }}</a>
                                    <p class="text-xs text-base-content/40 mt-0.5">
                                        {{ $doc->document_number }} · {{ $doc->division?->code ?? '—' }} · {{ $doc->owner->name }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-xs text-base-content/40 shrink-0">
                                @if($doc->isGeneral()) <span class="text-success">General</span>
                                @elseif($doc->isPersonal()) <span class="text-info">Personal</span>
                                @else {{ $doc->division?->code }} @endif
                                · {{ $doc->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-base-content/50">No documents match "{{ request('search') }}".</div>
                    @endforelse
                </div>
                @if($results->hasPages())
                    <div class="p-4 border-t border-base-300">{{ $results->links() }}</div>
                @endif
            </div>
        @endif

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat bg-base-100 border border-base-300 rounded-box p-4">
                <div class="stat-title text-base-content/50 text-xs font-medium">Total Documents</div>
                <div class="stat-value text-2xl font-bold text-base-content mt-1">{{ auth()->user()->documents()->count() }}</div>
                <div class="stat-desc text-xs text-base-content/40 mt-1">All time</div>
            </div>
            <div class="stat bg-base-100 border border-base-300 rounded-box p-4">
                <div class="stat-title text-base-content/50 text-xs font-medium">Active Documents</div>
                <div class="stat-value text-2xl font-bold text-success mt-1">
                    {{ auth()->user()->documents()->whereHas('currentVersion', fn($q) => $q->where('status', 'active'))->count() }}
                </div>
                <div class="stat-desc text-xs text-base-content/40 mt-1">Approved & published</div>
            </div>
            <div class="stat bg-base-100 border border-base-300 rounded-box p-4">
                <div class="stat-title text-base-content/50 text-xs font-medium">Pending Approval</div>
                <div class="stat-value text-2xl font-bold text-warning mt-1">
                    {{ auth()->user()->documents()->whereHas('versions', fn($q) => $q->where('status', 'pending'))->count() }}
                </div>
                <div class="stat-desc text-xs text-base-content/40 mt-1">Awaiting head review</div>
            </div>
            <div class="stat bg-base-100 border border-base-300 rounded-box p-4">
                <div class="stat-title text-base-content/50 text-xs font-medium">Shared Links</div>
                <div class="stat-value text-2xl font-bold text-primary mt-1">
                    {{ \App\Models\DocumentAccessLink::whereHas('document', fn($q) => $q->where('owner_id', auth()->id()))->count() }}
                </div>
                <div class="stat-desc text-xs text-base-content/40 mt-1">Active access links</div>
            </div>
        </div>

        <!-- Recent Documents -->
        <div class="bg-base-100 border border-base-300 rounded-box">
            <div class="px-5 py-4 border-b border-base-300 flex items-center justify-between">
                <h2 class="font-semibold text-base-content">Recent Documents</h2>
                <a href="{{ route('documents.index', ['type' => 'mine']) }}" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">View all</a>
            </div>
            <div class="divide-y divide-base-200">
                @forelse($recent as $doc)
                    <div class="px-5 py-3.5 flex items-center justify-between hover:bg-base-50 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-8 h-8 shrink-0 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <div class="min-w-0">
                                <a href="{{ route('documents.show', $doc) }}" class="text-sm font-medium text-base-content hover:text-primary truncate block">{{ $doc->title }}</a>
                                <p class="text-xs text-base-content/40 mt-0.5">
                                    {{ $doc->document_number }} · {{ $doc->division?->code ?? '—' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            @if($doc->currentVersion)
                                <span class="badge badge-success badge-sm gap-1">
                                    <span class="w-1 h-1 rounded-full bg-white"></span>
                                    Active
                                </span>
                            @else
                                <span class="badge badge-warning badge-sm">Pending</span>
                            @endif
                            <span class="text-xs text-base-content/30">{{ $doc->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center">
                        <svg class="w-10 h-10 mx-auto text-base-content/20 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <p class="text-sm text-base-content/50 mb-1">No documents yet</p>
                        <p class="text-xs text-base-content/30">Create your first document to get started.</p>
                        <a href="{{ route('documents.create') }}" class="btn btn-primary btn-sm mt-4">Create Document</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
