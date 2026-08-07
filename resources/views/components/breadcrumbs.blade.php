@props(['items' => []])

<nav class="breadcrumbs text-sm text-base-content/60 mb-4" aria-label="Breadcrumb">
    <ul>
        @foreach($items as $item)
            @if(!empty($item['url']) && !$loop->last)
                <li>
                    <a href="{{ $item['url'] }}" class="hover:text-primary transition-colors">{{ $item['label'] }}</a>
                </li>
            @else
                <li class="text-base-content font-medium">{{ $item['label'] }}</li>
            @endif
        @endforeach
    </ul>
</nav>