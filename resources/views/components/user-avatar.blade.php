@props([
    'user' => null,
    'name' => null,
    'avatarUrl' => null,
    'size' => 'w-6 h-6',
    'textSize' => 'text-xs',
])

@php
    $resolvedName = $name ?? $user?->name ?? '?';
    $resolvedAvatar = $avatarUrl ?? $user?->avatar_url;
    $initial = strtoupper(mb_substr($resolvedName, 0, 1) ?: '?');
@endphp

@if($resolvedAvatar)
    <img src="{{ $resolvedAvatar }}" alt="{{ $resolvedName }}" {{ $attributes->merge(['class' => "$size rounded-full object-cover shrink-0 ring-1 ring-base-content/10"]) }}>
@else
    <div {{ $attributes->merge(['class' => "$size rounded-full bg-primary/15 text-primary flex items-center justify-center font-bold $textSize shrink-0 select-none"]) }}>
        {{ $initial }}
    </div>
@endif
