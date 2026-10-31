<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $document->title }} — {{ config('app.name', 'DokuFlow') }}</title>

    <link rel="icon" type="image/webp" href="{{ asset('logo.webp') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <script>
        (function(){
            var t=sessionStorage.getItem('theme:v2'),m=window.matchMedia('(prefers-color-scheme: dark)'),d=(t==='dark')||(t!=='light'&&m.matches);
            document.documentElement.setAttribute('data-theme',d?'dark':'light');
            document.documentElement.classList.toggle('dark',d);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200 text-base-content flex flex-col">
    @php
        $display = $version ?? $document->displayVersion();
        $isPdf = $display && (str_ends_with(strtolower($display->file_path ?? ''), '.pdf') || str_contains(strtolower($display->file_mime ?? ''), 'pdf'));
    @endphp

    {{-- Top Navigation Bar --}}
    <header class="bg-base-100 border-b border-base-300 sticky top-0 z-30 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
                    <img src="{{ asset('logo.webp') }}" alt="Logo" class="w-8 h-8 object-contain" />
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="font-bold text-sm sm:text-base text-base-content truncate max-w-xs sm:max-w-lg" title="{{ $document->title }}">
                            {{ $document->title }}
                        </h1>
                        @if($document->document_number)
                            <span class="badge badge-ghost badge-xs font-mono shrink-0">{{ $document->document_number }}</span>
                        @endif
                        <span class="badge badge-primary badge-outline badge-xs font-semibold uppercase">
                            {{ $document->link_role === 'editor' ? __('Editor') : __('Viewer') }}
                        </span>
                    </div>
                    <div class="text-xs text-base-content/60 flex items-center gap-2 flex-wrap mt-0.5">
                        <span>{{ $document->unitKerja?->nama_unit_kerja ?? $document->unitKerja?->kode_unit_kerja ?? '—' }}</span>
                        @if($document->owner)
                            <span>•</span>
                            <span>{{ __('Pemilik:') }} <strong class="font-medium text-base-content/80">{{ $document->owner->name }}</strong></span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="button" onclick="document.getElementById('export-pdf-modal').showModal()" class="btn btn-primary btn-sm gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>{{ __('Unduh') }}</span>
                </button>
                <a href="{{ route('login') }}" class="btn btn-outline btn-sm">
                    {{ __('Masuk') }}
                </a>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-3 sm:px-6 py-4 sm:py-6">
        <div class="card bg-base-100 border border-base-300 shadow-xs">
            <div class="card-body p-3 sm:p-5">
                @if($display && $display->file_path)
                    @include('documents._file-preview', [
                        'document' => $document,
                        'version' => $display,
                        'onlyOfficeConfig' => $onlyOfficeConfig,
                        'customFileUrl' => route('documents.shared.file', $token),
                    ])
                @elseif($display)
                    @php
                        $paperSizes = [
                            'F4' => ['w' => 794, 'h' => 1247],
                            'A4' => ['w' => 794, 'h' => 1123],
                            'A5' => ['w' => 559, 'h' => 794],
                            'A3' => ['w' => 1123, 'h' => 1587],
                            'Letter' => ['w' => 816, 'h' => 1056],
                            'Legal' => ['w' => 816, 'h' => 1344],
                        ];
                        $ps = $paperSizes[$document->paper_size ?? 'A4'] ?? $paperSizes['A4'];
                        $m = $document->paper_margin ?? [];
                        $mt = $m['top'] ?? 96;
                        $mr = $m['right'] ?? 96;
                        $mb = $m['bottom'] ?? 96;
                        $ml = $m['left'] ?? 96;
                        $resolvedContent = app(\App\Services\SignatureResolverService::class)->resolve($display->content, $document, null);
                        $resolvedContent = app(\App\Services\QrCodeService::class)->injectPlaceholder($resolvedContent, $document);
                    @endphp
                    <div class="bg-base-300 p-4 sm:p-6 rounded-lg overflow-auto max-h-[82vh]">
                        <div class="doku-paper bg-white text-black shadow-md mx-auto"
                             style="width: {{ $ps['w'] }}px; min-height: {{ $ps['h'] }}px; padding: {{ $mt }}px {{ $mr }}px {{ $mb }}px {{ $ml }}px; box-sizing: border-box;">
                            <div class="doku-content max-w-none">{!! $resolvedContent !!}</div>
                        </div>
                    </div>
                @else
                    <p class="text-base-content/50 italic text-center py-8">{{ __('Belum ada konten untuk ditampilkan.') }}</p>
                @endif
            </div>
        </div>
    </main>

    {{-- Export / Download Modal --}}
    <dialog id="export-pdf-modal" class="modal" x-data="{
        withKop: '{{ $document->hasKop() ? '1' : '0' }}',
        hasKopDoc: {{ $document->hasKop() ? 'true' : 'false' }},
        paperSize: '{{ $document->paper_size ?? 'A4' }}',
        customWidth: '',
        customHeight: '',
        customUnit: 'cm',
        downloadDocx() {
            const url = '{{ route('documents.shared.download', $token) }}?with_kop=' + this.withKop;
            window.location.href = url;
            document.getElementById('export-pdf-modal').close();
        }
    }">
        <div class="modal-box max-w-lg max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-base-200 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-base shadow-xs shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content leading-tight">{{ __('Unduh Dokumen') }}</h3>
                        <p class="text-xs text-base-content/60">{{ __('Pilih format file dan ukuran kertas untuk mengunduh dokumen') }}</p>
                    </div>
                </div>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('export-pdf-modal').close()">✕</button>
            </div>

            <form method="POST" action="{{ route('documents.shared.export-pdf', $token) }}" @submit="setTimeout(() => document.getElementById('export-pdf-modal').close(), 300)">
                @csrf
                <input type="hidden" name="with_kop" :value="withKop">

                <div class="form-control w-full mb-4 bg-base-200/40 p-3 rounded-2xl border border-base-200">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-bold text-xs text-base-content/80">{{ __('Ukuran Kertas Dokumen (PDF)') }}</span>
                        <span class="label-text-alt text-[11px] text-primary font-semibold">{{ __('Default: :size', ['size' => $document->paper_size ?? 'A4']) }}</span>
                    </label>
                    <select name="paper_size" x-model="paperSize" class="select select-bordered select-sm w-full bg-base-100">
                        <option value="A4">A4 (21 x 29.7 cm)</option>
                        <option value="F4">F4 (21 x 33 cm) — {{ __('Folio / Standar') }}</option>
                        <option value="Letter">Letter (8.5" x 11" / 21.59 x 27.94 cm)</option>
                        <option value="Legal">Legal (8.5" x 14" / 21.59 x 35.56 cm)</option>
                        <option value="A5">A5 (14.8 x 21 cm)</option>
                        <option value="A3">A3 (29.7 x 42 cm)</option>
                    </select>
                </div>

                <div class="flex items-center justify-between gap-2 pt-3 border-t border-base-200">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('export-pdf-modal').close()">{{ __('Batal') }}</button>
                    <div class="flex items-center gap-2">
                        @if(!$isPdf && $display && $display->file_path)
                            <button type="button" @click="downloadDocx()" class="btn btn-outline btn-primary btn-sm gap-1.5">
                                {{ __('Unduh DOCX') }}
                            </button>
                        @endif
                        <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                            {{ __('Unduh PDF') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</body>
</html>

