<x-guest-layout title="Verifikasi Dokumen" heading="Status Dokumen" description="Hasil pemindaian QR Code Dokumen">
    <div class="text-center">
        @if($document->currentVersion)
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-success/20 text-success mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-success mb-1">Dokumen Valid & Terverifikasi</h2>
            <p class="text-sm text-base-content/70 mb-6">Dokumen ini terdaftar resmi dalam sistem {{ config('app.name', 'DokuFlow') }}.</p>
        @else
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-warning/20 text-warning mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-warning mb-1">Dokumen Belum Disetujui</h2>
            <p class="text-sm text-base-content/70 mb-6">Dokumen ini ada dalam sistem, namun belum memiliki versi yang aktif atau disetujui.</p>
        @endif

        <div class="bg-base-200/50 rounded-xl p-4 text-left border border-base-300">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-base-content/50 uppercase">Nomor Dokumen</dt>
                    <dd class="font-medium mt-0.5">{{ $document->document_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-base-content/50 uppercase">Judul Dokumen</dt>
                    <dd class="font-medium mt-0.5">{{ $document->title }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-base-content/50 uppercase">Divisi</dt>
                    <dd class="font-medium mt-0.5">{{ $document->division?->name ?? $document->division?->code ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-base-content/50 uppercase">Pemilik</dt>
                    <dd class="font-medium mt-0.5">{{ $document->owner->name ?? '-' }}</dd>
                </div>
                @if($document->currentVersion)
                <div>
                    <dt class="text-xs font-semibold text-base-content/50 uppercase">Versi Aktif</dt>
                    <dd class="font-medium mt-0.5">Versi {{ $document->currentVersion->version_number }} ({{ $document->currentVersion->created_at->format('d M Y') }})</dd>
                </div>
                @endif
            </dl>
        </div>
        
        <div class="mt-6">
            <a href="/" class="btn btn-outline btn-block">Kembali ke Beranda</a>
        </div>
    </div>
</x-guest-layout>
