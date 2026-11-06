<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Verifikasi Rekaman Arsip') }} - {{ $entry->archive_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-base-200 min-h-screen flex items-center justify-center p-4 antialiased">
    <div class="card bg-base-100 max-w-lg w-full shadow-2xl border border-base-300 p-6 sm:p-8 space-y-6">
        {{-- Header Status --}}
        <div class="text-center space-y-2">
            @if($entry->status === 'registered' || $entry->status === 'permanent' || $entry->status === 'under_review')
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-success/20 text-success mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h1 class="text-xl font-black text-success">{{ __('REKAMAN ARSIP RESMI TERVERIFIKASI') }}</h1>
                <p class="text-xs text-base-content/60">{{ __('Dokumen ini terdaftar secara sah dalam Buku Induk Arsip DokuFlow.') }}</p>
            @elseif($entry->status === 'disposed')
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral/20 text-neutral mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h1 class="text-xl font-black text-neutral">{{ __('ARSIP TELAH DIMUSNAHKAN') }}</h1>
                <p class="text-xs text-base-content/60">{{ __('Rekaman arsip telah disusutkan secara sah berdasarkan Berita Acara.') }}</p>
            @elseif($entry->status === 'voided')
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-error/20 text-error mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-xl font-black text-error">{{ __('NOMOR ARSIP DIBATALKAN (VOID)') }}</h1>
                <p class="text-xs text-base-content/60">{{ __('Nomor arsip ini telah dibatalkan secara administratif dan tidak sah.') }}</p>
            @endif
        </div>

        {{-- Details List --}}
        <div class="divide-y divide-base-200 text-xs">
            <div class="py-2.5 flex justify-between gap-4">
                <span class="text-base-content/60">{{ __('Nomor Arsip Resmi:') }}</span>
                <span class="font-mono font-bold text-primary text-right">{{ $entry->archive_number }}</span>
            </div>

            <div class="py-2.5 flex justify-between gap-4">
                <span class="text-base-content/60">{{ __('Judul Dokumen:') }}</span>
                <span class="font-semibold text-base-content text-right">{{ $entry->title }}</span>
            </div>

            <div class="py-2.5 flex justify-between gap-4">
                <span class="text-base-content/60">{{ __('Kode Klasifikasi:') }}</span>
                <span class="font-medium text-base-content text-right">{{ $entry->classification_code_snapshot }} - {{ $entry->classification_name_snapshot }}</span>
            </div>

            <div class="py-2.5 flex justify-between gap-4">
                <span class="text-base-content/60">{{ __('Lembaga / Cabang:') }}</span>
                <span class="font-medium text-base-content text-right">{{ $entry->company_name_snapshot }} / {{ $entry->branch_name_snapshot }}</span>
            </div>

            <div class="py-2.5 flex justify-between gap-4">
                <span class="text-base-content/60">{{ __('Tanggal Registrasi:') }}</span>
                <span class="font-medium text-base-content text-right">{{ $entry->registered_at?->format('d M Y H:i:s T') }}</span>
            </div>

            <div class="py-2.5 flex justify-between gap-4">
                <span class="text-base-content/60">{{ __('Tingkat Akses:') }}</span>
                <span class="badge badge-outline badge-xs uppercase font-bold text-right">{{ $entry->sensitivity_level }}</span>
            </div>

            <div class="py-2.5">
                <span class="text-base-content/60 block mb-1">{{ __('Sidik Jari SHA-256 Imutabel:') }}</span>
                <span class="font-mono text-[10px] break-all select-all bg-base-200/80 p-2 rounded block">
                    {{ $entry->snapshot?->content_hash_sha256 ?? '-' }}
                </span>
            </div>
        </div>

        <div class="text-center pt-2">
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost text-xs">
                {{ __('Masuk ke DokuFlow') }}
            </a>
        </div>
    </div>
</body>
</html>

