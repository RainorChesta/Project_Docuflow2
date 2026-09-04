@props([
    'format' => 'baru',
    'size' => 'xs',
    'showDot' => true,
])

@php
    $isOld = ($format === 'lama');
    
    // Size variants
    $sizeClasses = match($size) {
        'sm' => 'text-xs px-2.5 py-1 gap-1.5',
        default => 'text-[10px] px-2 py-0.5 gap-1',
    };
    
    $dotSize = match($size) {
        'sm' => 'w-2 h-2',
        default => 'w-1.5 h-1.5',
    };
    
    // Color schemes
    if ($isOld) {
        $colorClasses = 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/15';
        $dotColor = 'bg-amber-500';
        $label = __('Format Lama');
        $title = __('Format Penomoran Lama');
    } else {
        $colorClasses = 'bg-primary/10 text-primary border border-primary/20 hover:bg-primary/15';
        $dotColor = 'bg-primary';
        $label = __('Format Baru');
        $title = __('Format Penomoran Baru');
    }
@endphp

<span 
    {{ $attributes->merge([
        'class' => "inline-flex items-center font-semibold tracking-wide rounded-full whitespace-nowrap shrink-0 transition-colors leading-none {$sizeClasses} {$colorClasses}"
    ]) }}
    title="{{ $title }}"
>
    @if($showDot)
        <span class="inline-block rounded-full shrink-0 {{ $dotSize }} {{ $dotColor }}" aria-hidden="true"></span>
    @endif
    <span>{{ $label }}</span>
</span>
