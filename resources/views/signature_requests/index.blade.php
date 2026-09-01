<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-base-content leading-tight">
                    {{ __('Persetujuan & Riwayat TTD') }}
                </h2>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ __('Kelola permintaan penggunaan tanda tangan Anda serta riwayat pengajuan') }}
                </p>
            </div>
            @php $pendingIncomingCount = $incomingRequests->where('status', 'pending')->count(); @endphp
            <div class="flex items-center gap-2 shrink-0">
                @if($pendingIncomingCount > 0)
                    <span class="badge badge-warning gap-1.5 text-xs py-2.5 px-3 font-semibold shadow-sm">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-600 animate-ping"></span>
                        {{ $pendingIncomingCount }} {{ __('Menunggu Persetujuan') }}
                    </span>
                @else
                    <span class="badge badge-ghost gap-1.5 text-xs py-2.5 px-3 text-base-content/70">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        {{ __('Semua Tuntas') }}
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        @if(session('success'))
            <div class="alert alert-success shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error shadow-sm">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Incoming Requests Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-base-200/40 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Permintaan Tanda Tangan Masuk') }}
                            <span class="badge badge-primary badge-sm font-semibold">{{ $incomingRequests->total() }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Daftar pengguna yang meminta izin untuk menyematkan tanda tangan Anda pada dokumen.') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($incomingRequests->isEmpty())
                <div class="py-12 text-center text-base-content/50 space-y-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="text-sm font-medium">{{ __('Tidak ada permintaan tanda tangan masuk.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[720px]">
                        <thead>
                            <tr class="bg-base-200/40 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-5">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Pemohon') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu') }}</th>
                                <th class="py-3 px-4">{{ __('Status') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($incomingRequests as $req)
                                <tr class="hover:bg-base-200/40 transition-colors {{ $req->isPending() ? 'bg-warning/5 font-normal' : '' }}">
                                    <td class="px-5 py-4 max-w-xs">
                                        <div class="space-y-0.5">
                                            @if($req->document)
                                                <div class="font-semibold text-sm text-base-content break-words line-clamp-2">
                                                    {{ $req->document->title }}
                                                </div>
                                                @if($req->document->document_number)
                                                    <span class="text-xs font-mono text-base-content/50 block">{{ $req->document->document_number }}</span>
                                                @endif
                                            @else
                                                <span class="font-semibold text-sm text-base-content">{{ __('Dokumen Umum') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $req->requester->name }}</div>
                                        <div class="text-xs text-base-content/50">{{ $req->requester->email }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        {{ $req->requested_at ? $req->requested_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($req->isApproved())
                                            <span class="badge badge-success badge-sm gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                {{ __('Disetujui') }}
                                            </span>
                                        @elseif($req->isRejected())
                                            <div class="space-y-1">
                                                <span class="badge badge-error badge-sm gap-1 font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    {{ __('Ditolak') }}
                                                </span>
                                                @if($req->rejected_reason)
                                                    <p class="text-[11px] text-error max-w-xs truncate" title="{{ $req->rejected_reason }}">
                                                        {{ $req->rejected_reason }}
                                                    </p>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge badge-warning badge-sm gap-1 font-semibold animate-pulse">
                                                ⏳ {{ __('Pending') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($req->document)
                                                <a href="{{ route('documents.preview', ['document' => $req->document, 'from' => 'signature_requests']) }}" title="{{ __('Preview Dokumen') }}" class="btn btn-ghost btn-xs gap-1 font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>{{ __('Preview') }}</span>
                                                </a>
                                            @endif

                                            @if($req->isPending())
                                                <button type="button" onclick="document.getElementById('approve-modal-{{ $req->id }}').showModal()" class="btn btn-success btn-xs gap-1 font-medium" title="{{ __('Setujui penggunaan tanda tangan Anda pada dokumen ini') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                    {{ __('Approve TTD') }}
                                                </button>

                                                {{-- Custom Approve Signature Modal --}}
                                                <dialog id="approve-modal-{{ $req->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                        {{-- Header --}}
                                                        <div class="p-6 pb-4">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div class="flex items-center gap-3.5">
                                                                    <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                                        </svg>
                                                                    </div>
                                                                    <div>
                                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Permintaan Tanda Tangan') }}</h3>
                                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Tanda tangan Anda akan dibubuhkan secara otomatis ke dalam dokumen ini.') }}</p>
                                                                    </div>
                                                                </div>
                                                                <button type="button" onclick="document.getElementById('approve-modal-{{ $req->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                    ✕
                                                                </button>
                                                            </div>

                                                            {{-- Target Document Info Box --}}
                                                            <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                                <div class="p-2 rounded-lg bg-base-100 text-base-content/70 shrink-0 shadow-xs">
                                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                </div>
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="flex items-center gap-2 flex-wrap">
                                                                        <span class="font-semibold text-sm text-base-content break-words">{{ $req->document?->title ?? __('Dokumen') }}</span>
                                                                    </div>
                                                                    <p class="text-xs text-base-content/60 mt-1">
                                                                        {{ __('Diminta oleh') }}: <span class="font-medium text-base-content/80">{{ $req->requester->name }}</span>
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            {{-- Note Box --}}
                                                            <div class="mt-3 p-3 rounded-xl bg-success/5 border border-success/15 flex items-start gap-2.5 text-xs text-base-content/70">
                                                                <svg class="w-4 h-4 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                <span>{{ __('Pastikan Anda telah memeriksa isi dokumen sebelum memberikan persetujuan pembubuhan tanda tangan.') }}</span>
                                                            </div>
                                                        </div>

                                                        {{-- Form / Modal Action Footer --}}
                                                        <form method="POST" action="{{ route('signatures.requests.approve', $req) }}" onsubmit="document.getElementById('approve-modal-{{ $req->id }}').close(); document.getElementById('loading-modal').showModal();">
                                                            @csrf
                                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                                <button type="button" onclick="document.getElementById('approve-modal-{{ $req->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                    {{ __('Batal') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                    {{ __('Ya, Setujui & Tanda Tangani') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <form method="dialog" class="modal-backdrop">
                                                        <button>{{ __('Batal') }}</button>
                                                    </form>
                                                </dialog>

                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').showModal()" class="btn btn-error btn-outline btn-xs gap-1 font-medium" title="{{ __('Tolak izin penggunaan tanda tangan Anda') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    {{ __('Reject TTD') }}
                                                </button>

                                                {{-- Reject Reason Modal --}}
                                                <dialog id="reject-modal-{{ $req->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                        {{-- Header --}}
                                                        <div class="p-6 pb-4">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div class="flex items-center gap-3.5">
                                                                    <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                        </svg>
                                                                    </div>
                                                                    <div>
                                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Permintaan Tanda Tangan') }}</h3>
                                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Izin tanda tangan tidak akan diberikan.') }}</p>
                                                                    </div>
                                                                </div>
                                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                    ✕
                                                                </button>
                                                            </div>

                                                            {{-- Target Document Info Box --}}
                                                            <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                                <div class="p-2 rounded-lg bg-base-100 text-base-content/70 shrink-0 shadow-xs">
                                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                </div>
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="flex items-center gap-2 flex-wrap">
                                                                        <span class="font-semibold text-sm text-base-content break-words">{{ $req->document?->title ?? __('Dokumen') }}</span>
                                                                    </div>
                                                                    <p class="text-xs text-base-content/60 mt-1">
                                                                        {{ __('Diminta oleh') }}: <span class="font-medium text-base-content/80">{{ $req->requester->name }}</span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- Form --}}
                                                        <form method="POST" action="{{ route('signatures.requests.reject', $req) }}">
                                                            @csrf
                                                            <div class="px-6 pb-5 space-y-2">
                                                                <div class="flex items-center justify-between">
                                                                    <label for="reject-sig-reason-{{ $req->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                                        {{ __('Alasan Penolakan') }}
                                                                    </label>
                                                                    <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                                </div>
                                                                <div class="relative">
                                                                    <textarea 
                                                                        id="reject-sig-reason-{{ $req->id }}"
                                                                        name="reason" 
                                                                        maxlength="500"
                                                                        class="textarea textarea-bordered w-full text-sm rounded-xl bg-base-200/30 border-base-300 focus:border-error focus:ring-2 focus:ring-error/20 focus:outline-hidden transition-all placeholder:text-base-content/40 leading-relaxed min-h-[95px] p-3" 
                                                                        placeholder="{{ __('Tuliskan alasan penolakan izin tanda tangan...') }}"></textarea>
                                                                </div>
                                                                <p class="text-[11px] text-base-content/50 flex items-center gap-1">
                                                                    <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    {{ __('Catatan ini akan dikirimkan ke pemohon izin tanda tangan.') }}
                                                                </p>
                                                            </div>

                                                            {{-- Modal Action Footer --}}
                                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                    {{ __('Batal') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                    {{ __('Tolak Permintaan') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <form method="dialog" class="modal-backdrop">
                                                        <button>{{ __('Batal') }}</button>
                                                    </form>
                                                </dialog>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($incomingRequests->hasPages())
                <div class="px-5 py-3 border-t border-base-200 bg-base-200/20">
                    {{ $incomingRequests->links() }}
                </div>
            @endif
        </div>

        {{-- Outgoing Requests Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-base-200/40 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Riwayat Pengajuan Tanda Tangan Saya') }}
                            <span class="badge badge-secondary badge-sm font-semibold">{{ $outgoingRequests->total() }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Daftar tanda tangan pengguna lain yang pernah Anda ajukan untuk dokumen Anda.') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($outgoingRequests->isEmpty())
                <div class="py-12 text-center text-base-content/50 space-y-2">
                    <p class="text-sm font-medium">{{ __('Belum pernah mengajukan tanda tangan pengguna lain.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[720px]">
                        <thead>
                            <tr class="bg-base-200/40 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-5">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Pemilik TTD') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu') }}</th>
                                <th class="py-3 px-4">{{ __('Status') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($outgoingRequests as $req)
                                <tr class="hover:bg-base-200/40 transition-colors">
                                    <td class="px-5 py-4 max-w-xs">
                                        <div class="space-y-0.5">
                                            @if($req->document)
                                                <div class="font-semibold text-sm text-base-content break-words line-clamp-2">
                                                    {{ $req->document->title }}
                                                </div>
                                                @if($req->document->document_number)
                                                    <span class="text-xs font-mono text-base-content/50 block">{{ $req->document->document_number }}</span>
                                                @endif
                                            @else
                                                <span class="font-semibold text-sm text-base-content">{{ __('Dokumen Umum') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $req->targetUser->name }}</div>
                                        <div class="text-xs text-base-content/50">{{ $req->targetUser->email }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        {{ $req->requested_at ? $req->requested_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($req->isUsed())
                                            <span class="badge badge-neutral badge-sm gap-1 font-medium">✓ {{ __('Telah Digunakan') }}</span>
                                        @elseif($req->isApproved())
                                            <span class="badge badge-success badge-sm gap-1 font-medium">{{ __('Disetujui (Siap Dibubuhkan)') }}</span>
                                        @elseif($req->isRejected())
                                            <div class="space-y-1">
                                                <span class="badge badge-error badge-sm gap-1 font-medium">{{ __('Ditolak') }}</span>
                                                @if($req->rejected_reason)
                                                    <p class="text-[11px] text-error max-w-xs truncate" title="{{ $req->rejected_reason }}">
                                                        {{ $req->rejected_reason }}
                                                    </p>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge badge-warning badge-sm gap-1 font-medium">⏳ {{ __('Menunggu') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        @if($req->document)
                                            <a href="{{ route('documents.preview', ['document' => $req->document, 'from' => 'signature_requests']) }}" title="{{ __('Preview Dokumen') }}" class="btn btn-ghost btn-xs gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>{{ __('Preview') }}</span>
                                            </a>
                                        @else
                                            <span class="text-xs text-base-content/40">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($outgoingRequests->hasPages())
                <div class="px-5 py-3 border-t border-base-200 bg-base-200/20">
                    {{ $outgoingRequests->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Loading Modal --}}
    <dialog id="loading-modal" class="modal">
        <div class="modal-box flex flex-col items-center justify-center py-10">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <h3 class="font-bold text-lg mt-4">{{ __('Memproses Dokumen...') }}</h3>
            <p class="text-sm text-base-content/70 mt-2 text-center">{{ __('Harap tunggu sebentar, sistem sedang membubuhkan tanda tangan Anda ke dalam dokumen secara otomatis.') }}</p>
        </div>
    </dialog>
</x-app-layout>

