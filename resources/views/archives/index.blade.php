<x-app-layout>
    <x-slot name="header">{{ __('Buku Induk & Registrasi Arsip') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- Header Actions --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-bold text-base-content flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        <span>{{ __('Buku Induk Arsip (Archive Registry)') }}</span>
                        <button type="button" 
                                onclick="document.getElementById('archive-info-modal').showModal()" 
                                class="btn btn-ghost btn-circle btn-xs text-base-content/40 hover:text-info hover:bg-info/10 transition-colors"
                                title="{{ __('Mengapa dokumen perlu diarsipkan? (Bandingkan dengan Dokumen Saya)') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </h3>
                    <p class="text-sm text-base-content/60">
                        {{ __('Pusat repositori rekaman resmi berintegritas tinggi dengan nomor arsip unik dan snapshot kriptografis SHA-256 imutabel.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <button type="button" 
                            onclick="document.getElementById('archive-info-modal').showModal()" 
                            class="btn btn-ghost btn-sm border border-base-300 gap-1.5 text-base-content/75 hover:text-primary hover:border-primary/40 shadow-2xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-info shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ __('Fungsi Pengarsipan') }}</span>
                    </button>

                    <a href="{{ route('archives.export', request()->query()) }}" class="btn btn-outline btn-sm gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('Ekspor CSV') }}
                    </a>

                    <a href="{{ route('archive-requests.index') }}" class="btn btn-primary btn-sm gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('Permohonan Arsip') }}
                    </a>
                </div>
            </div>

            {{-- Flash Alert Messages --}}
            @if(session('success'))
                <div class="alert alert-success shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Stat KPI Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="card bg-base-100 border border-base-200 shadow-sm p-4">
                    <div class="text-xs font-semibold text-base-content/60 uppercase tracking-wider">{{ __('Total Arsip Terdaftar') }}</div>
                    <div class="text-2xl font-black text-primary mt-1">{{ number_format($stats['total']) }}</div>
                    <div class="text-xs text-base-content/50 mt-1">{{ __('Rekaman aktif berintegritas') }}</div>
                </div>

                <div class="card bg-base-100 border border-base-200 shadow-sm p-4">
                    <div class="text-xs font-semibold text-base-content/60 uppercase tracking-wider">{{ __('Dalam Legal Hold') }}</div>
                    <div class="text-2xl font-black text-error mt-1">{{ number_format($stats['holds']) }}</div>
                    <div class="text-xs text-error/70 mt-1">{{ __('Dibekukan dari penyusutan') }}</div>
                </div>

                <div class="card bg-base-100 border border-base-200 shadow-sm p-4">
                    <div class="text-xs font-semibold text-base-content/60 uppercase tracking-wider">{{ __('Review & Jatuh Tempo') }}</div>
                    <div class="text-2xl font-black text-warning mt-1">{{ number_format($stats['review']) }}</div>
                    <div class="text-xs text-warning/80 mt-1">{{ __('Mendekati masa retensi') }}</div>
                </div>

                <div class="card bg-base-100 border border-base-200 shadow-sm p-4">
                    <div class="text-xs font-semibold text-base-content/60 uppercase tracking-wider">{{ __('Arsip Permanen') }}</div>
                    <div class="text-2xl font-black text-info mt-1">{{ number_format($stats['permanent']) }}</div>
                    <div class="text-xs text-info/80 mt-1">{{ __('Disimpan tanpa batas waktu') }}</div>
                </div>
            </div>

            {{-- Filters & Search Bar --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm p-4">
                <form method="GET" action="{{ route('archives.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                    <div class="md:col-span-2">
                        <label class="label py-1"><span class="label-text text-xs font-semibold">{{ __('Pencarian') }}</span></label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="{{ __('Nomor arsip, judul, kode dokumen...') }}"
                               class="input input-sm input-bordered w-full" />
                    </div>

                    <div>
                        <label class="label py-1"><span class="label-text text-xs font-semibold">{{ __('Klasifikasi') }}</span></label>
                        <select name="classification_id" class="select select-sm select-bordered w-full">
                            <option value="">{{ __('Semua Klasifikasi') }}</option>
                            @foreach($classifications as $c)
                                <option value="{{ $c->id }}" {{ request('classification_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->code }} - {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label py-1"><span class="label-text text-xs font-semibold">{{ __('Status Arsip') }}</span></label>
                        <select name="status" class="select select-sm select-bordered w-full">
                            <option value="">{{ __('Semua Status') }}</option>
                            <option value="registered" {{ request('status') === 'registered' ? 'selected' : '' }}>{{ __('Terdaftar (Registered)') }}</option>
                            <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>{{ __('Sedang Ditinjau (Under Review)') }}</option>
                            <option value="eligible_for_disposition" {{ request('status') === 'eligible_for_disposition' ? 'selected' : '' }}>{{ __('Siap Dimusnahkan/Penyusutan') }}</option>
                            <option value="permanent" {{ request('status') === 'permanent' ? 'selected' : '' }}>{{ __('Permanen') }}</option>
                            <option value="disposed" {{ request('status') === 'disposed' ? 'selected' : '' }}>{{ __('Telah Dimusnahkan (Disposed)') }}</option>
                            <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>{{ __('Dibatalkan (Voided)') }}</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-1">
                            {{ __('Filter') }}
                        </button>
                        <a href="{{ route('archives.index') }}" class="btn btn-sm btn-ghost">
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            {{-- Archive Records Table --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-hover w-full text-sm">
                        <thead class="bg-base-200/60 text-base-content/70">
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th>{{ __('Nomor Arsip Resmi') }}</th>
                                <th>{{ __('Judul Dokumen') }}</th>
                                <th>{{ __('Klasifikasi & Unit') }}</th>
                                <th>{{ __('Tgl Registrasi') }}</th>
                                <th>{{ __('Status & Retensi') }}</th>
                                <th class="text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $index => $entry)
                                <tr class="hover">
                                    <td class="text-center font-mono text-xs text-base-content/50">
                                        {{ $entries->firstItem() + $index }}
                                    </td>
                                    <td>
                                        <div class="font-mono font-bold text-xs text-primary flex items-center gap-1.5">
                                            <span>{{ $entry->archive_number }}</span>
                                            @if($entry->is_under_legal_hold)
                                                <span class="badge badge-error badge-xs font-bold text-white uppercase">{{ __('HOLD') }}</span>
                                            @endif
                                        </div>
                                        @if($entry->original_document_number)
                                            <div class="text-[11px] text-base-content/50">
                                                {{ __('Asli:') }} {{ $entry->original_document_number }} (v{{ $entry->version_number_snapshot }})
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-semibold text-base-content line-clamp-1">
                                            <a href="{{ route('archives.show', $entry->id) }}" class="hover:underline hover:text-primary">
                                                {{ $entry->title }}
                                            </a>
                                        </div>
                                        <div class="text-[11px] text-base-content/50 flex items-center gap-2 mt-0.5">
                                            <span class="badge badge-outline badge-xs">{{ ucfirst($entry->sensitivity_level) }}</span>
                                            <span>{{ $entry->owner_name_snapshot }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-medium text-xs text-base-content">
                                            {{ $entry->classification_code_snapshot }}
                                        </div>
                                        <div class="text-[11px] text-base-content/50">
                                            {{ $entry->branch_code_snapshot }} / {{ $entry->unit_code_snapshot }}
                                        </div>
                                    </td>
                                    <td class="text-xs">
                                        <div>{{ $entry->registered_at?->format('d M Y') }}</div>
                                        <div class="text-[11px] text-base-content/50">{{ $entry->registered_at?->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        @if($entry->status === 'registered')
                                            <span class="badge badge-success badge-sm font-semibold">{{ __('Terdaftar') }}</span>
                                        @elseif($entry->status === 'under_review')
                                            <span class="badge badge-warning badge-sm font-semibold">{{ __('Review') }}</span>
                                        @elseif($entry->status === 'eligible_for_disposition')
                                            <span class="badge badge-error badge-sm font-semibold">{{ __('Penyusutan') }}</span>
                                        @elseif($entry->status === 'permanent')
                                            <span class="badge badge-info badge-sm font-semibold">{{ __('Permanen') }}</span>
                                        @elseif($entry->status === 'disposed')
                                            <span class="badge badge-neutral badge-sm font-semibold">{{ __('Dimusnahkan') }}</span>
                                        @elseif($entry->status === 'voided')
                                            <span class="badge badge-neutral badge-sm line-through">{{ __('Voided') }}</span>
                                        @endif

                                        <div class="text-[11px] text-base-content/50 mt-0.5">
                                            @if($entry->isPermanent())
                                                {{ __('Retensi: Tanpa Batas') }}
                                            @elseif($entry->retention_due_at)
                                                {{ __('Jatuh Tempo:') }} {{ $entry->retention_due_at->format('d/m/Y') }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <div class="dropdown dropdown-end">
                                            <label tabindex="0" class="btn btn-ghost btn-xs btn-square">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                </svg>
                                            </label>
                                            <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-48 text-xs border border-base-200">
                                                <li>
                                                    <a href="{{ route('archives.show', $entry->id) }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                        {{ __('Lihat Detail') }}
                                                    </a>
                                                </li>
                                                @if(!$entry->isDisposed())
                                                    <li>
                                                        <a href="{{ route('archives.download', $entry->id) }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                            {{ __('Unduh Berkas Asli') }}
                                                        </a>
                                                    </li>
                                                @endif
                                                <li>
                                                    <form method="POST" action="{{ route('archives.verify-integrity', $entry->id) }}" class="p-0">
                                                        @csrf
                                                        <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                                            {{ __('Cek SHA-256') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-base-content/50">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-base-content/30 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                            <div class="font-bold text-base">{{ __('Belum Ada Rekaman Arsip') }}</div>
                                            <p class="text-xs max-w-sm mt-1">
                                                {{ __('Dokumen yang telah difinalisasi dan ditandatangani dapat didaftarkan menjadi rekaman arsip resmi melalui menu Permohonan Arsip.') }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($entries->hasPages())
                    <div class="p-4 border-t border-base-200">
                        {{ $entries->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

