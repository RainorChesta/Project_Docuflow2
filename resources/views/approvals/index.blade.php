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
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('approvals.approve', [$version->document, $version]) }}" class="inline">
                                            @csrf
                                            <button class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('approvals.reject', [$version->document, $version]) }}" class="inline">
                                            @csrf
                                            <button class="btn btn-error btn-sm">Reject</button>
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
