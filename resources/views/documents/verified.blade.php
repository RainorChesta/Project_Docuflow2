<x-guest-layout title="Verifikasi Dokumen" heading="Status Dokumen" description="Hasil pemindaian QR Code Dokumen" size="sm">
    <div class="text-center">
        @if($document->is_expired)
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-error/20 text-error mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-base font-semibold text-error mb-0.5">Dokumen Kedaluwarsa</h2>
            <p class="text-xs text-base-content/70 mb-5">Masa berlaku dokumen ini telah habis pada {{ $document->expiration_date?->format('d M Y') ?? 'waktu yang ditentukan' }}.</p>
        @elseif($document->currentVersion)
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-success/20 text-success mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-base font-semibold text-success mb-0.5">Dokumen Valid & Terverifikasi</h2>
            <p class="text-xs text-base-content/70 mb-5">Dokumen terdaftar resmi dalam sistem {{ config('app.name', 'DokuFlow') }}.</p>
        @else
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-warning/20 text-warning mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 class="text-base font-semibold text-warning mb-0.5">Dokumen Belum Disetujui</h2>
            <p class="text-xs text-base-content/70 mb-5">Dokumen ada dalam sistem, namun belum memiliki versi yang disetujui.</p>
        @endif

        <div class="bg-base-200/50 rounded-xl p-3 text-left border border-base-300">
            <dl class="space-y-2 text-[13px]">
                <div>
                    <dt class="text-[10px] font-semibold text-base-content/50 uppercase">Nomor Dokumen</dt>
                    <dd class="font-medium">{{ $document->document_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold text-base-content/50 uppercase">Judul Dokumen</dt>
                    <dd class="font-medium">{{ $document->title }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold text-base-content/50 uppercase">Divisi</dt>
                    <dd class="font-medium">{{ $document->division?->name ?? $document->division?->code ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold text-base-content/50 uppercase">Pemilik</dt>
                    <dd class="font-medium">{{ $document->owner->name ?? '-' }}</dd>
                </div>
                @if($document->currentVersion)
                <div>
                    <dt class="text-[10px] font-semibold text-base-content/50 uppercase">Versi Aktif</dt>
                    <dd class="font-medium">Versi {{ $document->currentVersion->version_number }} ({{ $document->currentVersion->created_at->format('d M Y') }})</dd>
                </div>
                @endif
            </dl>
        </div>
        
        <div class="mt-5 flex flex-col sm:flex-row gap-2">
            <a href="{{ route('documents.preview', $document) }}" class="btn btn-primary btn-sm flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Lihat
            </a>
            <a href="/" class="btn btn-outline btn-sm flex-1">Beranda</a>
        </div>
    </div>
</x-guest-layout>
