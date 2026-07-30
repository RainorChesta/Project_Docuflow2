@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success text-sm']) }}>
        <span>{{ $status }}</span>
    </div>
@endif
