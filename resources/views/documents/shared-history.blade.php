<x-app-layout>
    <x-slot name="header">Riwayat Edit via Share Link</x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto w-full px-0">
            <div class="bg-base-100 border border-base-300 rounded-box">
                <div class="px-4 sm:px-5 py-4 border-b border-base-300">
                    <h2 class="font-semibold text-base-content">Dokumen yang Pernah Kamu Edit</h2>
                </div>
                <div class="divide-y divide-base-200">
                    @forelse($history as $item)
                        @php $linkActive = $item['link'] && !$item['link']->isExpired(); @endphp
                        <div class="px-4 sm:px-5 py-3.5 flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-base-content truncate">{{ $item['document']->title }}</p>
                                <p class="text-xs text-base-content/40 mt-0.5">
                                    {{ $item['document']->document_number }} · {{ $item['document']->division?->code ?? '—' }}
                                    · v{{ $item['version_number'] }} · {{ $item['edited_at']->diffForHumans() }}
                                </p>
                            </div>
                            <div class="shrink-0">
                                @if($linkActive)
                                    <a href="{{ route('shared.documents', $item['token']) }}" class="btn btn-primary btn-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        Lanjut Edit
                                    </a>
                                @else
                                    <span class="badge badge-neutral badge-sm">Link tidak aktif</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 sm:px-5 py-10 text-center text-sm text-base-content/50">
                            Belum ada dokumen yang pernah kamu edit lewat share link.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
