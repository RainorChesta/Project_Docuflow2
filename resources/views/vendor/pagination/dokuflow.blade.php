<nav class="flex flex-wrap items-center justify-center gap-1" role="navigation" aria-label="Pagination">
    {{-- Previous --}}
    @if($paginator->onFirstPage())
        <span class="btn btn-sm btn-ghost btn-disabled pointer-events-none opacity-40">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            Previous
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-sm btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            Previous
        </a>
    @endif

    {{-- Page numbers with ellipsis --}}
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $window = 2; // pages shown around current
        $start = max(1, $current - $window);
        $end = min($last, $current + $window);
        $pages = [];
        if ($start > 1) {
            $pages[] = 1;
            if ($start > 2) $pages[] = '...';
        }
        for ($i = $start; $i <= $end; $i++) $pages[] = $i;
        if ($end < $last) {
            if ($end < $last - 1) $pages[] = '...';
            $pages[] = $last;
        }
    @endphp

    @foreach($pages as $page)
        @if($page === '...')
            <span class="px-2 text-base-content/40">…</span>
        @elseif($page === $current)
            <span aria-current="page" class="btn btn-sm btn-primary pointer-events-none">{{ $page }}</span>
        @else
            <a href="{{ $paginator->url($page) }}" class="btn btn-sm btn-ghost">{{ $page }}</a>
        @endif
    @endforeach

    {{-- Next --}}
    @if($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-sm btn-ghost">
            Next
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        </a>
    @else
        <span class="btn btn-sm btn-ghost btn-disabled pointer-events-none opacity-40">
            Next
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        </span>
    @endif
</nav>