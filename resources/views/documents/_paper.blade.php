<style>
    /* WAJIB baris PERTAMA — @import harus paling atas di file CSS, sama
       seperti iframeStyle editor di resources/js/jodit.js. Tanpa ini font
       Google tidak tampil di preview halaman → beda dgn pratinjau Jodit. */
    @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,400&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Code+Pro:ital,wght@0,400;0,700;1,400&display=swap');

    @media print {
    .doku-paper-scope {
        max-height: none;
        overflow: visible;
        padding: 0;
        background: #fff;
    }
    .doku-paper-scope .doku-paper {
        border: none !important;
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
       border ini mempertegas perbedaannya. Saat print, border & shadow
       dimatikan (lihat @media print di atas) supaya tidak ikut tercetak. */
    border: 1px solid #d1d5db;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.08);
    box-sizing: border-box;
    font-family: Times, "Times New Roman", serif;
    font-size: 16px;
    line-height: normal;
    color: #000;
    /* Kata/teks panjang (URL, kode, font lebar) dipecah biar tidak
       meluber keluar kotak kertas preview. */
    overflow-wrap: break-word;
    word-break: break-word;
}

    /* Responsif: di layar sempit, kertas mengikuti lebar device dan
       margin halaman dikecilkan biar konten tidak kepotong. */
    @media (max-width: 640px) {
        .doku-paper-scope .doku-paper {
            padding: 24px 16px;
            min-height: auto;
        }
    }
    .doku-paper-scope .doku-paper::after {
        content: "";
        display: table;
        clear: both;
    }

    /* Netralkan preflight Tailwind di dalam paper: semua elemen konten
       dikembalikan ke default browser (UA stylesheet) — persis seperti
       render di iframe editor Jodit yang TIDAK kena preflight. Tanpa ini
       margin h1-h6, ukuran heading, list-style, dsb. di-reset preflight
       → tinggi konten beda → elemen yang di editor jatuh ke halaman
       berikutnya (di bawah garis) malah sejajar dengan garis di preview. */
    .doku-paper-scope .doku-paper :is(p, h1, h2, h3, h4, h5, h6, ul, ol, li, blockquote, pre, figure, dl, dd, dt, hr) {
        all: revert;
    }

    .doku-paper-scope .doku-paper table {
        width: 100%;
        border: none;
        border-collapse: collapse;
        empty-cells: show;
        max-width: 100%;
    }

    .doku-paper-scope .doku-paper th,
    .doku-paper-scope .doku-paper td {
        padding: 2px 5px;
        border: 1px solid #ccc;
    }

    .doku-paper-scope .doku-paper img {
        max-width: 100%;
        height: auto;
    }

    /* Toolbar kecil di atas kertas: dropdown ukuran kertas + tombol reset.
       Hanya tampil kalau halaman mengirim data-live-storage (punya editor
       terkait); halaman tanpa itu (mis. show) tetap murni menampilkan
       kertas tanpa kontrol. */
    .doku-paper-scope .doku-paper-toolbar {
        display: flex;
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
</style>

<div class="doku-paper-scope" @isset($liveStorage) data-live-storage="{{ $liveStorage }}" @endisset
     data-paper-size="{{ $paperSize ?? 'A4' }}"
     data-paper-margin="{{ json_encode($paperMargin ?? null) }}">
    @isset($liveStorage)
        <div class="doku-paper-toolbar">
            <label class="text-base-content/70">Ukuran Kertas:</label>
            <select data-paper-size-select>
                @foreach(['A4', 'A5', 'A3', 'Letter', 'Legal'] as $paperKey)
                    <option value="{{ $paperKey }}">{{ $paperKey }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    <div class="doku-paper">
        {!! $content !!}
    </div>
</div>