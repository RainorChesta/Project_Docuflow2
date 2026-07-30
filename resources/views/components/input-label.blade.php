@props(['value'])

<label {{ $attributes->merge(['class' => 'label label-text font-medium']) }}>
    {{ $value ?? $slot }}
</label>
