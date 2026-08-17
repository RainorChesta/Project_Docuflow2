@once
    @push('styles')
        <style>
    /* WAJIB baris PERTAMA — @import harus paling atas di file CSS, sama
       seperti iframeStyle editor di resources/js/jodit.js. Tanpa ini font
       Google tidak tampil di preview halaman → beda dgn pratinjau Jodit. */
    @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,400&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Code+Pro:ital,wght@0,400;0,700;1,400&display=swap');

    /* NUCLEAR RESET: batalkan SEMUA efek Tailwind Preflight di dalam area
       dokumen. Tailwind mereset *, ::before, ::after { box-sizing: border-box;
       border-width: 0; ... }, img { display: block }, h1-h6 { font-size:
       inherit }, ol/ul { list-style: none; padding: 0 }, p { margin: 0 },
       dll. Di editor Jodit (iframe), Tailwind TIDAK ADA — elemen pakai
       browser defaults. Mengejar satu-satu aturan Tailwind tidak realistis
       dan selalu ada yang terlewat (penyebab list lompat ke halaman 2 di
       preview tapi muat di editor). `all: revert` mengembalikan SEMUA
       properti ke User-Agent stylesheet (= browser defaults) sekaligus.
       Aturan eksplisit kita di bawah punya specificity lebih tinggi
       (0,1,1 vs 0,1,0) sehingga tetap menang. Inline style dari Jodit
       (specificity 1,0,0) juga tetap terjaga. */
    .doku-paper *,
    .doku-paper *::before,
    .doku-paper *::after {
        all: revert;
    }

    /* ---------- Base ---------- */
    .doku-content, .doku-paper, .jodit-wysiwyg { font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5; color: #000; word-wrap: break-word; text-align: left; }

    /* ---------- Paragraf ---------- */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) p { margin-top: 0; margin-bottom: 1em; }

    /* ---------- List (ul, ol, li) ---------- */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) ul, :is(.doku-content, .doku-paper, .jodit-wysiwyg) ol { margin-top: 0; margin-bottom: 1em; padding-left: 40px !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) ul { list-style-type: disc !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) ul ul { list-style-type: circle !important; margin-bottom: 0; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) ul ul ul { list-style-type: square !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) ol { list-style-type: decimal !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) ol ol { list-style-type: lower-alpha !important; margin-bottom: 0; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) ol ol ol { list-style-type: lower-roman !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) li { margin-bottom: 4px; display: list-item !important; text-align: match-parent; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) li > ul, :is(.doku-content, .doku-paper, .jodit-wysiwyg) li > ol { margin-bottom: 0; }

    /* ---------- Headings ---------- */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) h1, :is(.doku-content, .doku-paper, .jodit-wysiwyg) h2, :is(.doku-content, .doku-paper, .jodit-wysiwyg) h3, :is(.doku-content, .doku-paper, .jodit-wysiwyg) h4, :is(.doku-content, .doku-paper, .jodit-wysiwyg) h5, :is(.doku-content, .doku-paper, .jodit-wysiwyg) h6 { margin-top: 1.2em; margin-bottom: 0.5em; font-weight: bold !important; line-height: 1.2; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) h1 { font-size: 2em !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) h2 { font-size: 1.5em !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) h3 { font-size: 1.17em !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) h4 { font-size: 1em !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) h5 { font-size: 0.83em !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) h6 { font-size: 0.67em !important; }

    /* ---------- Table ---------- */
    /* FIX: samakan prioritas dengan aturan lain (li, heading, dst) yang
       sudah !important. Tanpa !important di sini, style bawaan Jodit yang
       diinject ke iframe editor (untuk cell-selection/resize handle) bisa
       menimpa border tabel ini sehingga border "hilang" hanya saat mode
       edit. Nilai di sini WAJIB SAMA PERSIS dengan blok table di
       resources/js/jodit.js (buildIframeStyle) supaya editor & show/preview
       selalu konsisten. */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) table { border-collapse: collapse !important; width: 100%; margin-bottom: 1em; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) th, :is(.doku-content, .doku-paper, .jodit-wysiwyg) td { border: 1px solid #ccc !important; padding: 8px; text-align: left; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) th { font-weight: bold !important; background-color: #f9fafb !important; }

    /* ---------- Blockquote / Pre ---------- */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) blockquote { margin: 1em 40px; border-left: 4px solid #ccc; padding-left: 1em; color: #666; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) pre { background: #f4f4f4; padding: 1em; overflow-x: auto; font-family: monospace; }

    /* ---------- Inline formatting ---------- */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) b, :is(.doku-content, .doku-paper, .jodit-wysiwyg) strong { font-weight: bold !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) i, :is(.doku-content, .doku-paper, .jodit-wysiwyg) em { font-style: italic !important; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) u { text-decoration: underline !important; }

    /* ---------- Elemen yang di-reset Tailwind Preflight tapi BELUM ditangani ---------- */
    /* Tailwind: img { display: block } — Browser default: inline. Ini bikin
       gambar di preview jadi block (ada gap bawah) padahal di editor inline. */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) img { display: inline; max-width: 100%; height: auto; }
    /* Tailwind: a { color: inherit; text-decoration: inherit } */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) a { color: #1a0dab; text-decoration: underline; }
    /* Tailwind: hr { height: 0; border-top-width: 1px; color: inherit } */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) hr { margin: 1em 0; border: none; border-top: 1px solid #ccc; }
    /* Tailwind: sub/sup { font-size: 75%; line-height: 0 } — benar, tapi kita
       pastikan vertical-align supaya posisinya sama dengan default browser. */
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) sub { vertical-align: sub; font-size: smaller; }
    :is(.doku-content, .doku-paper, .jodit-wysiwyg) sup { vertical-align: super; font-size: smaller; }

    /* Fix scroll hilang saat live-sync render: preview.blade.php merender
       ulang konten draft dari localStorage dengan menimpa innerHTML
       #live-preview-content — style <style> di dalam partial ini ikut
       terhapus, padahal CSS-nya (max-height 75vh + overflow auto) yang
       bikin area kertas bisa di-scroll. Dengan memindahkan <style> ke
       HEAD dokumen (dipush sekali per render), style tetap hidup walau
       konten di-render ulang. */
            @media print {
    .doku-paper-scope {
        max-height: none;
        overflow: visible;
        padding: 0;
        background: #fff;
    }
    .doku-paper-scope .doku-paper {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        width: 100%;
        min-height: auto;
        margin: 0;
        padding: 0 !important;
    }
    .doku-paper-scope .doku-paper [data-page-spacer] {
        display: none !important;
    }
    .doku-paper-scope .doku-paper-toolbar {
        display: none !important;
    }

    @page {
        margin: 0;
    }
}

    /* Scope = container scroll khusus dokumen (ala editor Jodit). Kalau
       dokumen panjang (banyak halaman), halaman utama TIDAK ikut memanjang —
       area kertas ini yang scroll sendiri. max-height dibatasi 75vh biar
       metadata/aksi di atasnya tetap terlihat. */
    .doku-paper-scope {
        max-height: 75vh;
        overflow-y: auto;
        overflow-x: auto;
        background: #e5e7eb;
        padding: 20px;
        box-sizing: border-box;
        border-radius: 8px;
    }

    .doku-paper-scope .doku-paper {
    width: 100%;
    /* max-width dihapus — repaginatePreview (resources/js/jodit.js) set
       width inline sesuai ukuran kertas aktif (A4=794, A5=559, A3=1123, dst).
       Kalau max-width:794px dibiarkan, A3 (1123px) ke-clip jadi 794px dan
       perbedaan ukuran kertas tidak terlihat. */
    margin: 0 auto;
    /* padding default di-set inline oleh repaginatePreview (resources/js/
       jodit.js) sesuai margin aktif; nilai di sini hanya fallback saat JS
       belum jalan (mis. render server-side pertama). */
    padding: 48px 56px;
    background-color: #fff;
    min-height: 1123px;
    /* Border & shadow di layar biar batas kertas terlihat jelas — terutama
       saat ukuran kertas diubah (A4/A5/A3 dst), lebar kertas berubah dan
       border ini mempertegas perbedaannya. Menggunakan outline agar tidak
       memakan ruang konten 2px (kiri-kanan) yang menyebabkan selisih teks melompat 1 baris. */
    outline: 1px solid #d1d5db;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.08);
    box-sizing: border-box;
    /* font-family, font-size, line-height, color, overflow-wrap, word-break
       sekarang berasal dari .doku-paper dalam document-shared.css
       (line-height: 1.5 — sama persis dengan yang diterapkan editor via
       .doku-content). Tidak perlu di-set ulang di sini. */
}

    /* Responsif: di layar sempit, kertas mengikuti lebar device dan
       margin halaman dikecilkan biar konten tidak kepotong. !important
       dipakai karena repaginatePreview (resources/js/jodit.js) menulis
       width/min-height sebagai inline style — media query ini harus
       menang biar preview pas dengan lebar layar kecil. Garis pembatas
       antar halaman TETAP ditampilkan (hanya dirampingkan) supaya
       user tetap melihat di mana halaman berikutnya dimulai — sama
       seperti di layar lebar.
       Ukuran teks ikut dikecilkan (px fix, bukan em) supaya konten
       tetap terbaca di device kecil. Pakai px agar tidak kompaun pada
       elemen bersarang (em berlipat tiap level) dan mengalahkan
       font-size inline dari Jodit. Pagination tetap akurat karena
       dihitung dari layout yang sudah berskala ini. */
    @media (max-width: 640px) {
        .doku-paper-scope {
            padding: 8px;
            max-height: 90vh;
        }
        .doku-paper-scope .doku-paper {
            width: 100% !important;
            min-height: auto !important;
            font-size: 14px !important;
        }
        .doku-paper-scope .doku-paper :is(p, li, td, th, div, span, blockquote, pre, figcaption, dd, dt) {
            font-size: 14px !important;
        }
        .doku-paper-scope .doku-paper h1 { font-size: 28px !important; }
        .doku-paper-scope .doku-paper h2 { font-size: 21px !important; }
        .doku-paper-scope .doku-paper h3 { font-size: 17px !important; }
        .doku-paper-scope .doku-paper h4 { font-size: 14px !important; }
        .doku-paper-scope .doku-paper h5 { font-size: 12px !important; }
        .doku-paper-scope .doku-paper h6 { font-size: 11px !important; }
        .doku-paper-scope .doku-paper [data-page-spacer] {
            display: block !important;
        }
        .doku-paper-scope .doku-paper [data-page-spacer] > div {
            background: #fff !important;
        }
        .doku-paper-scope .doku-paper [data-page-spacer] > div:nth-child(2) {
            height: 6px !important;
            background: #cbd5e1 !important;
            border-top: 1px solid #94a3b8 !important;
            border-bottom: 1px solid #94a3b8 !important;
        }
    }
    @media (max-width: 400px) {
        .doku-paper-scope .doku-paper {
            font-size: 13px !important;
        }
        .doku-paper-scope .doku-paper :is(p, li, td, th, div, span, blockquote, pre, figcaption, dd, dt) {
            font-size: 13px !important;
        }
        .doku-paper-scope .doku-paper h1 { font-size: 24px !important; }
        .doku-paper-scope .doku-paper h2 { font-size: 18px !important; }
        .doku-paper-scope .doku-paper h3 { font-size: 15px !important; }
        .doku-paper-scope .doku-paper h4 { font-size: 13px !important; }
        .doku-paper-scope .doku-paper h5 { font-size: 11px !important; }
        .doku-paper-scope .doku-paper h6 { font-size: 10px !important; }
    }
    .doku-paper-scope .doku-paper::after {
        content: "";
        display: table;
        clear: both;
    }

    /* FIX: Netralkan preflight Tailwind di luar paper yang bisa "bocor"
       masuk ke dalam .doku-paper dan menimpa aturan document-shared.css.
       Tailwind preflight me-reset margin/padding/font-size semua elemen ke
       nol — di sisi luar halaman itu normal, tapi di dalam .doku-paper kita
       perlu UA stylesheet + document-shared.css, bukan reset Tailwind.
       Dengan mengembalikan elemen konten ke nilai yang sudah kita definisikan
       secara eksplisit di document-shared.css (via .doku-paper scope),
       preflight tidak punya kesempatan menimpa mereka.
       CATATAN: Blok "all: revert" yang sebelumnya ada di sini DIHAPUS —
       revert mengembalikan ke UA defaults yang berbeda-beda antar browser
       DAN dapat membatalkan aturan dari document-shared.css. Sebaliknya,
       kita mengandalkan aturan eksplisit di document-shared.css (.doku-paper
       scope) yang sudah SAMA PERSIS dengan aturan editor (.doku-content scope),
       sehingga preview dan editor selalu konsisten. */

    /* Toolbar kecil di atas kertas: dropdown ukuran kertas (hanya jika ada
       editor terkait) + kontrol zoom in/out/reset — selalu tampil di semua
       preview dokumen. */
    .doku-paper-scope .doku-paper-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        font-size: 13px;
        background: #e5e7eb;
        padding: 4px 0;
    }
    .doku-paper-scope .doku-paper-toolbar select {
        padding: 4px 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background: #fff;
        color: #1a1a1a;
    }
    .doku-paper-scope .doku-paper-toolbar .doku-paper-zoom-btn {
        padding: 4px 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background: #fff;
        color: #1a1a1a;
        cursor: pointer;
        font-size: 13px;
        line-height: 1;
    }
    .doku-paper-scope .doku-paper-toolbar .doku-paper-zoom-btn:hover {
        background: #f3f4f6;
    }
    .doku-paper-scope .doku-paper-toolbar .doku-paper-zoom-label {
        min-width: 44px;
        text-align: center;
        color: #1a1a1a;
        font-weight: 600;
    }
        </style>
    @endpush
