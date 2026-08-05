<style>
    /* WAJIB baris PERTAMA — @import harus paling atas di file CSS, sama
       seperti iframeStyle editor di resources/js/jodit.js. Tanpa ini font
       Google tidak tampil di preview halaman → beda dgn pratinjau Jodit. */
    @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,400&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Code+Pro:ital,wght@0,400;0,700;1,400&display=swap');

    .doku-paper-scope .doku-paper {
        width: 794px;
        margin: 0 auto;
        padding: 48px 56px;
        background-color: #fff;
        min-height: 1129px;
        border: 2px solid #6b7280;
        border-top: none; /* konsisten sama iframeStyle editor */
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        box-sizing: border-box;

        /* WAJIB match font & line-height iframe editor (yang pakai default
           browser: serif, line-height normal), biar tinggi render konten
           identik antara editor dan show. Kalau tidak, posisi garis
           pembatas halaman ini akan selalu meleset / berbeda jarak. */
        font-family: Times, "Times New Roman", serif;
        font-size: 16px;
        line-height: normal;
        color: #000; /* paksa gelap — jangan ikut warna body (putih di dark theme) */

        background-image: repeating-linear-gradient(
            to bottom,
            transparent 0,
            transparent 1129px,
            #d1d5db 1129px,
            #d1d5db 1135px
        ) !important;
        background-repeat: repeat-y !important;
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
</style>

<div class="doku-paper-scope">
    <div class="doku-paper">
        {!! $content !!}
    </div>
</div>