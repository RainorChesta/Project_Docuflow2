<x-app-layout>
    <x-slot name="header">{{ __('Divisi') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full">
            <div class="mb-4">
                <a href="{{ route('admin.divisions.create') }}" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ __('Divisi Baru') }}
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error mb-4">{{ $errors->first() }}</div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table min-w-[640px]">
                        <thead>
                            <tr>
                                <th>{{ __('Kode') }}</th>
                                <th>{{ __('Nama') }}</th>
                                <th>{{ __('Pengguna') }}</th>
                                <th>{{ __('Dokumen') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($divisions as $div)
                                <tr>
                                    <td class="font-mono">{{ $div->code }}</td>
                                    <td>{{ $div->name }}</td>
                                    <td>{{ $div->users_count }}</td>
                                    <td>{{ $div->documents_count }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.divisions.edit', $div) }}" class="btn btn-ghost btn-xs btn-square text-primary" title="{{ __('Edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </a>
                                        @if($div->users_count === 0 && $div->documents_count === 0)
                                        <form method="POST" action="{{ route('admin.divisions.destroy', $div) }}" class="inline ml-2" onsubmit="return confirm('{{ __('Hapus divisi ini?') }}')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-ghost btn-xs btn-square text-error" title="{{ __('Hapus') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($divisions->hasPages())
                    <div class="p-4 border-t border-base-200">{{ $divisions->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
