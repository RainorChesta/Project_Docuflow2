@props(['items' => []])

<nav class="breadcrumbs text-xs text-base-content/50 w-full overflow-x-auto whitespace-nowrap scrollbar-hide py-1 mb-2 sm:mb-3 touch-pan-x" aria-label="Breadcrumb">
    <ul class="flex items-center flex-nowrap min-w-max">
        @foreach($items as $item)
            @if(!empty($item['url']) && !$loop->last)
                <li class="shrink-0 whitespace-nowrap">
                    <a href="{{ $item['url'] }}" class="hover:text-primary transition-colors">{{ $item['label'] }}</a>
                </li>
            @else
                <li class="text-base-content/80 font-medium shrink-0 whitespace-nowrap">{{ $item['label'] }}</li>
            @endif
        @endforeach
    </ul>
</nav>