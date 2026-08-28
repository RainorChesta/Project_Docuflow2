<x-app-layout>
    <x-slot name="header">{{ __('Kategori Template') }}</x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto w-full">
            <div class="mb-4">
                <a href="{{ route('admin.template-categories.create') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ __('Kategori Baru') }}
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4"><span>{{ session('error') }}</span></div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table min-w-[520px]">
                        <thead>
                            <tr>
                                <th>{{ __('Nama Kategori') }}</th>
                                <th class="text-center">{{ __('Jumlah Template') }}</th>
                                <th class="text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>{{ $category->name }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-ghost">{{ $category->templates_count }}</span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.template-categories.edit', $category) }}" class="link link-primary text-sm inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.template-categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline btn-primary btn-xs ml-2" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                {{ __('Hapus') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-base-content/60 py-6">{{ __('Belum ada kategori template.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    @if($categories->hasPages())
                        <div class="p-4 border-t border-base-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <p class="text-sm text-base-content/60 shrink-0">
                                Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of {{ $categories->total() }}
                            </p>
                            <div class="shrink-0">
                                {{ $categories->links('vendor.pagination.dokuflow') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
