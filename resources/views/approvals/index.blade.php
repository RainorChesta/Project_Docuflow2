<x-app-layout>
    <x-slot name="header">Pending Approvals</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            @if(session('success'))
                <div class="alert alert-success mb-4">{{ session('success') }}</div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-0">
                    <div class="divide-y divide-base-200">
                        @forelse($pendingVersions as $version)
                            <div class="p-6">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium">{{ $version->document->title }}</p>
                                        <p class="text-sm text-base-content/60">
                                            v{{ $version->version_number }} · by {{ $version->author_name }} · {{ $version->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2 items-center">
                                        <a href="{{ route('documents.preview', $version->document) }}" title="Preview Dokumen" class="inline-flex items-center justify-center w-6 h-6 rounded-full text-base-content/60 hover:text-base-content hover:bg-base-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('approvals.approve', [$version->document, $version]) }}" class="inline">
                                            @csrf
                                            <button class="btn btn-success btn-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('approvals.reject', [$version->document, $version]) }}" class="inline">
                                            @csrf
                                            <button class="btn btn-error btn-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="mt-3 p-3 bg-base-200/50 rounded-lg text-sm max-h-32 overflow-y-auto">
                                    {!! $version->content !!}
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-base-content/60">No pending approvals.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
