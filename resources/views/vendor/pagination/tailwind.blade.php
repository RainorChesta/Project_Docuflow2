@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full">
        
        <!-- Left Side: Showing results -->
        <div class="flex-shrink-0">
            <p class="text-[13px] font-medium text-base-content/60">
                {!! __('Showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-bold text-base-content">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="font-bold text-base-content">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                {!! __('of') !!}
                <span class="font-bold text-base-content">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>
        </div>

        <!-- Right Side: Pagination Controls -->
        <div>
                <div class="join shadow-sm rounded-lg">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <button class="join-item btn btn-sm border-base-300 bg-base-100 btn-disabled" aria-disabled="true">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="join-item btn btn-sm border-base-300 bg-base-100 hover:bg-base-200 text-base-content transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <button class="join-item btn btn-sm border-base-300 bg-base-100 btn-disabled">{{ $element }}</button>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <button class="join-item btn btn-sm bg-primary text-primary-content hover:bg-primary border-primary pointer-events-none">{{ $page }}</button>
                                @else
                                    <a href="{{ $url }}" class="join-item btn btn-sm border-base-300 bg-base-100 hover:bg-base-200 text-base-content transition-colors">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="join-item btn btn-sm border-base-300 bg-base-100 hover:bg-base-200 text-base-content transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @else
                        <button class="join-item btn btn-sm border-base-300 bg-base-100 btn-disabled" aria-disabled="true">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </nav>
@endif
