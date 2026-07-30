<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline btn-neutral btn-sm']) }}>
    {{ $slot }}
</button>
