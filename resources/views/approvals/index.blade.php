<x-app-layout>
    <x-slot name="header">Pending Approvals</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @forelse($pendingVersions as $version)
                        <div class="border-b py-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium">{{ $version->document->title }}</p>
                                    <p class="text-sm text-gray-500">
                                        v{{ $version->version_number }} · by {{ $version->author_name }} · {{ $version->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('approvals.approve', [$version->document, $version]) }}" class="inline">
                                        @csrf
                                        <button class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('approvals.reject', [$version->document, $version]) }}" class="inline">
                                        @csrf
                                        <button class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700">Reject</button>
                                    </form>
                                </div>
                            </div>
                            <div class="mt-2 p-3 bg-gray-50 rounded text-sm max-h-32 overflow-y-auto">
                                {!! $version->content !!}
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">No pending approvals.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
