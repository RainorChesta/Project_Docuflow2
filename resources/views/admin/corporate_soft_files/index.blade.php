<x-app-layout>
    <x-slot name="header">{{ __('Soft File Korporat') }}</x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto w-full px-4 sm:px-6">
            {{-- Header Actions --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-base-content">{{ __('Daftar Soft File Korporat') }}</h1>
                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Kelola master soft file, kop surat, dan template resmi perusahaan beserta hak akses per cabang.') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.corporate-soft-files.create') }}" class="btn btn-primary btn-sm gap-2 rounded-xl shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('Tambah Soft File') }}
                    </a>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.corporate-soft-files.index') }}" class="mb-5 bg-base-100 p-3 sm:p-4 rounded-2xl border border-base-300 shadow-2xs">
                <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5">
                    <div class="form-control flex-1 min-w-[200px]">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Cari judul atau deskripsi soft file...') }}" class="input input-bordered input-sm w-full rounded-xl">
                    </div>
                    <div class="form-control">
                        <select name="company_id" class="select select-bordered select-sm w-full sm:w-auto rounded-xl">
                            <option value="">{{ __('Semua Perusahaan') }}</option>
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <select name="branch_id" class="select select-bordered select-sm w-full sm:w-auto rounded-xl">
                            <option value="">{{ __('Semua Cabang') }}</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->company?->name }} - {{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <select name="status" class="select select-bordered select-sm w-full sm:w-auto rounded-xl">
                            <option value="">{{ __('Semua Status') }}</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Aktif') }}</option>
                            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>{{ __('Diarsipkan') }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn btn-sm btn-primary rounded-xl px-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            {{ __('Filter') }}
                        </button>
                        @if(request()->hasAny(['search', 'company_id', 'branch_id', 'status']))
                            <a href="{{ route('admin.corporate-soft-files.index') }}" class="btn btn-sm btn-ghost text-base-content/50 rounded-xl">{{ __('Reset') }}</a>
                        @endif
                    </div>
                </div>
            </form>

            @if(session('success'))
                <div class="alert alert-success mb-4 rounded-2xl shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4 rounded-2xl shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr class="bg-base-200/50 border-b border-base-300 text-base-content/70 text-xs uppercase tracking-wider">
                                    <th class="py-3 px-4">{{ __('Soft File Korporat') }}</th>
                                    <th class="py-3 px-4">{{ __('Akses Perusahaan & Cabang') }}</th>
                                    <th class="py-3 px-4">{{ __('Hak Akses Role') }}</th>
                                    <th class="py-3 px-4 text-center">{{ __('Status') }}</th>
                                    <th class="py-3 px-4 text-right">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-200/60 text-sm">
                                @forelse($softFiles as $softFile)
                                    <tr class="hover:bg-base-200/30 transition-colors">
                                        <td class="py-3 px-4 min-w-[220px]">
                                            <div class="flex items-start gap-3">
                                                <div class="btn-trigger-sf-preview w-8 h-8 rounded-xl {{ $softFile->isPdf() ? 'bg-error/10 text-error' : ($softFile->isImage() ? 'bg-secondary/10 text-secondary' : 'bg-primary/10 text-primary') }} flex items-center justify-center shrink-0 mt-0.5 cursor-pointer hover:scale-105 transition-transform"
                                                     onclick="triggerSoftFilePreview(this)"
                                                     data-id="{{ $softFile->id }}"
                                                     data-title="{{ $softFile->title }}"
                                                     data-type="{{ $softFile->file_type }}"
                                                     data-filename="{{ $softFile->file_original_name }}"
                                                     data-filesize="{{ $softFile->file_size ? number_format($softFile->file_size / 1024, 1) . ' KB' : '' }}"
                                                     data-is-image="{{ $softFile->isImage() ? '1' : '0' }}"
                                                     data-is-pdf="{{ $softFile->isPdf() ? '1' : '0' }}"
                                                     data-content-url="{{ route('admin.corporate-soft-files.preview-content', $softFile) }}"
                                                     data-preview-url="{{ route('admin.corporate-soft-files.preview', $softFile) }}"
                                                     data-download-url="{{ route('admin.corporate-soft-files.download', $softFile) }}"
                                                     title="{{ __('Klik untuk pratinjau') }}">
                                                    @if($softFile->isPdf())
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                        </svg>
                                                    @elseif($softFile->isImage())
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-bold text-base-content flex items-center gap-1.5">
                                                        <span class="btn-trigger-sf-preview cursor-pointer hover:text-primary transition-colors"
                                                              onclick="triggerSoftFilePreview(this)"
                                                              data-id="{{ $softFile->id }}"
                                                              data-title="{{ $softFile->title }}"
                                                              data-type="{{ $softFile->file_type }}"
                                                              data-filename="{{ $softFile->file_original_name }}"
                                                              data-filesize="{{ $softFile->file_size ? number_format($softFile->file_size / 1024, 1) . ' KB' : '' }}"
                                                              data-is-image="{{ $softFile->isImage() ? '1' : '0' }}"
                                                              data-is-pdf="{{ $softFile->isPdf() ? '1' : '0' }}"
                                                              data-content-url="{{ route('admin.corporate-soft-files.preview-content', $softFile) }}"
                                                              data-preview-url="{{ route('admin.corporate-soft-files.preview', $softFile) }}"
                                                              data-download-url="{{ route('admin.corporate-soft-files.download', $softFile) }}">{{ $softFile->title }}</span>
                                                        <span class="badge {{ $softFile->isPdf() ? 'badge-error' : ($softFile->isImage() ? 'badge-secondary' : 'badge-primary') }} badge-xs font-mono font-bold">{{ $softFile->file_type }}</span>
                                                    </div>
                                                    <div class="text-xs text-base-content/50 mt-0.5 flex items-center gap-2 flex-wrap">
                                                        <span>{{ $softFile->file_original_name }}</span>
                                                        @if($softFile->file_size)
                                                            <span>• {{ number_format($softFile->file_size / 1024, 1) }} KB</span>
                                                        @endif
                                                    </div>
                                                    @if($softFile->description)
                                                        <div class="text-xs text-base-content/60 mt-1 max-w-sm line-clamp-1">{{ $softFile->description }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 min-w-[200px]">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-[11px] font-semibold text-base-content/50 uppercase">{{ __('Perusahaan:') }}</span>
                                                    @if($softFile->is_all_companies)
                                                        <span class="badge badge-primary badge-xs font-semibold">{{ __('Semua Perusahaan') }}</span>
                                                    @else
                                                        @forelse($softFile->companies as $comp)
                                                            <span class="badge badge-outline badge-xs font-medium">{{ $comp->name }}</span>
                                                        @empty
                                                            <span class="text-xs text-base-content/40 italic">{{ __('Tidak ada') }}</span>
                                                        @endforelse
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-[11px] font-semibold text-base-content/50 uppercase">{{ __('Cabang:') }}</span>
                                                    @if($softFile->is_all_branches)
                                                        <span class="badge badge-secondary badge-xs font-semibold">{{ __('Semua Cabang') }}</span>
                                                    @else
                                                        @forelse($softFile->branches as $br)
                                                            <span class="badge badge-ghost badge-xs">{{ $br->name }}</span>
                                                        @empty
                                                            <span class="text-xs text-base-content/40 italic">{{ __('Tidak ada') }}</span>
                                                        @endforelse
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 min-w-[140px]">
                                            @if(empty($softFile->allowed_roles))
                                                <span class="badge badge-neutral badge-xs">{{ __('Semua Role') }}</span>
                                            @else
                                                <div class="flex items-center gap-1 flex-wrap">
                                                    @foreach($softFile->allowed_roles as $role)
                                                        @php
                                                            $roleDisplay = match($role) {
                                                                'staff', 'user' => 'Staff',
                                                                'head' => 'Head',
                                                                'direktur' => 'Direktur',
                                                                default => ucfirst($role)
                                                            };
                                                        @endphp
                                                        <span class="badge badge-info badge-outline badge-xs font-semibold">{{ $roleDisplay }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            @if($softFile->isActive())
                                                <span class="badge badge-success badge-sm gap-1 font-semibold">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    {{ __('Aktif') }}
                                                </span>
                                            @else
                                                <span class="badge badge-ghost badge-sm text-base-content/50">{{ __('Diarsipkan') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" 
                                                        class="btn-trigger-sf-preview btn btn-ghost btn-xs btn-square text-primary" 
                                                        onclick="triggerSoftFilePreview(this)"
                                                        data-id="{{ $softFile->id }}"
                                                        data-title="{{ $softFile->title }}"
                                                        data-type="{{ $softFile->file_type }}"
                                                        data-filename="{{ $softFile->file_original_name }}"
                                                        data-filesize="{{ $softFile->file_size ? number_format($softFile->file_size / 1024, 1) . ' KB' : '' }}"
                                                        data-is-image="{{ $softFile->isImage() ? '1' : '0' }}"
                                                        data-is-pdf="{{ $softFile->isPdf() ? '1' : '0' }}"
                                                        data-content-url="{{ route('admin.corporate-soft-files.preview-content', $softFile) }}"
                                                        data-preview-url="{{ route('admin.corporate-soft-files.preview', $softFile) }}"
                                                        data-download-url="{{ route('admin.corporate-soft-files.download', $softFile) }}"
                                                        title="{{ __('Preview Soft File') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                                <a href="{{ route('admin.corporate-soft-files.download', $softFile) }}" class="btn btn-ghost btn-xs btn-square" title="{{ __('Unduh File') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-base-content/70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                </a>
                                                <a href="{{ route('admin.corporate-soft-files.edit', $softFile) }}" class="btn btn-ghost btn-xs btn-square" title="{{ __('Edit') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-base-content/70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                </a>
                                                <form method="POST" action="{{ route('admin.corporate-soft-files.toggle-status', $softFile) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-ghost btn-xs btn-square" title="{{ $softFile->isActive() ? __('Arsipkan') : __('Aktifkan') }}">
                                                        @if($softFile->isActive())
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        @endif
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.corporate-soft-files.destroy', $softFile) }}" class="inline" onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus soft file korporat ini?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-ghost btn-xs btn-square text-error" title="{{ __('Hapus') }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-base-content/50">
                                            <div class="max-w-xs mx-auto text-center space-y-2">
                                                <svg class="w-12 h-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="font-medium text-sm">{{ __('Belum ada soft file korporat yang ditambahkan.') }}</p>
                                                <a href="{{ route('admin.corporate-soft-files.create') }}" class="btn btn-primary btn-xs rounded-xl">{{ __('Tambah Soft File Sekarang') }}</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($softFiles->hasPages())
                <div class="mt-4">
                    {{ $softFiles->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Interactive Corporate Soft File Preview Modal --}}
    <dialog id="softfile-preview-modal" class="modal modal-bottom sm:modal-middle backdrop-blur-xs">
        <div id="modal-sf-box" class="modal-box p-0 rounded-2xl sm:rounded-3xl border border-base-300 shadow-2xl bg-base-100 max-w-[96vw] w-[96vw] flex flex-col h-[94vh] sm:h-[95vh] overflow-hidden transition-all duration-200">
            {{-- Modal Header --}}
            <div class="px-4 sm:px-6 py-3 border-b border-base-200 flex items-center justify-between gap-3 shrink-0 bg-base-100/95 backdrop-blur-md">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0" id="modal-sf-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 id="modal-sf-title" class="font-bold text-sm sm:text-base text-base-content truncate"></h3>
                            <span id="modal-sf-badge" class="badge badge-primary badge-xs font-mono font-bold"></span>
                        </div>
                        <p id="modal-sf-subtitle" class="text-[11px] text-base-content/50 truncate"></p>
                    </div>
                </div>

                {{-- Image Zoom Toolbar (Visible only when viewing image) --}}
                <div id="modal-sf-img-tools" class="hidden items-center gap-1 bg-base-200/80 px-2 py-1 rounded-xl border border-base-300/80">
                    <button type="button" onclick="zoomSoftFileImage(-0.2)" class="btn btn-ghost btn-xs btn-square" title="{{ __('Perkecil') }}">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                    </button>
                    <span id="modal-sf-zoom-val" class="text-[11px] font-mono font-bold px-1 min-w-[40px] text-center text-base-content/80">100%</span>
                    <button type="button" onclick="zoomSoftFileImage(0.2)" class="btn btn-ghost btn-xs btn-square" title="{{ __('Perbesar') }}">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <button type="button" onclick="resetSoftFileImageZoom()" class="btn btn-ghost btn-xs px-1.5 text-[11px] font-medium" title="{{ __('Reset Ukuran Asli') }}">
                        {{ __('Reset') }}
                    </button>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button" onclick="toggleModalFullscreen()" id="modal-sf-maximize-btn" class="btn btn-ghost btn-xs rounded-xl gap-1" title="{{ __('Maksimalkan Layar') }}">
                        <svg id="modal-sf-maximize-icon" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        <span class="hidden md:inline" id="modal-sf-maximize-text">{{ __('Layar Penuh') }}</span>
                    </button>
                    <a id="modal-sf-fullscreen-btn" href="#" target="_blank" class="btn btn-ghost btn-xs rounded-xl gap-1" title="{{ __('Buka di Tab Baru') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('Tab Baru') }}</span>
                    </a>
                    <a id="modal-sf-download-btn" href="#" class="btn btn-primary btn-xs rounded-xl gap-1 shadow-xs" title="{{ __('Unduh Berkas') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('Unduh') }}</span>
                    </a>
                    <button type="button" onclick="closeSoftFilePreviewModal()" class="btn btn-ghost btn-xs btn-square rounded-xl text-base-content/60 hover:text-base-content" title="{{ __('Tutup (ESC)') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body Viewport --}}
            <div class="flex-1 bg-base-200/40 p-0 overflow-hidden relative flex flex-col items-center justify-center">
                {{-- Spinner Loader --}}
                <div id="modal-sf-loader" class="absolute inset-0 bg-base-100/90 z-20 flex flex-col items-center justify-center gap-2">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                    <span class="text-xs text-base-content/60 font-medium">{{ __('Memuat pratinjau soft file...') }}</span>
                </div>

                {{-- Image Viewport Container --}}
                <div id="modal-sf-image-container" class="hidden w-full h-full flex flex-col items-center justify-start overflow-auto p-4 sm:p-8 bg-slate-900/5 dark:bg-base-300/30">
                    <div class="my-auto w-full max-w-5xl flex flex-col items-center justify-center transition-transform duration-150" id="modal-sf-img-wrapper">
                        <img id="modal-sf-image" src="" alt="Soft File Preview" class="w-full max-h-none object-contain rounded-2xl shadow-2xl border border-base-300 bg-white transition-all">
                    </div>
                </div>

                {{-- ONLYOFFICE / Document Viewer Container --}}
                <div id="modal-sf-doc-container" class="hidden w-full h-full overflow-hidden bg-base-100">
                    <div id="modal-onlyoffice-container" class="w-full h-full"></div>
                </div>

                {{-- Fallback Box if ONLYOFFICE unavailable --}}
                <div id="modal-sf-fallback" class="hidden max-w-sm p-6 bg-base-100 rounded-2xl border border-base-300 shadow-xl text-center space-y-3 z-10">
                    <div class="w-10 h-10 rounded-full bg-warning/20 text-warning flex items-center justify-center mx-auto">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h4 class="font-bold text-sm">{{ __('Pratinjau Interaktif Memerlukan Server ONLYOFFICE') }}</h4>
                    <p class="text-xs text-base-content/60">{{ __('Anda tetap dapat mengunduh soft file ini secara langsung.') }}</p>
                    <a id="modal-fallback-download-btn" href="#" class="btn btn-primary btn-xs rounded-xl">{{ __('Unduh Berkas') }}</a>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" onclick="closeSoftFilePreviewModal()">close</button>
        </form>
    </dialog>

    @push('scripts')
        <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js?v=9.4.0-f4-v2" onerror="window._onlyofficeFailed = true;"></script>
        <script>
            let currentModalDocEditor = null;
            let currentImageZoom = 1.0;
            let isModalFullscreen = false;

            window.toggleModalFullscreen = function() {
                const box = document.getElementById('modal-sf-box');
                const text = document.getElementById('modal-sf-maximize-text');
                isModalFullscreen = !isModalFullscreen;

                if (isModalFullscreen) {
                    box.classList.remove('max-w-[96vw]', 'w-[96vw]', 'h-[94vh]', 'sm:h-[95vh]', 'rounded-2xl', 'sm:rounded-3xl');
                    box.classList.add('max-w-none', 'w-screen', 'h-screen', 'rounded-none');
                    if (text) text.textContent = '{{ __("Perkecil") }}';
                } else {
                    box.classList.remove('max-w-none', 'w-screen', 'h-screen', 'rounded-none');
                    box.classList.add('max-w-[96vw]', 'w-[96vw]', 'h-[94vh]', 'sm:h-[95vh]', 'rounded-2xl', 'sm:rounded-3xl');
                    if (text) text.textContent = '{{ __("Layar Penuh") }}';
                }
            };

            window.zoomSoftFileImage = function(delta) {
                currentImageZoom = Math.max(0.4, Math.min(3.0, currentImageZoom + delta));
                applySoftFileImageZoom();
            };

            window.resetSoftFileImageZoom = function() {
                currentImageZoom = 1.0;
                applySoftFileImageZoom();
            };

            function applySoftFileImageZoom() {
                const img = document.getElementById('modal-sf-image');
                const val = document.getElementById('modal-sf-zoom-val');
                if (img) {
                    img.style.transform = `scale(${currentImageZoom})`;
                    img.style.transformOrigin = 'center top';
                }
                if (val) {
                    val.textContent = Math.round(currentImageZoom * 100) + '%';
                }
            }

            window.triggerSoftFilePreview = function(el) {
                if (!el) return;
                const d = el.dataset;
                window.openSoftFilePreviewModal(
                    d.id,
                    d.title,
                    d.type,
                    d.filename,
                    d.filesize,
                    d.isImage === '1',
                    d.isPdf === '1',
                    d.contentUrl,
                    d.previewUrl,
                    d.downloadUrl
                );
            };

            window.openSoftFilePreviewModal = function(id, title, fileType, fileName, fileSize, isImage, isPdf, contentUrl, previewUrl, downloadUrl) {
                const modal = document.getElementById('softfile-preview-modal');
                const titleEl = document.getElementById('modal-sf-title');
                const badgeEl = document.getElementById('modal-sf-badge');
                const subtitleEl = document.getElementById('modal-sf-subtitle');
                const fullscreenBtn = document.getElementById('modal-sf-fullscreen-btn');
                const downloadBtn = document.getElementById('modal-sf-download-btn');
                const fallbackDownloadBtn = document.getElementById('modal-fallback-download-btn');
                const loader = document.getElementById('modal-sf-loader');
                const imageContainer = document.getElementById('modal-sf-image-container');
                const imgTools = document.getElementById('modal-sf-img-tools');
                const docContainer = document.getElementById('modal-sf-doc-container');
                const fallbackBox = document.getElementById('modal-sf-fallback');
                const imageEl = document.getElementById('modal-sf-image');

                if (titleEl) titleEl.textContent = title || '';
                if (badgeEl) {
                    badgeEl.textContent = fileType || '';
                    badgeEl.className = 'badge ' + (fileType === 'PDF' ? 'badge-error' : (isImage ? 'badge-secondary' : 'badge-primary')) + ' badge-xs font-mono font-bold';
                }
                if (subtitleEl) subtitleEl.textContent = (fileName || '') + (fileSize ? ' • ' + fileSize : '');
                if (fullscreenBtn) fullscreenBtn.href = previewUrl || '#';
                if (downloadBtn) downloadBtn.href = downloadUrl || '#';
                if (fallbackDownloadBtn) fallbackDownloadBtn.href = downloadUrl || '#';

                // Reset viewports
                if (loader) loader.classList.remove('hidden');
                if (imageContainer) imageContainer.classList.add('hidden');
                if (imgTools) imgTools.classList.add('hidden');
                if (docContainer) docContainer.classList.add('hidden');
                if (fallbackBox) fallbackBox.classList.add('hidden');

                currentImageZoom = 1.0;
                applySoftFileImageZoom();

                if (currentModalDocEditor) {
                    try { currentModalDocEditor.destroyEditor(); } catch(e) {}
                    currentModalDocEditor = null;
                }
                const onlyContainer = document.getElementById('modal-onlyoffice-container');
                if (onlyContainer) onlyContainer.innerHTML = '';

                if (modal) {
                    if (typeof modal.showModal === 'function') {
                        modal.showModal();
                    } else {
                        modal.classList.add('modal-open');
                    }
                }

                if (isImage) {
                    // Image soft file: Full resolution preview with zoom tools
                    if (imgTools) imgTools.classList.remove('hidden');
                    if (imageEl) {
                        imageEl.src = contentUrl;
                        imageEl.onload = function() {
                            if (loader) loader.classList.add('hidden');
                            if (imageContainer) imageContainer.classList.remove('hidden');
                        };
                        imageEl.onerror = function() {
                            if (loader) loader.classList.add('hidden');
                            if (fallbackBox) fallbackBox.classList.remove('hidden');
                        };
                    }
                } else {
                    // DOCX / PDF Document: fetch ONLYOFFICE config
                    if (docContainer) docContainer.classList.remove('hidden');

                    fetch("{{ url('/admin/corporate-soft-files') }}/" + id + "/preview-config", {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    })
                    .then(res => res.json())
                    .then(config => {
                        if (loader) loader.classList.add('hidden');
                        if (typeof DocsAPI !== 'undefined' && !window._onlyofficeFailed) {
                            try {
                                config.type = 'embedded';
                                config.document = config.document || {};
                                config.document.permissions = config.document.permissions || {};
                                config.document.permissions.edit = false;
                                config.document.permissions.review = false;
                                config.document.permissions.comment = false;
                                config.document.permissions.fillForms = false;
                                config.document.permissions.modifyFilter = false;
                                config.document.permissions.modifyContentControl = false;

                                config.editorConfig = config.editorConfig || {};
                                config.editorConfig.mode = 'view';
                                config.editorConfig.canEdit = false;
                                config.editorConfig.customization = config.editorConfig.customization || {};
                                config.editorConfig.customization.compactHeader = true;
                                config.editorConfig.customization.toolbarNoTabs = true;
                                config.editorConfig.customization.toolbarHideFileName = false;
                                config.editorConfig.customization.autosave = false;
                                config.editorConfig.customization.forcesave = false;
                                config.editorConfig.customization.leftMenu = false;
                                config.editorConfig.customization.rightMenu = false;
                                config.editorConfig.customization.embedded = config.editorConfig.customization.embedded || { toolbarDockPosition: 'bottom' };
                                config.editorConfig.customization.zoom = -2; // Fit to Width

                                currentModalDocEditor = new DocsAPI.DocEditor("modal-onlyoffice-container", config);
                            } catch (err) {
                                console.error("ONLYOFFICE modal editor init error:", err);
                                if (docContainer) docContainer.classList.add('hidden');
                                if (fallbackBox) fallbackBox.classList.remove('hidden');
                            }
                        } else {
                            if (docContainer) docContainer.classList.add('hidden');
                            if (fallbackBox) fallbackBox.classList.remove('hidden');
                        }
                    })
                    .catch(err => {
                        console.error("Failed to load preview config:", err);
                        if (loader) loader.classList.add('hidden');
                        if (docContainer) docContainer.classList.add('hidden');
                        if (fallbackBox) fallbackBox.classList.remove('hidden');
                    });
                }
            };

            window.closeSoftFilePreviewModal = function() {
                const modal = document.getElementById('softfile-preview-modal');
                if (currentModalDocEditor) {
                    try { currentModalDocEditor.destroyEditor(); } catch(e) {}
                    currentModalDocEditor = null;
                }
                const onlyContainer = document.getElementById('modal-onlyoffice-container');
                if (onlyContainer) onlyContainer.innerHTML = '';
                if (modal) {
                    if (typeof modal.close === 'function') {
                        modal.close();
                    } else {
                        modal.classList.remove('modal-open');
                    }
                }
            };

            // Global delegated event listener
            document.addEventListener('click', function(e) {
                const trigger = e.target.closest('.btn-trigger-sf-preview');
                if (trigger) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.triggerSoftFilePreview(trigger);
                }
            });
        </script>
    @endpush
</x-app-layout>


