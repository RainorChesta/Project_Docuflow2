@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium bg-primary/10 text-primary transition-all duration-150'
            : 'flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-base-content/70 hover:bg-base-200 hover:text-base-content transition-all duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
