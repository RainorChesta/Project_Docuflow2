<x-app-layout>
    <x-slot name="header">Document Types</x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto">
            <div class="mb-4">
                <a href="{{ route('admin.document-types.create') }}" class="btn btn-primary">
                    + New Document Type
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
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Keterangan</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documentTypes as $type)
                                <tr>
                                    <td><span class="badge badge-outline">{{ $type->code }}</span></td>
                                    <td>{{ $type->name }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.document-types.edit', $type) }}" class="link link-primary text-sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.document-types.destroy', $type) }}" class="inline" onsubmit="return confirm('Hapus tipe dokumen ini?')">
                                            @csrf @method('DELETE')
                                            <button class="link link-error text-sm ml-2">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-base-content/60 py-6">Belum ada tipe dokumen.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($documentTypes->hasPages())
                        <div class="p-4 border-t border-base-200">{{ $documentTypes->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
