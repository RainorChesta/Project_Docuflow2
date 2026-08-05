<style>
    .doku-paper-scope .doku-paper {
        width: 794px;
        margin: 0 auto;
        padding: 48px 56px;
        background-color: #fff;
        min-height: 1123px;
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
            transparent 1117px,
            #d1d5db 1117px,
            #d1d5db 1123px
        ) !important;
        background-repeat: repeat-y !important;
    }

    .doku-paper-scope .doku-paper::after {
        content: "";
        display: table;
        clear: both;
    }

    /* Reset margin/padding default elemen supaya tidak kena style
       Tailwind/daisyUI dari luar — samakan juga dengan UA default iframe */
    .doku-paper-scope .doku-paper p {
        margin: 0 0 1em 0;
    }

    .doku-paper-scope .doku-paper ul,
    .doku-paper-scope .doku-paper ol {
        margin: 1em 0;
        padding-left: 40px;
    }

    /* Tailwind preflight reset list-style jadi none → bullet/nomor hilang.
       Di iframe editor tidak ada preflight (pakai default browser), jadi
       set eksplisit biar preview konsisten dengan editor. */
    .doku-paper-scope .doku-paper ul {
        list-style: disc;
    }

    .doku-paper-scope .doku-paper ol {
        list-style: decimal;
    }

    .doku-paper-scope .doku-paper li {
        list-style: inherit;
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