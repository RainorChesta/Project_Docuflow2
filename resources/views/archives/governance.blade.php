<x-app-layout>
    <x-slot name="header">{{ __('Tata Kelola, Retensi & Penyusutan Arsip') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- Top Header --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-bold text-base-content flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        {{ __('Pusat Tata Kelola Arsip (Records Governance)') }}
                    </h3>
                    <p class="text-sm text-base-content/60">
                        {{ __('Pemantauan siklus hidup Jadwal Retensi Arsip (JRA), kontrol pembekuan hukum (legal holds), dan persetujuan penyusutan/pemusnahan berkeabsahan hukum.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('archives.index') }}" class="btn btn-outline btn-sm gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        {{ __('Buku Induk Arsip') }}
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
                <a href="{{ route('archive-governance.index', ['tab' => 'retention']) }}"
                   class="tab tab-sm font-semibold {{ $tab === 'retention' ? 'tab-active' : '' }}">
                    {{ __('Jatuh Tempo Retensi') }}
                    <span class="badge badge-sm ml-2 {{ $tab === 'retention' ? 'badge-primary' : 'badge-ghost' }}">
                        {{ $retentionEntries->total() }}
                    </span>
                </a>

                <a href="{{ route('archive-governance.index', ['tab' => 'holds']) }}"
                   class="tab tab-sm font-semibold {{ $tab === 'holds' ? 'tab-active' : '' }}">
                    {{ __('Pembekuan Hukum (Legal Holds)') }}
                    <span class="badge badge-sm ml-2 {{ $tab === 'holds' ? 'badge-primary' : 'badge-ghost' }}">
                        {{ $legalHolds->total() }}
                    </span>
                </a>

                <a href="{{ route('archive-governance.index', ['tab' => 'disposition']) }}"
                   class="tab tab-sm font-semibold {{ $tab === 'disposition' ? 'tab-active' : '' }}">
                    {{ __('Antrean Penyusutan & Berita Acara') }}
                    <span class="badge badge-sm ml-2 {{ $tab === 'disposition' ? 'badge-primary' : 'badge-ghost' }}">
                        {{ $dispositionRequests->total() }}
                    </span>
                </a>
            </div>

            @if($tab === 'retention')
                {{-- Retention Reviews Table --}}
                <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full text-sm">
                            <thead class="bg-base-200/60 text-base-content/70">
                                <tr>
                                    <th>{{ __('Nomor Arsip') }}</th>
                                    <th>{{ __('Judul Dokumen') }}</th>
                                    <th>{{ __('Tgl Jatuh Tempo') }}</th>
                                    <th>{{ __('Status Siklus Hidup') }}</th>
                                    <th>{{ __('Legal Hold') }}</th>
                                    <th class="text-right">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($retentionEntries as $entry)
                                    <tr class="hover">
                                        <td class="font-mono text-xs font-bold text-primary">
                                            <a href="{{ route('archives.show', $entry->id) }}" class="hover:underline">
                                                {{ $entry->archive_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="font-bold text-base-content">{{ $entry->title }}</div>
                                            <div class="text-xs text-base-content/50">{{ $entry->classification_code_snapshot }}</div>
                                        </td>
                                        <td class="text-xs">
                                            <span class="font-bold {{ $entry->retention_due_at?->isPast() ? 'text-error' : 'text-warning' }}">
                                                {{ $entry->retention_due_at?->format('d M Y') ?? '-' }}
                                            </span>
                                            <div class="text-[11px] text-base-content/50">{{ $entry->retention_due_at?->diffForHumans() }}</div>
                                        </td>
                                        <td>
                                            @if($entry->status === 'eligible_for_disposition')
                                                <span class="badge badge-error badge-sm font-semibold">{{ __('Siap Penyusutan') }}</span>
                                            @else
                                                <span class="badge badge-warning badge-sm font-semibold">{{ __('Under Review') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($entry->is_under_legal_hold)
                                                <span class="badge badge-error badge-xs font-bold text-white uppercase">{{ __('AKTIF') }}</span>
                                            @else
                                                <span class="text-xs text-base-content/40">{{ __('Tidak') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('archives.show', $entry->id) }}" class="btn btn-ghost btn-xs">
                                                {{ __('Buka Detail') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-base-content/50">
                                            {{ __('Tidak ada arsip yang memerlukan review retensi saat ini.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($retentionEntries->hasPages())
                        <div class="p-4 border-t border-base-200">
                            {{ $retentionEntries->links() }}
                        </div>
                    @endif
                </div>

            @elseif($tab === 'holds')
                {{-- Legal Holds Table --}}
                <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full text-sm">
                            <thead class="bg-base-200/60 text-base-content/70">
                                <tr>
                                    <th>{{ __('Referensi Kasus') }}</th>
                                    <th>{{ __('Nomor Arsip Terkait') }}</th>
                                    <th>{{ __('Judul Perkara / Alasan') }}</th>
                                    <th>{{ __('Diberlakukan Oleh') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($legalHolds as $hold)
                                    <tr class="hover">
                                        <td class="font-mono text-xs font-bold text-error">
                                            {{ $hold->case_reference }}
                                        </td>
                                        <td class="font-mono text-xs">
                                            @if($hold->archiveEntry)
                                                <a href="{{ route('archives.show', $hold->archiveEntry->id) }}" class="link link-primary font-bold">
                                                    {{ $hold->archiveEntry->archive_number }}
                                                </a>
                                            @else
                                                <span class="text-base-content/40">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-bold text-base-content">{{ $hold->title }}</div>
                                            <div class="text-xs text-base-content/60 line-clamp-1">{{ $hold->reason }}</div>
                                        </td>
                                        <td class="text-xs">
                                            <div>{{ $hold->appliedBy?->name }}</div>
                                            <div class="text-[11px] text-base-content/50">{{ $hold->applied_at?->format('d M Y H:i') }}</div>
                                        </td>
                                        <td>
                                            @if($hold->isActive())
                                                <span class="badge badge-error badge-sm font-semibold text-white">{{ __('Aktif (Hold)') }}</span>
                                            @else
                                                <span class="badge badge-ghost badge-sm">{{ __('Dicabut') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($hold->archiveEntry)
                                                <a href="{{ route('archives.show', $hold->archiveEntry->id) }}" class="btn btn-ghost btn-xs">
                                                    {{ __('Lihat Arsip') }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-base-content/50">
                                            {{ __('Belum ada riwayat pembekuan hukum (legal holds).') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($legalHolds->hasPages())
                        <div class="p-4 border-t border-base-200">
                            {{ $legalHolds->links() }}
                        </div>
                    @endif
                </div>

            @else
                {{-- Disposition Requests Table --}}
                <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full text-sm">
                            <thead class="bg-base-200/60 text-base-content/70">
                                <tr>
                                    <th>{{ __('Nomor Arsip') }}</th>
                                    <th>{{ __('Tindakan Penyusutan') }}</th>
                                    <th>{{ __('Pemohon & Waktu') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Nomor Berita Acara') }}</th>
                                    <th class="text-right">{{ __('Keputusan') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dispositionRequests as $disp)
                                    <tr class="hover">
                                        <td class="font-mono text-xs font-bold">
                                            @if($disp->archiveEntry)
                                                <a href="{{ route('archives.show', $disp->archiveEntry->id) }}" class="link link-primary">
                                                    {{ $disp->archiveEntry->archive_number }}
                                                </a>
                                            @else
                                                <span class="text-base-content/40">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-outline badge-sm uppercase font-bold">{{ $disp->action_type }}</span>
                                            <div class="text-xs text-base-content/60 mt-1 line-clamp-1">{{ $disp->justification }}</div>
                                        </td>
                                        <td class="text-xs">
                                            <div>{{ $disp->requester?->name }}</div>
                                            <div class="text-[11px] text-base-content/50">{{ $disp->requested_at?->format('d M Y H:i') }}</div>
                                        </td>
                                        <td>
                                            @if($disp->isPending())
                                                <span class="badge badge-warning badge-sm font-semibold">{{ __('Menunggu Persetujuan') }}</span>
                                            @elseif($disp->isExecuted())
                                                <span class="badge badge-success badge-sm font-semibold text-white">{{ __('Tereksekusi') }}</span>
                                            @elseif($disp->status === 'rejected')
                                                <span class="badge badge-error badge-sm">{{ __('Ditolak') }}</span>
                                            @endif
                                        </td>
                                        <td class="font-mono text-xs">
                                            @if($disp->certificate_number)
                                                <a href="{{ route('archives.dispositions.certificate', $disp->id) }}" target="_blank" class="link link-primary font-bold">
                                                    {{ $disp->certificate_number }}
                                                </a>
                                            @else
                                                <span class="text-base-content/40">-</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($disp->isPending() && (auth()->user()->isAdmin() || auth()->user()->isDirector()))
                                                <div class="flex items-center justify-end gap-1">
                                                    <form method="POST" action="{{ route('archives.dispositions.approve', $disp->id) }}" onsubmit="return confirm('{{ __('Konfirmasi eksekusi penyusutan/pemusnahan arsip ini secara sah?') }}');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-xs btn-success text-white">
                                                            {{ __('Setujui & Eksekusi') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif($disp->isExecuted())
                                                <a href="{{ route('archives.dispositions.certificate', $disp->id) }}" target="_blank" class="btn btn-xs btn-outline">
                                                    {{ __('Lihat Berita Acara') }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-base-content/50">
                                            {{ __('Belum ada riwayat permohonan penyusutan atau pemusnahan arsip.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($dispositionRequests->hasPages())
                        <div class="p-4 border-t border-base-200">
                            {{ $dispositionRequests->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

