<x-app-layout>
    <x-slot name="header">{{ __('Master Company') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ __('Perusahaan Baru') }}
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4">{{ session('error') }}</div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table min-w-[640px]">
                        <thead>
                            <tr>
                                <th>{{ __('Nama Perusahaan') }}</th>
                                <th>{{ __('Kode Perusahaan') }}</th>
                                <th>{{ __('Jumlah Cabang') }}</th>
                                <th>{{ __('Jumlah User') }}</th>
                                <th class="text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companies as $comp)
                                <tr>
                                    <td class="font-medium">{{ $comp->name }}</td>
                                    <td><span class="badge badge-outline">{{ $comp->code }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.branches.index', ['company_id' => $comp->id]) }}" class="link link-primary">
                                            {{ $comp->branches_count }} {{ __('Cabang') }}
                                        </a>
                                    </td>
                                    <td>{{ $comp->users_count }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.companies.edit', $comp) }}" class="link link-primary inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            {{ __('Edit') }}
                                        </a>
                                        <form method="POST" action="{{ route('admin.companies.destroy', $comp) }}" class="inline ml-2" onsubmit="return confirm('Hapus perusahaan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline btn-error btn-xs" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                {{ __('Hapus') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-base-content/60 py-6">{{ __('Belum ada perusahaan.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($companies->hasPages())
                    <div class="p-4 border-t border-base-200">{{ $companies->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
