<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline btn-primary btn-sm']) }}>
    {{ $slot }}
</button>
