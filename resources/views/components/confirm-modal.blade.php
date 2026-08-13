@props([
    'name',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed?',
    'action',
    'method' => 'DELETE',
    'confirmLabel' => 'Delete',
    'cancelLabel' => 'Cancel',
    'confirmClass' => 'btn-error',
    'reopenOnCancel' => null,
])

<x-modal :name="$name" :show="false" maxWidth="sm">
    <div class="p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-base-content">{{ $title }}</h3>
        <p class="mt-2 text-sm text-base-content/70">{{ $message }}</p>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <button type="button"
                    class="btn btn-ghost"
                    x-on:click="$dispatch('close-modal', '{{ $name }}'); @if($reopenOnCancel) document.getElementById('{{ $reopenOnCancel }}').showModal(); @endif">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                {{ $cancelLabel }}
            </button>

            <form method="POST" action="{{ $action }}" class="inline">
                @csrf
                @if(strtoupper($method) !== 'POST')
                    @method($method)
                @endif
                <button type="submit" class="btn {{ $confirmClass }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    {{ $confirmLabel }}
                </button>
            </form>
        </div>
    </div>
</x-modal>