@endonce

<div class="doku-paper-scope" @isset($liveStorage) data-live-storage="{{ $liveStorage }}" @endisset
     data-paper-size="{{ $paperSize ?? 'A4' }}"
     data-paper-margin="{{ json_encode($paperMargin ?? null) }}">
    <div class="doku-paper-toolbar">
        @isset($liveStorage)
            <label class="text-base-content/70">{{ __('Ukuran Kertas') }}:</label>
            <select data-paper-size-select>
                @foreach(['A4', 'A5', 'A3', 'Letter', 'Legal'] as $paperKey)
                    <option value="{{ $paperKey }}">{{ $paperKey }}</option>
                @endforeach
            </select>
        @endisset
        <button type="button" class="doku-paper-zoom-btn" data-paper-zoom-out title="Perkecil" aria-label="Zoom out">−</button>
        <span class="doku-paper-zoom-label" data-paper-zoom-label>100%</span>
        <button type="button" class="doku-paper-zoom-btn" data-paper-zoom-in title="Perbesar" aria-label="Zoom in">+</button>
        <button type="button" class="doku-paper-zoom-btn" data-paper-zoom-reset>Reset</button>
    </div>
    <div class="doku-paper">
        @php
            $finalContent = app(\App\Services\SignatureResolverService::class)->resolve($content, $document ?? null, auth()->user());
            if (isset($document)) {
                $finalContent = app(\App\Services\QrCodeService::class)->injectPlaceholder($finalContent, $document);
            }
        @endphp
        {!! $finalContent !!}
    </div>
</div>

@once
<script>
    // Zoom in/out kertas preview — pakai event delegation supaya tetap hidup
    // walau preview.blade.php me-render ulang .doku-paper-scope (innerHTML
    // #live-preview-content ditimpa). State zoom disimpan di data-zoom scope.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-paper-zoom-in], [data-paper-zoom-out], [data-paper-zoom-reset]');
        if (!btn) return;
        var scope = btn.closest('.doku-paper-scope');
        if (!scope) return;
        var zoom = parseInt(scope.dataset.zoom || '100', 10);
        if (btn.hasAttribute('data-paper-zoom-in')) {
            zoom = Math.min(200, zoom + 10);
        } else if (btn.hasAttribute('data-paper-zoom-out')) {
            zoom = Math.max(50, zoom - 10);
        } else {
            zoom = 100;
        }
        scope.dataset.zoom = zoom;
        var paper = scope.querySelector('.doku-paper');
        if (paper) paper.style.zoom = zoom / 100;
        var label = scope.querySelector('[data-paper-zoom-label]');
        if (label) label.textContent = zoom + '%';
    });
</script>
@endonce