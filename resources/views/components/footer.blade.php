@props(['class' => ''])

<footer {{ $attributes->merge(['class' => 'py-4 text-center text-xs text-base-content/50 print:hidden ' . $class]) }}>
    Copyright &copy; {{ date('Y') }} <a href="https://cmhgroup.id/" target="_blank" rel="noopener noreferrer" class="font-semibold text-base-content/70 hover:text-primary transition-colors underline decoration-base-content/20 hover:decoration-primary">CMH Group</a>. {{ __('All rights reserved.') }}
</footer>

