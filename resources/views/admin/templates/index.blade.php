<x-app-layout>
    <x-slot name="header">{{ __('Template Dokumen') }}</x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto w-full">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <a href="{{ route('admin.templates.create-manual') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    {{ __('Buat Manual') }}
                </a>
                <a href="{{ route('admin.templates.create') }}" class="btn btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    {{ __('Upload Template') }}
                </a>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.templates.index') }}" class="mb-4">
                <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2">
                    <div class="form-control flex-1 min-w-[180px]">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Cari template...') }}" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <select name="document_type_id" class="select select-bordered select-sm w-full sm:w-auto">
                            <option value="">{{ __('Semua Tipe Dokumen') }}</option>
                            @foreach($documentTypes as $dt)
                                <option value="{{ $dt->id }}" {{ request('document_type_id') == $dt->id ? 'selected' : '' }}>{{ $dt->code }} - {{ $dt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <select name="status" class="select select-bordered select-sm w-full sm:w-auto">
                            <option value="">{{ __('Semua Status') }}</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Aktif') }}</option>
                            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>{{ __('Diarsipkan') }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn btn-sm btn-outline btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            {{ __('Filter') }}
                        </button>
                        @if(request()->hasAny(['search', 'document_type_id', 'status']))
                            <a href="{{ route('admin.templates.index') }}" class="btn btn-sm btn-ghost text-base-content/50">{{ __('Reset') }}</a>
                        @endif
                    </div>
                </div>
            </form>

            @if(session('success'))
                <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4"><span>{{ session('error') }}</span></div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table min-w-[700px]">
                        <thead>
                            <tr>
                                <th>{{ __('Template') }}</th>
                                <th>{{ __('Tipe Dokumen') }}</th>
                                <th>{{ __('File') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                                <th class="text-center">{{ __('Dokumen') }}</th>
                                <th class="text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td>
                                        <div class="font-medium">{{ $template->title }}</div>
                                        @if($template->description)
                                            <div class="text-xs text-base-content/50 mt-0.5">{{ Str::limit($template->description, 60) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-medium text-sm">{{ $template->documentType->code }}</div>
                                        <div class="text-xs text-base-content/60">{{ $template->documentType->name }}</div>
                                    </td>
                                    <td>
                                        <span class="text-xs text-base-content/60">{{ $template->file_original_name }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($template->isActive())
                                            <span class="badge badge-success badge-sm gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                {{ __('Aktif') }}
                                            </span>
                                        @else
                                            <span class="badge badge-ghost badge-sm">{{ __('Diarsipkan') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-ghost badge-sm">{{ $template->documents_count }}</span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.templates.download', $template) }}" class="btn btn-ghost btn-xs" title="{{ __('Download') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                            </a>
                                            <a href="{{ route('admin.templates.editor', $template) }}" class="btn btn-ghost btn-xs" title="{{ __('Edit Konten') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </a>
                                            <a href="{{ route('admin.templates.edit', $template) }}" class="btn btn-ghost btn-xs" title="{{ __('Edit') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </a>
                                            <form method="POST" action="{{ route('admin.templates.toggle-status', $template) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-ghost btn-xs" title="{{ $template->isActive() ? __('Arsipkan') : __('Aktifkan') }}">
                                                    @if($template->isActive())
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    @endif
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.templates.destroy', $template) }}" class="inline" onsubmit="return confirm('{{ __('Hapus template ini?') }}')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-ghost btn-xs text-error" title="{{ __('Hapus') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-base-content/60 py-6">{{ __('Belum ada template dokumen.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    @if($templates->hasPages())
                        <div class="p-4 border-t border-base-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <p class="text-sm text-base-content/60 shrink-0">
                                Showing {{ $templates->firstItem() }} to {{ $templates->lastItem() }} of {{ $templates->total() }}
                            </p>
                            <div class="shrink-0">{{ $templates->links('vendor.pagination.dokuflow') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
