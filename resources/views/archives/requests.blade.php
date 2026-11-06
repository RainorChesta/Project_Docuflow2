<x-app-layout>
    <x-slot name="header">{{ __('Manajemen Permohonan Pengarsipan') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- Header Actions --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-bold text-base-content flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>{{ __('Alur Kerja & Permohonan Arsip') }}</span>
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
                        {{ __('Kelola proses pengajuan, review kelayakan, verifikasi persetujuan, dan finalisasi registrasi arsip resmi.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" 
                            onclick="document.getElementById('archive-info-modal').showModal()" 
                            class="btn btn-ghost btn-sm border border-base-300 gap-1.5 text-base-content/75 hover:text-primary hover:border-primary/40 shadow-2xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-info shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ __('Fungsi Pengarsipan') }}</span>
                    </button>

                    <a href="{{ route('archives.index') }}" class="btn btn-outline btn-sm gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        {{ __('Ke Buku Induk Arsip') }}
                    </a>
                </div>
            </div>

            {{-- Flash Messages --}}
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

            {{-- Tabs --}}
            <div class="tabs tabs-boxed bg-base-200/80 p-1 w-fit">
                <a href="{{ route('archive-requests.index', ['tab' => 'my_requests']) }}"
                   class="tab tab-sm font-semibold {{ $tab === 'my_requests' ? 'tab-active' : '' }}">
                    {{ __('Permohonan Saya') }}
                    <span class="badge badge-sm ml-2 {{ $tab === 'my_requests' ? 'badge-primary' : 'badge-ghost' }}">
                        {{ $myRequests->total() }}
                    </span>
                </a>

                @if(auth()->user()->isAdmin() || auth()->user()->isDirector() || auth()->user()->isPicKlinik() || auth()->user()->isHead())
                <a href="{{ route('archive-requests.index', ['tab' => 'approval_queue']) }}"
                   class="tab tab-sm font-semibold {{ $tab === 'approval_queue' ? 'tab-active' : '' }}">
                    {{ __('Antrean Persetujuan (Approval Queue)') }}
                    @if($pendingApprovals->total() > 0)
                        <span class="badge badge-sm badge-error text-white ml-2">
                            {{ $pendingApprovals->total() }}
                        </span>
                    @endif
                </a>
                @endif
            </div>

            @if($tab === 'my_requests')
                {{-- My Requests Table --}}
                <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full text-sm">
                            <thead class="bg-base-200/60 text-base-content/70">
                                <tr>
                                    <th>{{ __('Judul Permohonan') }}</th>
                                    <th>{{ __('Klasifikasi') }}</th>
                                    <th>{{ __('Status Alur Kerja') }}</th>
                                    <th>{{ __('Nomor Arsip Terbit') }}</th>
                                    <th>{{ __('Tgl Pengajuan') }}</th>
                                    <th class="text-right">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($myRequests as $req)
                                    <tr class="hover">
                                        <td>
                                            <div class="font-bold text-base-content">
                                                <a href="{{ route('archive-requests.show', $req->id) }}" class="hover:underline hover:text-primary">
                                                    {{ $req->archive_title }}
                                                </a>
                                            </div>
                                            <div class="text-xs text-base-content/50">
                                                {{ __('Dokumen:') }} {{ $req->document?->document_number ?? '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-medium text-xs">{{ $req->classification?->code }}</div>
                                            <div class="text-[11px] text-base-content/50">{{ $req->classification?->name }}</div>
                                        </td>
                                        <td>
                                            @if($req->state === 'draft')
                                                <span class="badge badge-ghost badge-sm">{{ __('Draf') }}</span>
                                            @elseif($req->state === 'pending_approval')
                                                <span class="badge badge-warning badge-sm font-semibold">{{ __('Menunggu Persetujuan') }}</span>
                                            @elseif($req->state === 'approved')
                                                <span class="badge badge-info badge-sm font-semibold">{{ __('Disetujui — Siap Terbit') }}</span>
                                            @elseif($req->state === 'registered')
                                                <span class="badge badge-success badge-sm font-semibold">{{ __('Telah Terdaftar') }}</span>
                                            @elseif($req->state === 'rejected')
                                                <span class="badge badge-error badge-sm">{{ __('Ditolak') }}</span>
                                            @elseif($req->state === 'returned_for_correction')
                                                <span class="badge badge-warning badge-sm">{{ __('Perlu Perbaikan') }}</span>
                                            @elseif($req->state === 'canceled')
                                                <span class="badge badge-neutral badge-sm">{{ __('Dibatalkan') }}</span>
                                            @endif
                                        </td>
                                        <td class="font-mono text-xs">
                                            @if($req->archiveEntry)
                                                <a href="{{ route('archives.show', $req->archiveEntry->id) }}" class="link link-primary font-bold">
                                                    {{ $req->archiveEntry->archive_number }}
                                                </a>
                                            @else
                                                <span class="text-base-content/40">-</span>
                                            @endif
                                        </td>
                                        <td class="text-xs text-base-content/70">
                                            {{ $req->created_at?->format('d M Y H:i') }}
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('archive-requests.show', $req->id) }}" class="btn btn-ghost btn-xs">
                                                    {{ __('Rincian') }}
                                                </a>
                                                @can('delete', $req)
                                                    <form method="POST" action="{{ route('archive-requests.destroy', $req->id) }}" onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus permohonan ini secara permanen?') }}');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-ghost btn-xs text-error hover:bg-error/10" title="{{ __('Hapus Permohonan') }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-base-content/50">
                                            {{ __('Anda belum memiliki riwayat permohonan pengarsipan.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($myRequests->hasPages())
                        <div class="p-4 border-t border-base-200">
                            {{ $myRequests->links() }}
                        </div>
                    @endif
                </div>

            @else
                {{-- Approvals Queue Table --}}
                <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full text-sm">
                            <thead class="bg-base-200/60 text-base-content/70">
                                <tr>
                                    <th>{{ __('Pemohon') }}</th>
                                    <th>{{ __('Judul Dokumen & Arsip') }}</th>
                                    <th>{{ __('Klasifikasi & Unit') }}</th>
                                    <th>{{ __('Tgl Diajukan') }}</th>
                                    <th class="text-right">{{ __('Keputusan') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingApprovals as $item)
                                    <tr class="hover">
                                        <td>
                                            <div class="font-bold text-xs">{{ $item->requester?->name }}</div>
                                            <div class="text-[11px] text-base-content/50">{{ $item->requester?->nip ?? $item->requester?->email }}</div>
                                        </td>
                                        <td>
                                            <div class="font-bold text-base-content">
                                                <a href="{{ route('archive-requests.show', $item->id) }}" class="hover:underline hover:text-primary">
                                                    {{ $item->archive_title }}
                                                </a>
                                            </div>
                                            <div class="text-xs text-base-content/50">
                                                {{ __('Asli:') }} {{ $item->document?->document_number ?? '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-medium text-xs">{{ $item->classification?->code }}</div>
                                            <div class="text-[11px] text-base-content/50">{{ $item->branch?->code }} / {{ $item->unitKerja?->kode_unit_kerja }}</div>
                                        </td>
                                        <td class="text-xs text-base-content/70">
                                            {{ $item->created_at?->diffForHumans() }}
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('archive-requests.show', $item->id) }}" class="btn btn-sm btn-primary">
                                                {{ __('Tinjau & Putuskan') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-base-content/50">
                                            {{ __('Tidak ada antrean permohonan arsip yang memerlukan persetujuan Anda.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($pendingApprovals->hasPages())
                        <div class="p-4 border-t border-base-200">
                            {{ $pendingApprovals->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

