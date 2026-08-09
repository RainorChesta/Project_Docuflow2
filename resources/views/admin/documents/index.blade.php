<x-app-layout>
    <x-slot name="header">Semua Dokumen</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full">
            @if(session('success'))
                <div class="alert alert-success mb-4">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error mb-4">{{ $errors->first() }}</div>
            @endif

            <form method="GET" action="{{ route('admin.documents.index') }}" class="mb-4 flex flex-wrap gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul, nomor, atau pemilik..."
                       class="input input-bordered input-sm flex-1 min-w-[200px]">
                <select name="division_id" class="select select-bordered select-sm" onchange="this.form.submit()">
                    <option value="">Semua divisi</option>
                    @foreach($divisions as $div)
                        <option value="{{ $div->id }}" {{ request('division_id') == $div->id ? 'selected' : '' }}>
                            {{ $div->code }} - {{ $div->name }}
                        </option>
                    @endforeach
                </select>
                <select name="document_type_id" class="select select-bordered select-sm" onchange="this.form.submit()">
                    <option value="">Semua tipe</option>
                    @foreach($documentTypes as $dt)
                        <option value="{{ $dt->id }}" {{ request('document_type_id') == $dt->id ? 'selected' : '' }}>
                            {{ $dt->code }} - {{ $dt->name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="select select-bordered select-sm" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                </select>
                <button type="submit" class="btn btn-neutral btn-sm">Filter</button>
                @if(request('search') || request('status') || request('division_id') || request('document_type_id'))
                    <a href="{{ route('admin.documents.index') }}" class="btn btn-ghost btn-sm">Clear</a>
                @endif
            </form>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table min-w-[720px]">
                        <thead>
                            <tr>
                                <th>Dokumen</th>
                                <th>Divisi</th>
                                <th>Pemilik</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $doc)
                                @php
                                    $hasPending = $doc->versions->contains('status', 'pending');
                                    $hasDraft = $doc->versions->contains('status', 'draft');
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('documents.show', $doc) }}" class="link link-primary font-medium">
                                            {{ $doc->title }}
                                        </a>
                                        <div class="text-xs text-base-content/60 font-mono">{{ $doc->document_number }}</div>
                                    </td>
                                    <td>{{ $doc->division?->code ?? '—' }}</td>
                                    <td>{{ $doc->owner->name }}</td>
                                    <td>
                                        @if($doc->currentVersion)
                                            <span class="badge badge-success badge-sm">v{{ $doc->currentVersion->version_number }}</span>
                                        @elseif($hasPending)
                                            <span class="badge badge-warning badge-sm">Pending</span>
                                        @elseif($hasDraft)
                                            <span class="badge badge-warning badge-sm">Draft</span>
                                        @else
                                            <span class="badge badge-ghost badge-sm">No version</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}" class="inline"
                                              onsubmit="return confirm('Hapus dokumen \'{{ $doc->title }}\' beserta semua versinya? Tindakan ini tidak bisa dibatalkan.')">
                                            @csrf @method('DELETE')
                                            <button class="link link-error inline-flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-base-content/60 py-6">Tidak ada dokumen.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($documents->hasPages())
                    <div class="p-4 border-t border-base-200">{{ $documents->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>