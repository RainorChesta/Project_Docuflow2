import { Jodit } from 'jodit';
import 'jodit/es2021/jodit.min.css';
import 'jodit/esm/plugins/all.js'; // registrasi semua plugin bawaan (wajib biar icon & fungsi lengkap)

// Daftar Google Fonts yang dipakai di dokumen — SATU sumber kebenaran,
// dipakai baik untuk import ke iframe MAUPUN untuk isi dropdown toolbar.
// key   = value CSS font-family (harus match persis dgn nama di Google Fonts)
// label = teks yang tampil di dropdown toolbar
const GOOGLE_FONTS_URL =
    'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900' +
    '&family=Open+Sans:ital,wght@0,300..800;1,300..800' +
    '&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,400' +
    '&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400' +
    '&family=Lora:ital,wght@0,400..700;1,400..700' +
    '&family=Source+Code+Pro:ital,wght@0,400;0,700;1,400' +
    '&display=swap';

const FONT_LIST = {
    'Default': 'Default',
    'Arial,Helvetica,sans-serif': 'Arial',
    'Georgia,serif': 'Georgia',
    '"Times New Roman",Times,serif': 'Times New Roman',
    '"Courier New",Courier,monospace': 'Courier New',
    // --- Google Fonts ---
    'Roboto,sans-serif': 'Roboto',
    '"Open Sans",sans-serif': 'Open Sans',
    'Merriweather,serif': 'Merriweather',
    'Poppins,sans-serif': 'Poppins',
    'Lora,serif': 'Lora',
    '"Source Code Pro",monospace': 'Source Code Pro',
};

// Ukuran kertas (px @96dpi). key = label yang tampil di dropdown.
// PENTING: nilai height di sini adalah SATU-SATUNYA sumber kebenaran untuk
// tinggi halaman — dipakai baik untuk simulasi page-break di editor
// (repaginateEditor) MAUPUN untuk memaksa ukuran halaman fisik saat print/
// export (lihat controls.print.exec di bawah, yang set @page { size }).
// Karena dua-duanya baca dari objek yang sama, keduanya dijamin selalu sinkron.
//
// PENTING JUGA: nilai px di sini WAJIB SAMA PERSIS dengan PAPER_SIZES_PX di
// app/Services/PdfExportService.php — kalau salah satu berubah, yang lain
// harus ikut disesuaikan manual (PHP tidak bisa import dari file JS ini).
const PAPER_SIZES = {
    'A4': { width: 794, height: 1123 },
    'A5': { width: 559, height: 794 },
    'A3': { width: 1123, height: 1587 },
    'Letter': { width: 816, height: 1056 },
    'Legal': { width: 816, height: 1344 },
};

// Margin default (px), match dengan padding lama "48px 56px" (atas/bawah 48,
// kanan/kiri 56). Ini SATU-SATUNYA sumber kebenaran untuk margin halaman —
// dipakai di iframeStyle (editor & preview) dan dibaca lagi saat print/export.
// UI margin menampilkan cm (dikonversi), nilai internal tetap px.
//
// PENTING: nilai ini WAJIB SAMA PERSIS dengan DEFAULT_MARGIN di
// app/Services/PdfExportService.php.
const DEFAULT_MARGIN = { top: 48, right: 56, bottom: 48, left: 56 };

// Ruang tulis minimum per halaman (px) — SAMA PERSIS dengan
// MIN_PAGE_CONTENT_PX di app/Services/PdfExportService.php. Dipakai oleh
// clampMarginToPage() di bawah untuk membatasi margin supaya tidak
// "memakan" seluruh halaman.
const MIN_PAGE_CONTENT_PX = 60;

// Clamp margin ke ukuran kertas: margin gabungan (top+bottom / left+right)
// tidak boleh melebihi ukuran kertas dikurangi ruang tulis minimum.
//
// FIX AKAR MASALAH margin ekstrem (mis. 15cm/30cm) tidak sesuai antara
// editor & hasil export PDF: PdfExportService.php SUDAH lebih dulu punya
// clamp seperti ini (lihat komentar di buildHtml() di sana, yang secara
// eksplisit merujuk "clampMarginToPage() di jodit.js" — padahal fungsi itu
// sebelumnya TIDAK PERNAH ada di file ini). Akibatnya:
//   - Editor: contentPerPage = size.height - margin.top - margin.bottom
//     bisa jadi 0/negatif untuk margin ekstrem → pagination visual di
//     editor kacau/tidak presisi, dan @page margin yang dikirim ke print
//     browser juga mentah (melebihi tinggi kertas, perilakunya tidak
//     konsisten antar browser).
//   - Server: PdfExportService diam-diam MENGECILKAN margin.top/left
//     supaya konten tetap muat & tercetak.
// Dua sisi menghasilkan margin efektif yang berbeda pada kasus yang sama
// persis itulah yang bikin hasil export "tidak sesuai" editor.
//
// Fungsi ini WAJIB tetap sinkron (nilai & logika) dengan clamp yang ada di
// PdfExportService::buildHtml() — kalau salah satu berubah, yang lain harus
// ikut disesuaikan manual.
function clampMarginToPage(size, margin) {
    const clamped = { ...margin };
    if (clamped.top + clamped.bottom > size.height - MIN_PAGE_CONTENT_PX) {
        clamped.top = Math.max(0, size.height - MIN_PAGE_CONTENT_PX - clamped.bottom);
    }
    if (clamped.left + clamped.right > size.width - MIN_PAGE_CONTENT_PX) {
        clamped.left = Math.max(0, size.width - MIN_PAGE_CONTENT_PX - clamped.right);
    }
    return clamped;
}

// Cari nama key ukuran kertas (A4/A5/...) dari objek size — dipakai untuk
// menyimpan pilihan kertas ke localStorage supaya halaman preview tahu
// ukuran mana yang aktif di editor. Pencocokan by reference aman karena
// semua pemanggil selalu meneruskan objek dari PAPER_SIZES yang sama.
function findPaperKey(size) {
    for (const key of Object.keys(PAPER_SIZES)) {
        if (PAPER_SIZES[key] === size) return key;
    }
    return null;
}

// Bangun CSS iframeStyle dari ukuran kertas + margin. Dipakai saat init
// editor DAN saat preview/print (yang regenerate dokumen via iframeStyle).
//
// CATATAN FIX: dulu di sini ada `background-image: repeating-linear-gradient(...)`
// sebagai garis pemisah halaman visual. Itu DIBUANG karena jadi sumber
// kebenaran KEDUA yang independen dari spacer nyata yang disisipkan
// repaginateEditor — begitu spacer pertama disisipkan, konten halaman 2 dst
// ketarik turun sejauh margin.top+margin.bottom px, tapi background-image
// adalah pola CSS statis yang TIDAK ikut bergeser (tetap ngulang di kelipatan
// tinggi halaman apa adanya). Akibatnya garis background & spacer asli makin
// ke bawah makin tidak sinkron — itulah sebabnya margin halaman 1 kelihatan
// "benar" (kebetulan align di titik 0) sementara halaman berikutnya meleset,
// plus area itu jadi dobel-highlight (garis gradient + blok spacer abu-abu).
// Sekarang spacer nyata (lihat repaginateEditor) jadi SATU-SATUNYA sumber
// kebenaran untuk tampilan batas halaman.
function buildIframeStyle(size, margin = DEFAULT_MARGIN) {
    const padding = `${margin.top}px ${margin.right}px ${margin.bottom}px ${margin.left}px`;
    return [
        `@import url('${GOOGLE_FONTS_URL}');`,
        'html { margin:0; padding:0; background:#e5e7eb; }',
        'body {',
        '  box-sizing:border-box;',
        `  width:${size.width}px;`,
        '  margin:0 auto;',
        `  padding:${padding};`,
        '  background:#fff;',
        `  min-height:${size.height}px;`,
        '  box-shadow:0 1px 3px rgba(0,0,0,0.1);',
        '}',
        'table { width:100%; border:none; border-collapse:collapse; empty-cells:show; max-width:100%; }',
        'th, td { padding:2px 5px; border:1px solid #ccc; }',
        'body, p, div, td, th, li, h1, h2, h3, h4, h5, h6 { overflow-wrap:break-word; word-break:break-word; }',
    ].join('\n');
}

// Terapkan ukuran kertas + margin ke editor yang sudah hidup: update
// iframeStyle (biar preview/print konsisten) + inline style body (biar live
// editor langsung berubah tanpa reload) + tinggi container mengikuti kertas.
// size dan margin sama-sama opsional — kalau tidak dikasih, pakai yang lagi
// aktif di editor (atau default kalau belum pernah diset sama sekali).
function applyPaperSize(editor, size, margin) {
    size = size || editor.currentPaperSize || PAPER_SIZES['A4'];
    // FIX: margin di-clamp ke ukuran kertas SEBELUM dipakai di mana pun —
    // ini SATU-SATUNYA titik di mana margin masuk ke state editor
    // (iframeStyle, body style, localStorage), jadi cukup diclamp di sini
    // supaya semua jalur pemanggil (init, ganti ukuran kertas, popup
    // margin) otomatis konsisten dengan clamp yang sama di
    // PdfExportService.php.
    margin = clampMarginToPage(size, margin || editor.currentMargin || DEFAULT_MARGIN);

    editor.o.iframeStyle = buildIframeStyle(size, margin);
    // Simpan ukuran & margin aktif di instance editor supaya
    // controls.print.exec (fungsi terpisah, dipanggil belakangan saat
    // tombol print diklik) tau persis nilai mana yang lagi dipakai sekarang.
    editor.currentPaperSize = size;
    editor.currentMargin = margin;

    // Sinkronkan pilihan kertas & margin ke localStorage supaya halaman
    // preview (tab lain / halaman show) bisa menampilkan batas halaman yang
    // SAMA PERSIS dengan editor. Key dibangun dari data-live-storage textarea
    // (mis. "doc-preview-3") + suffix, jadi per-dokumen.
    const storageKey = editor.element?.dataset?.liveStorage;
    if (storageKey) {
        const paperKey = findPaperKey(size);
        try {
            localStorage.setItem(storageKey + ':paper', JSON.stringify({
                size: paperKey || size,
                margin,
            }));
        } catch (e) { /* localStorage penuh / private mode — abaikan */ }
    }

    const body = editor.editor;
    if (!body) return;
    // FIX: tidak lagi set backgroundImage di sini — lihat catatan panjang
    // di buildIframeStyle soal kenapa pola background statis dibuang.
    Object.assign(body.style, {
        width: size.width + 'px',
        padding: `${margin.top}px ${margin.right}px ${margin.bottom}px ${margin.left}px`,
    });
    // Atur min-height container Jodit → plugin size otomatis menghitung
    // min-height body = containerMin − toolbar, sehingga body pas tinggi
    // kertas. (margin.top+margin.bottom) = padding atas/bawah sesuai margin
    // yang aktif (dulu hardcoded 96 = 48×2). +64 = ruang antar halaman.
    // +15 = tinggi toolbar Jodit (dikurangi plugin size dari body).
    //
    // FIX: dulu di sini JUGA di-set `container.style.height` (bukan cuma
    // minHeight) memakai containerH yang rumusnya cuma ngitung buat SATU
    // halaman. Kalau dokumen butuh >1 halaman (dan makin butuh banyak
    // halaman kalau margin makin besar, karena ruang tulis per halaman
    // makin sempit), height yang dipatok fix itu jadi lebih kecil dari
    // tinggi konten sebenarnya → sisa konten (termasuk spacer/garis
    // pembatas halaman berikutnya) ke-clip/ketutup di luar area yang
    // kelihatan, padahal elemennya tetap ada normal di DOM. Auto-grow
    // alami Jodit (yang sudah terbukti benar saat ngetik biasa tanpa
    // ganti margin) jadi ke-override sama height fix ini. Sekarang cukup
    // set minHeight (batas bawah saja, biar editor tidak keliatan kepetit
    // pas dokumen masih pendek/kosong) — container tetap bebas tumbuh lebih
    // tinggi dari itu kalau kontennya emang butuh lebih dari 1 halaman.
    const containerH = size.height + margin.top + margin.bottom + 64 + 15;
    editor.container.style.minHeight = containerH + 'px';
    // Plugin size Jodit menghitung min-height body dari container saat
    // resize. Paksa re-kalkulasi biar body ikut tinggi kertas baru.
    editor.e.fire('resize');
    editor.e.fire('afterResize');
    // FIX: repaginateEditor TIDAK dipanggil langsung/sinkron di sini lagi.
    // Plugin resize bawaan Jodit (dipicu 'resize'/'afterResize' di atas)
    // baru benar-benar selesai reflow body (min-height, dsb) di frame
    // render berikutnya — kalau repaginateEditor dipanggil sinkron sebelum
    // itu settle, dia sempat mengukur & menyisipkan spacer pakai layout yang
    // belum final, lalu keburu "ketimpa" ulang oleh reflow plugin resize
    // sesudahnya → garis pembatas halaman yang baru disisipkan kelihatan
    // hilang setelah margin/ukuran kertas diganti. Menunda ke
    // requestAnimationFrame memastikan repaginateEditor jalan SETELAH resize
    // plugin selesai, mengukur layout yang sudah final.
    //
    // FIX #2 (dobel rAF): satu rAF ternyata kadang masih belum cukup — pada
    // kasus ganti ukuran kertas, plugin size Jodit menyelesaikan reflow-nya
    // dalam DUA microtask/frame terpisah (resize awal lalu penyesuaian
    // ukuran body lanjutan). Kalau repaginateEditor jalan di antara
    // keduanya, dia mengukur boundingClientRect yang masih "separuh jalan"
    // → boundary halaman salah hitung dan spacer yang baru disisipkan
    // langsung ketimpa ulang oleh reflow kedua (persis gejala "garis
    // pembatas ikut hilang saat ukuran kertas diubah"). Menunggu DUA rAF
    // berturut-turut memastikan repaginateEditor benar-benar jalan setelah
    // browser selesai commit layout final.
    requestAnimationFrame(() => {
        requestAnimationFrame(() => repaginateEditor(editor));
    });
}

// Ambil HTML editor tanpa elemen spacer pagination (lihat repaginateEditor
// di bawah) — WAJIB dipakai tiap kali konten mau disimpan/di-print/di-preview,
// supaya jeda visual antar halaman di editor TIDAK ikut kesimpen sebagai
// bagian dari dokumen asli.
//
// FIX EXPORT: dulu spacer SELALU dibuang polos (el.remove()), baik untuk
// disimpan ke DB/localStorage MAUPUN untuk di-print. Itu benar untuk kasus
// simpan (DB tidak boleh punya elemen jeda buatan), tapi SALAH untuk print —
// begitu spacer dibuang, browser tidak tahu sama sekali di mana keputusan
// pagination yang sudah dihitung repaginateEditor tadi berada, dan dia
// memotong halaman sendiri pakai reflow-nya (yang boleh motong DI TENGAH
// elemen, dan titik potongnya bisa beda dari yang di editor). Itu sebabnya
// elemen yang di editor sudah didorong ke halaman 2 bisa balik "nempel" ke
// halaman 1 saat hasil export — dua sumber kebenaran pagination yang
// independen (repaginateEditor vs reflow print browser) gampang meleset.
//
// Sekarang ada parameter forPrint: kalau true, tiap spacer diganti jadi
// forced page-break asli (`break-after: page`) TEPAT DI POSISI yang sama
// dengan keputusan repaginateEditor — jadi titik potong export dijamin
// identik dengan titik potong yang terlihat di editor, bukan ditebak ulang
// oleh browser. Elemen level atas juga dikunci `break-inside: avoid` supaya
// browser tidak boleh memotong satu elemen jadi dua halaman (konsisten
// dengan aturan "elemen tidak pernah dipecah" yang sudah dipakai
// repaginateEditor).
function getCleanValue(editor, { forPrint = false } = {}) {
    const raw = editor.value;
    const doc = new DOMParser().parseFromString(raw, 'text/html');

    doc.querySelectorAll('[data-page-spacer]').forEach((el) => {
        if (forPrint) {
            const pageBreak = doc.createElement('div');
            pageBreak.setAttribute('data-page-break', 'true');
            pageBreak.style.cssText = 'height:0;margin:0;padding:0;border:0;' +
                'break-after:page;page-break-after:always;';
            el.replaceWith(pageBreak);
        } else {
            el.remove();
        }
    });

    if (forPrint) {
        // Elemen level atas tidak boleh dipotong browser di tengah saat
        // print/export — mencerminkan aturan yang sama dipakai
        // repaginateEditor ("elemen didorong utuh ke halaman berikutnya,
        // tidak pernah dipecah separuh-separuh").
        doc.body.querySelectorAll(':scope > *:not([data-page-break])').forEach((el) => {
            el.style.breakInside = 'avoid';
            el.style.pageBreakInside = 'avoid';
        });
    }

    return doc.body.innerHTML;
}

// Simulasikan margin nyata antar "halaman visual" di editor: begitu konten
// nyampe/ngelewatin batas satu halaman (size.height), sisipkan elemen jeda
// (non-editable) setinggi margin.bottom + margin.top TEPAT SEBELUM elemen
// yang jatuh ke halaman berikutnya — supaya elemen itu betul-betul terdorong
// turun sejauh margin, bukan cuma ada garis penanda doang.
//
// FIX tampilan (ala Ms Word): spacer TIDAK lagi satu blok abu-abu solid
// setinggi margin penuh (itu yang bikin kesan "margin di-highlight" dan beda
// dari halaman 1). Sekarang spacer terdiri dari 3 bagian dengan TOTAL TINGGI
// TETAP SAMA (margin.top + margin.bottom, tidak berubah — supaya jeda visual
// di editor tetap presisi sinkron dgn margin asli yang dipakai print/export):
//   1. endPart   — tinggi margin.bottom, PUTIH (margin bawah halaman yang
//                  berakhir, konsisten dgn margin halaman 1 yang juga putih)
//                  + shadow tipis simulasi tepi kertas.
//   2. gapLine   — garis abu-abu tipis 2px, penanda batas antar halaman.
//   3. startPart — tinggi (margin.top - 2), PUTIH (margin atas halaman
//                  berikutnya).
//
// Batasan yang disadari (approximation, bukan pagination sungguhan kayak
// Word/Google Docs):
// - Elemen TIDAK pernah dipecah di tengah; kalau satu elemen (mis. paragraf
//   panjang / gambar besar) melewati batas halaman, seluruh elemen itu utuh
//   didorong ke halaman berikutnya. Efeknya mirip aturan CSS
//   `break-inside: avoid` — rapi, tapi bisa nyisain rongga kosong di akhir
//   halaman sebelumnya kalau elemennya cukup besar.
// - Elemen yang lebih tinggi dari satu halaman penuh (tabel/gambar raksasa)
//   tidak otomatis terpotong di sini; itu di luar cakupan simulasi ini.
// - Karena semua halaman masih satu <body> contenteditable yang menyambung
//   (bukan kotak terpisah per halaman), box-shadow di level body cuma bikin
//   bayangan di tepi luar SELURUH dokumen, bukan per-halaman seperti Word/
//   Google Docs asli. Shadow tipis di endPart adalah pendekatan visual saja.
// - Ini SEMATA-MATA tampilan editor. Hasil final yang presisi tetap PDF
//   export (yang pagination-nya sekarang memakai forced page-break persis
//   di posisi spacer ini — lihat getCleanValue({ forPrint: true })).
function repaginateEditor(editor) {
    const body = editor.editor;
    if (!body || editor._isRepaginating) return;

    const size = editor.currentPaperSize || PAPER_SIZES['A4'];
    const margin = editor.currentMargin || DEFAULT_MARGIN;
    const gap = margin.top + margin.bottom;

    editor._isRepaginating = true; // cegah rekursi dari 'change' yang terpicu oleh mutasi kita sendiri
    // Matikan sementara observer spacer (lihat initJoditEditor) supaya
    // penghapusan spacer oleh repaginate sendiri tidak memicu repagination
    // ulang. Flag dibersihkan via microtask di finally — MutationObserver
    // callback (microtask yang di-queue saat mutasi terjadi) jalan SEBELUM
    // microtask pembersih ini, jadi mutasi kita sendiri selalu diabaikan.
    editor._suppressSpacerObserver = true;
    try {
        // 1. Buang semua spacer lama dulu → perhitungan selalu mulai dari
        //    kondisi "flat" (posisi asli elemen tanpa jeda buatan).
        body.querySelectorAll(':scope > [data-page-spacer]').forEach((el) => el.remove());

        const children = Array.from(body.children);
        if (children.length === 0) {
            editor.synchronizeValues();
            return;
        }

        const paddingTop = parseFloat(getComputedStyle(body).paddingTop) || 0;
        const bodyTop = body.getBoundingClientRect().top;
        // PENTING: batas halaman BUKAN di kelipatan size.height (tinggi kertas
        // penuh) — margin.top sudah "terpakai" duluan sebagai padding-top body
        // sebelum konten mulai. Jadi ruang tulis yang beneran tersedia per
        // halaman cuma segini. FIX: dijaga minimal 1px lewat Math.max sebagai
        // jaring pengaman terakhir — margin sendiri sudah diclamp lebih dulu
        // di applyPaperSize()/repaginatePreview() lewat clampMarginToPage(),
        // jadi nilai ini seharusnya tidak akan pernah ≤ 0 lagi, tapi guard ini
        // tetap dipertahankan untuk data lama (localStorage/DB) dari sebelum
        // clamp ini ada.
        const contentPerPage = Math.max(size.height - margin.top - margin.bottom, 1);
        let nextBoundary = contentPerPage;

        for (const child of children) {
            const rect = child.getBoundingClientRect();
            const relTop = rect.top - bodyTop - paddingTop;
            const relBottom = relTop + rect.height;

            // Elemen ini akan "kepotong" batas halaman kalau bagian bawahnya
            // melewati nextBoundary sementara bagian atasnya masih di
            // halaman sekarang → dorong utuh ke halaman berikutnya.
            //
            // FIX: selain straddle, elemen yang MULAI di halaman berikutnya
            // (relTop >= nextBoundary) juga butuh spacer sebagai batas halaman.
            // Ini terjadi saat halaman sebelumnya penuh TEPAT di boundary —
            // elemen berikutnya jatuh ke halaman 2 tanpa straddle, sehingga
            // kondisi straddle lama (relTop < nextBoundary) gagal dan garis
            // pembatas antar halaman tidak pernah muncul. Kondisi onNextPage
            // menangkap elemen yang mulai di dalam halaman berikutnya.
            while (
                (relBottom > nextBoundary && relTop < nextBoundary) ||
                (relTop >= nextBoundary && relTop < nextBoundary + contentPerPage)
            ) {
                const spacer = document.createElement('div');
                spacer.setAttribute('data-page-spacer', 'true');
                spacer.setAttribute('contenteditable', 'false');
                Object.assign(spacer.style, {
                    margin: `0 -${margin.right}px 0 -${margin.left}px`,
                    pointerEvents: 'none',
                    userSelect: 'none',
                });

                // Pita abu-abu pembatas antar halaman (ala Google Docs/Word) —
                // dibikin cukup tebal biar KELIHATAN JELAS (fix dari versi
                // sebelumnya yang cuma 2px & warna terlalu terang, sehingga
                // secara teknis ada di DOM tapi ke mata nyaris tak terlihat).
                // Tinggi pita dibatasi max 24px (atau separuh dari total gap
                // kalau margin-nya kecil) supaya tidak "memakan" margin putih
                // sampai habis pada dokumen dengan margin kecil.
                const gapBandHeight = Math.max(2, Math.min(24, Math.round(gap * 0.3)));
                const remaining = gap - gapBandHeight;
                // Sisa tinggi (yang tetap putih) dibagi proporsional sesuai
                // rasio margin.bottom : margin.top asli, biar margin halaman
                // yang berakhir & margin halaman berikutnya tetap terasa
                // konsisten sama besarannya waktu margin diubah-ubah.
                const beforeHeight = gap > 0 ? Math.round(remaining * (margin.bottom / gap)) : 0;
                const afterHeight = remaining - beforeHeight;

                // Margin bawah halaman yang berakhir — putih, konsisten dgn
                // margin halaman 1 (bukan area highlight), + shadow tipis
                // simulasi tepi kertas.
                const endPart = document.createElement('div');
                Object.assign(endPart.style, {
                    height: beforeHeight + 'px',
                    background: '#fff',
                    boxShadow: '0 2px 4px -1px rgba(0,0,0,0.15)',
                });

                // Pita pembatas antar halaman — warna abu-abu jelas + garis
                // tepi sedikit lebih gelap di atas & bawah biar terasa kayak
                // "tepi kertas" yang terpisah, bukan cuma satu warna rata.
                const gapLine = document.createElement('div');
                Object.assign(gapLine.style, {
                    height: gapBandHeight + 'px',
                    background: '#cbd5e1',
                    borderTop: '1px solid #94a3b8',
                    borderBottom: '1px solid #94a3b8',
                    boxSizing: 'border-box',
                });

                // Margin atas halaman berikutnya — putih.
                const startPart = document.createElement('div');
                Object.assign(startPart.style, {
                    height: afterHeight + 'px',
                    background: '#fff',
                });

                spacer.appendChild(endPart);
                spacer.appendChild(gapLine);
                spacer.appendChild(startPart);

                child.parentNode.insertBefore(spacer, child);
                nextBoundary += contentPerPage + gap;
            }
        }

        // AKAR MASALAH "garis pembatas hilang" ada di sini: Jodit TIDAK pakai
        // MutationObserver buat melacak perubahan konten (ini dikonfirmasi di
        // dokumentasi resminya). Semua manipulasi DOM di atas (insertBefore,
        // el.remove()) dilakukan LANGSUNG ke body, BUKAN lewat API resmi
        // Jodit (mis. editor.selection.insertHTML) — jadi `editor.value`
        // (cache internal Jodit) TETAP versi lama yang tidak punya spacer.
        // Begitu ada proses lain yang bikin Jodit re-render isi editor dari
        // cache basi itu (mis. size plugin pas ganti margin/kertas), yang
        // muncul balik ya versi tanpa spacer → garis pembatas kelihatan
        // hilang. synchronizeValues() memaksa Jodit membaca ulang DOM yang
        // SEKARANG (dengan spacer) ke dalam cache-nya — jadi kalau nanti
        // Jodit re-render dari cache, spacer ikut kebawa, bukan hilang.
        // Aman dipanggil sesering apa pun karena cuma BACA dari DOM ke cache
        // (bukan nulis ulang DOM), jadi tidak mengganggu posisi kursor.
        editor.synchronizeValues();
    } finally {
        editor._isRepaginating = false;
        // Bersihkan flag suppress di microtask (lihat catatan di atas).
        queueMicrotask(() => { editor._suppressSpacerObserver = false; });
    }
}

// Throttle berbasis requestAnimationFrame supaya repaginateEditor tidak jalan
// dobel-dobel di tiap keystroke, TAPI tetap terasa instan (dihitung ulang di
// frame render berikutnya, ~16ms) — bukan debounce setTimeout seperti
// sebelumnya. Debounce lama nunggu user BERHENTI ngetik dulu (400ms jeda)
// baru recompute, jadi kalau di-spam Enter garis page-break berikutnya
// kelihatan delay/telat muncul karena timer-nya terus kereset tiap keystroke.
// Dengan rAF, semua event 'change' yang numpuk dalam satu frame (mis. spam
// Enter, atau paste teks panjang) otomatis digabung jadi SATU recompute di
// frame berikutnya — jadi selalu update secepat browser bisa render, walau
// ketikannya beruntun.
function scheduleRepaginate(editor) {
    if (editor._repaginateRAF) return; // sudah dijadwalkan untuk frame berikutnya, jangan dobel
    editor._repaginateRAF = requestAnimationFrame(() => {
        editor._repaginateRAF = null;
        repaginateEditor(editor);
    });
}

// ─── Pagination untuk halaman PREVIEW (non-editor) ────────────────────────
// Preview (show / preview / preview-version) menampilkan konten sebagai satu
// kotak kertas statis (.doku-paper) tanpa batas antar halaman. Fungsi ini
// menyisipkan spacer pembatas halaman yang SAMA PERSIS dengan yang dipakai
// editor (repaginateEditor), supaya preview dan editor selalu konsisten.
//
// Berbeda dari editor, preview TIDAK punya iframe terisolasi — .doku-paper
// hidup di halaman utama yang kena preflight Tailwind. Karena itu:
//   - Ukuran kertas & margin dibaca dari localStorage (diset oleh editor via
//     applyPaperSize) atau default A4.
//   - Lebar kertas di-set inline (px) supaya konsisten dengan editor, bukan
//     max-width:794px yang responsif.
//   - Spacer memakai margin negatif kiri/kanan agar melebar penuh selebar
//     kertas (mengimbangi padding kertas), sama seperti di editor.
//   - Elemen konten diukur via getBoundingClientRect relatif terhadap paper.
function repaginatePreview(paperEl, size, margin) {
    if (!paperEl) return;
    size = size || PAPER_SIZES['A4'];
    // FIX: clamp margin ke ukuran kertas — sama seperti applyPaperSize().
    // Preview membaca margin dari localStorage/DB, yang bisa jadi data
    // lama (dari sebelum clampMarginToPage() ada) atau nilai kertas hasil
    // dropdown ukuran kertas di preview itu sendiri (lihat
    // initPreviewPagination) yang belum tentu sudah diclamp terhadap
    // ukuran kertas BARU yang dipilih di situ.
    margin = clampMarginToPage(size, margin || DEFAULT_MARGIN);
    const gap = margin.top + margin.bottom;

    // Terapkan ukuran kertas & margin ke elemen kertas.
    paperEl.style.width = size.width + 'px';
    paperEl.style.minHeight = size.height + 'px';
    paperEl.style.padding = `${margin.top}px ${margin.right}px ${margin.bottom}px ${margin.left}px`;

    // Buang spacer lama → hitung ulang dari kondisi flat.
    paperEl.querySelectorAll(':scope > [data-page-spacer]').forEach((el) => el.remove());

    const children = Array.from(paperEl.children);
    if (children.length === 0) return;

    const paddingTop = parseFloat(getComputedStyle(paperEl).paddingTop) || 0;
    const paperTop = paperEl.getBoundingClientRect().top;
    const contentPerPage = Math.max(size.height - margin.top - margin.bottom, 1);
    let nextBoundary = contentPerPage;

    for (const child of children) {
        const rect = child.getBoundingClientRect();
        const relTop = rect.top - paperTop - paddingTop;
        const relBottom = relTop + rect.height;

        // Sama seperti repaginateEditor: selain straddle, elemen yang MULAI
        // di halaman berikutnya (halaman sebelumnya penuh tepat di boundary)
        // juga butuh spacer sebagai batas halaman.
        while (
            (relBottom > nextBoundary && relTop < nextBoundary) ||
            (relTop >= nextBoundary && relTop < nextBoundary + contentPerPage)
        ) {
            const spacer = document.createElement('div');
            spacer.setAttribute('data-page-spacer', 'true');
            Object.assign(spacer.style, {
                margin: `0 -${margin.right}px 0 -${margin.left}px`,
                pointerEvents: 'none',
                userSelect: 'none',
            });

            const gapBandHeight = Math.max(2, Math.min(24, Math.round(gap * 0.3)));
            const remaining = gap - gapBandHeight;
            const beforeHeight = gap > 0 ? Math.round(remaining * (margin.bottom / gap)) : 0;
            const afterHeight = remaining - beforeHeight;

            const endPart = document.createElement('div');
            Object.assign(endPart.style, {
                height: beforeHeight + 'px',
                background: '#fff',
                boxShadow: '0 2px 4px -1px rgba(0,0,0,0.15)',
            });

            const gapLine = document.createElement('div');
            Object.assign(gapLine.style, {
                height: gapBandHeight + 'px',
                background: '#cbd5e1',
                borderTop: '1px solid #94a3b8',
                borderBottom: '1px solid #94a3b8',
                boxSizing: 'border-box',
            });

            const startPart = document.createElement('div');
            Object.assign(startPart.style, {
                height: afterHeight + 'px',
                background: '#fff',
            });

            spacer.appendChild(endPart);
            spacer.appendChild(gapLine);
            spacer.appendChild(startPart);

            child.parentNode.insertBefore(spacer, child);
            nextBoundary += contentPerPage + gap;
        }
    }
}

// Baca ukuran kertas & margin yang disimpan editor untuk dokumen ini.
// storageKey = nilai data-live-storage (mis. "doc-preview-3").
function readStoredPaper(storageKey) {
    try {
        const raw = localStorage.getItem(storageKey + ':paper');
        if (!raw) return null;
        const data = JSON.parse(raw);
        const size = typeof data.size === 'string' && PAPER_SIZES[data.size]
            ? PAPER_SIZES[data.size]
            : (data.size && data.size.width ? data.size : null);
        const margin = data.margin && data.margin.top != null ? data.margin : null;
        if (!size || !margin) return null;
        return { size, margin };
    } catch (e) {
        return null;
    }
}

// Inisialisasi pagination untuk halaman preview: cari .doku-paper, baca
// ukuran kertas dari localStorage, lalu sisipkan spacer. Dipanggil dari
// halaman show / preview-version / preview (lihat initPreviewPagination).
// `scope` bisa berupa selector string ATAU elemen .doku-paper-scope langsung
// (dipakai preview.blade.php saat render ulang konten live dari localStorage).
function initPreviewPagination(scopeSelector = '.doku-paper-scope') {
    const scope = typeof scopeSelector === 'string'
        ? document.querySelector(scopeSelector)
        : scopeSelector;
    const paper = scope?.querySelector('.doku-paper');
    if (!paper) return;

    const storageKey = scope.dataset?.liveStorage;
    const stored = storageKey ? readStoredPaper(storageKey) : null;
    // Prioritas: localStorage (draft yang belum disimpan di editor) > data
    // attribute dari DB (paper_size/paper_margin dokumen) > default A4.
    let size = stored?.size || null;
    let margin = stored?.margin || null;
    if (!size && scope.dataset.paperSize && PAPER_SIZES[scope.dataset.paperSize]) {
        size = PAPER_SIZES[scope.dataset.paperSize];
    }
    if (!margin && scope.dataset.paperMargin) {
        try {
            const m = JSON.parse(scope.dataset.paperMargin);
            if (m && m.top != null) margin = m;
        } catch (e) { /* abaikan */ }
    }
    size = size || PAPER_SIZES['A4'];
    margin = margin || DEFAULT_MARGIN;

    repaginatePreview(paper, size, margin);

    // Kalau halaman ini punya dropdown ukuran kertas (lihat _paper.blade.php),
    // pasang handler-nya.
    const select = scope.querySelector('[data-paper-size-select]');
    if (select) {
        select.value = findPaperKey(size) || 'A4';
        select.addEventListener('change', () => {
            const key = select.value;
            const newSize = PAPER_SIZES[key];
            if (!newSize) return;
            repaginatePreview(paper, newSize, margin);
            // Simpan pilihan supaya konsisten dengan editor & preview lain.
            if (storageKey) {
                try {
                    localStorage.setItem(storageKey + ':paper', JSON.stringify({ size: key, margin }));
                } catch (e) { /* abaikan */ }
            }
        });
    }
}


// Margin internal disimpan dalam px (DEFAULT_MARGIN). Popup menampilkan
// dan menerima nilai dalam cm: 1cm = 96/2.54 px.
const PX_PER_CM = 96 / 2.54;

// Dipanggil dari tombol toolbar "margin" — lihat controls.margin di bawah.
function buildMarginPopup(editor, close) {
    const current = editor.currentMargin || DEFAULT_MARGIN;
    const fields = [
        { key: 'top', label: 'Atas' },
        { key: 'right', label: 'Kanan' },
        { key: 'bottom', label: 'Bawah' },
        { key: 'left', label: 'Kiri' },
    ];

    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'padding:12px; display:flex; flex-direction:column; gap:8px; min-width:220px; background:#fff;';

    const title = document.createElement('div');
    title.textContent = 'Margin Halaman (cm)';
    title.style.cssText = 'font-weight:600; margin-bottom:4px; color:#1a1a1a;';
    wrapper.appendChild(title);

    const inputs = {};
    fields.forEach(({ key, label }) => {
        const row = document.createElement('div');
        row.style.cssText = 'display:flex; align-items:center; justify-content:space-between; gap:10px;';

        const lbl = document.createElement('label');
        lbl.textContent = label;
        lbl.style.cssText = 'color:#1a1a1a; font-size:13px;';

        const input = document.createElement('input');
        input.type = 'number';
        input.min = '0';
        input.step = '0.1';
        input.value = (current[key] / PX_PER_CM).toFixed(2);
        input.style.cssText = 'width:70px; padding:4px 6px; border:1px solid #ccc; border-radius:4px;';

        inputs[key] = input;
        row.appendChild(lbl);
        row.appendChild(input);
        wrapper.appendChild(row);
    });

    const errorMsg = document.createElement('div');
    errorMsg.style.cssText = 'color:#b91c1c; font-size:12px; display:none;';
    errorMsg.textContent = 'Margin harus angka ≥ 0.';
    wrapper.appendChild(errorMsg);

    // BARU: pesan info kalau margin di-clamp otomatis supaya muat di
    // kertas (bukan error — sama seperti yang akan dilakukan
    // PdfExportService saat export, lihat clampMarginToPage()).
    const infoMsg = document.createElement('div');
    infoMsg.style.cssText = 'color:#92400e; font-size:12px; display:none;';
    wrapper.appendChild(infoMsg);

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Terapkan';
    btn.style.cssText = 'margin-top:6px; padding:6px 10px; cursor:pointer; border:1px solid #ccc; border-radius:4px; background:#f3f4f6;';
    btn.addEventListener('click', () => {
        const next = {};
        for (const { key } of fields) {
            const v = parseFloat(inputs[key].value);
            if (!Number.isFinite(v) || v < 0) {
                errorMsg.style.display = 'block';
                infoMsg.style.display = 'none';
                return;
            }
            next[key] = Math.round(v * PX_PER_CM);
        }
        errorMsg.style.display = 'none';

        // FIX: clamp margin SEBELUM diterapkan — sama seperti yang
        // dilakukan PdfExportService::buildHtml() saat export, supaya
        // margin efektif di editor & hasil PDF selalu identik untuk
        // margin ekstrem (mis. 15cm/30cm).
        const size = editor.currentPaperSize || PAPER_SIZES['A4'];
        const clamped = clampMarginToPage(size, next);
        const wasClamped = clamped.top !== next.top || clamped.left !== next.left;

        applyPaperSize(editor, editor.currentPaperSize, clamped);

        if (wasClamped) {
            infoMsg.textContent = `Margin disesuaikan otomatis (maks ~${((size.height - MIN_PAGE_CONTENT_PX) / PX_PER_CM).toFixed(1)}cm atas+bawah, ~${((size.width - MIN_PAGE_CONTENT_PX) / PX_PER_CM).toFixed(1)}cm kiri+kanan) supaya tetap muat di kertas.`;
            infoMsg.style.display = 'block';
            // Update input agar mencerminkan nilai yang benar-benar terpakai.
            fields.forEach(({ key }) => {
                inputs[key].value = (clamped[key] / PX_PER_CM).toFixed(2);
            });
            return; // biarkan popup tetap terbuka biar user lihat pesannya
        }

        infoMsg.style.display = 'none';
        if (typeof close === 'function') close();
    });
    wrapper.appendChild(btn);

    return wrapper;
}

// Popup tombol "print": pilih ukuran kertas fisik (A3/A4/A5/dst) lalu cetak.
// Konten editor dicetak ulang dengan @page { size } sesuai pilihan, terlepas
// dari ukuran kertas yang aktif di editor.
function buildPrintPopup(editor, close) {
    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'padding:12px; display:flex; flex-direction:column; gap:8px; min-width:200px; background:#fff;';

    const title = document.createElement('div');
    title.textContent = 'Cetak dengan Ukuran';
    title.style.cssText = 'font-weight:600; margin-bottom:4px; color:#1a1a1a;';
    wrapper.appendChild(title);

    const select = document.createElement('select');
    select.style.cssText = 'width:100%; padding:6px 8px; border:1px solid #ccc; border-radius:4px;';
    const currentKey = findPaperKey(editor.currentPaperSize || PAPER_SIZES['A4']);
    for (const key of Object.keys(PAPER_SIZES)) {
        const opt = document.createElement('option');
        opt.value = key;
        opt.textContent = key;
        if (key === currentKey) opt.selected = true;
        select.appendChild(opt);
    }
    wrapper.appendChild(select);

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Cetak';
    btn.style.cssText = 'margin-top:6px; padding:6px 10px; cursor:pointer; border:1px solid #ccc; border-radius:4px; background:#f3f4f6;';
    btn.addEventListener('click', () => {
        const size = PAPER_SIZES[select.value] || PAPER_SIZES['A4'];
        if (typeof close === 'function') close();
        doPrint(editor, size);
    });
    wrapper.appendChild(btn);

    return wrapper;
}

// FIX AKAR MASALAH "1 halaman kosong di export saat margin besar (mis. 20cm)":
// iframe print dibuat BARU setiap kali cetak, jadi Google Fonts (@import di
// iframeStyle) harus di-fetch ulang dari jaringan — BEDA dari iframe editor
// yang fontnya sudah lama termuat & metriknya sudah "settle". Kode print
// sebelumnya HANYA menunggu <img> selesai load sebelum memanggil print(),
// TIDAK PERNAH menunggu font. Kalau print() sempat terpanggil sebelum font
// selesai load, browser sempat render teks pakai font fallback (metrik
// lebar/tinggi beda dari font asli) — sementara batas halaman (page-break)
// yang disisipkan getCleanValue({forPrint:true}) dihitung repaginateEditor
// pakai tinggi konten versi font ASLI (karena di editor fontnya sudah lama
// termuat). Mismatch tinggi ini bikin sisa konten di akhir halaman meleber
// ke halaman berikutnya, nongol sebagai halaman nyaris kosong.
//
// Kenapa baru kelihatan jelas di margin besar: makin besar margin, makin
// sempit ruang tulis per halaman (contentPerPage) — jadi pergeseran tinggi
// sekecil apa pun akibat font fallback jauh lebih gampang mendorong konten
// lewat batas halaman. Di margin kecil, ruang lebih (dari sisa halaman)
// biasanya cukup menyerap pergeseran itu tanpa kelihatan.
//
// Fix: tunggu document.fonts.ready DI IFRAME PRINT (bukan iframe editor)
// SEJAJAR dengan penantian gambar, sebelum memanggil print() — supaya font
// asli benar-benar sudah settle & tinggi konten yang di-print identik
// dengan yang dipakai repaginateEditor saat menghitung titik potong halaman.
function waitForFontsAndImages(win, callback) {
    let done = false;
    const finish = () => {
        if (done) return;
        done = true;
        callback();
    };

    const imgs = Array.from(win.document.querySelectorAll('img'));
    let imagesReady = imgs.length === 0;
    let remainingImgs = imgs.length;

    // Font Loading API — document.fonts.ready resolve begitu semua font
    // yang dideklarasikan via @import/@font-face SELESAI dimuat & di-parse.
    // Fallback ke Promise.resolve() kalau browser tidak dukung (aman, jadi
    // tidak pernah nge-block print selamanya di browser lama).
    const fontsReady = win.document.fonts && win.document.fonts.ready
        ? win.document.fonts.ready
        : Promise.resolve();
    let fontsSettled = false;

    const checkAndFinish = () => {
        if (imagesReady && fontsSettled) finish();
    };

    fontsReady.then(() => { fontsSettled = true; checkAndFinish(); })
        .catch(() => { fontsSettled = true; checkAndFinish(); }); // jangan sampai error font nge-block print selamanya

    if (imagesReady) {
        checkAndFinish();
    } else {
        imgs.forEach((img) => {
            if (img.complete && img.naturalWidth > 0) {
                remainingImgs--;
                if (remainingImgs === 0) { imagesReady = true; checkAndFinish(); }
                return;
            }
            img.addEventListener('load', () => {
                remainingImgs--;
                if (remainingImgs === 0) { imagesReady = true; checkAndFinish(); }
            });
            img.addEventListener('error', () => {
                remainingImgs--;
                if (remainingImgs === 0) { imagesReady = true; checkAndFinish(); }
            });
        });
    }

    // Jaring pengaman: font di jaringan lambat/gagal → tetap print setelah
    // 4 detik (dinaikkan dari 3 detik semula, kasih sedikit ruang lebih
    // untuk font-fetch yang sebelumnya sama sekali tidak ditunggu).
    setTimeout(finish, 4000);
}

// Inti logika cetak: bangun iframe, isi konten bersih (forced page-break
// sesuai repaginateEditor), set @page { size } + margin sesuai argumen,
// tunggu FONT & gambar load, lalu panggil print.
function doPrint(jodit, size) {
    const iframe = jodit.create.element('iframe');
    Object.assign(iframe.style, {
        position: 'fixed',
        right: 0,
        bottom: 0,
        width: 0,
        height: 0,
        border: 0,
    });
    jodit.container.appendChild(iframe);

    const afterFinishPrint = () => {
        jodit.e.off(jodit.ow, 'mousemove', afterFinishPrint);
        iframe.remove();
    };

    const myWindow = iframe.contentWindow;
    if (!myWindow) return;

    jodit.e
        .on(myWindow, 'onbeforeunload onafterprint', afterFinishPrint)
        .on(jodit.ow, 'mousemove', afterFinishPrint);

    // Bangun struktur iframe sama seperti bawaan (pakai iframeStyle
    // yang sudah diset, biar paper look & font konsisten).
    jodit.e.fire('generateDocumentStructure.iframe', myWindow.document, jodit);
    // getCleanValue({ forPrint: true }): WAJIB — ini yang memaksa
    // titik potong halaman saat export identik dengan yang sudah
    // dihitung repaginateEditor (forced page-break persis di
    // posisi spacer), bukan dihitung ulang oleh reflow browser.
    myWindow.document.body.innerHTML = getCleanValue(jodit, { forPrint: true });

    // FIX: margin di sini SUDAH pasti hasil clampMarginToPage() karena
    // jodit.currentMargin hanya pernah diset lewat applyPaperSize().
    const margin = jodit.currentMargin || DEFAULT_MARGIN;
    // @page { size } & { margin } sama-sama tidak reliable pakai
    // unit "px" di semua browser — spec-nya buat unit fisik
    // (in/cm/mm). Convert ke inch (96px = 1in) biar pasti dikenali.
    const widthIn = (size.width / 96).toFixed(4);
    const heightIn = (size.height / 96).toFixed(4);
    const mTopIn = (margin.top / 96).toFixed(4);
    const mRightIn = (margin.right / 96).toFixed(4);
    const mBottomIn = (margin.bottom / 96).toFixed(4);
    const mLeftIn = (margin.left / 96).toFixed(4);
    const style = myWindow.document.createElement('style');
    style.innerHTML = `
        @page {
            size: ${widthIn}in ${heightIn}in;
            /* Margin WAJIB lewat @page, bukan padding body —
               @page margin otomatis diulang di SETIAP halaman
               fisik saat browser memotong konten yang lebih
               panjang dari satu halaman. Kalau margin cuma
               ditaruh sebagai padding body (cara lama), padding
               itu cuma muncul sekali di awal & akhir keseluruhan
               konten — halaman 2, 3, dst di tengah dokumen jadi
               tidak dapat margin atas/bawah sama sekali. */
            margin: ${mTopIn}in ${mRightIn}in ${mBottomIn}in ${mLeftIn}in;
        }
        @media print {
            html {
                background: #fff !important;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                box-shadow: none !important;
                /* Body TIDAK boleh lagi punya padding/width sendiri
                   saat print — area konten sudah otomatis dikurangi
                   margin oleh @page di atas. Kalau body masih pakai
                   padding manual, margin jadi dobel (padding body +
                   @page margin) dan ukurannya meleset lagi. */
                width: 100% !important;
                min-height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            /* Titik potong halaman dipaksa persis di posisi yang
               sudah dihitung repaginateEditor (lihat
               getCleanValue forPrint), bukan diserahkan ke
               reflow otomatis browser. */
            [data-page-break] {
                break-after: page;
                page-break-after: always;
            }
        }
    `;
    myWindow.document.head.appendChild(style);

    // FIX: tunggu FONT (document.fonts.ready) & gambar sama-sama selesai —
    // lihat catatan panjang di waitForFontsAndImages soal kenapa font juga
    // wajib ditunggu (bukan cuma gambar), supaya tinggi konten saat print
    // identik dengan yang dihitung repaginateEditor.
    waitForFontsAndImages(myWindow, () => {
        myWindow.focus();
        myWindow.print();
    });
}

export function initJoditEditor(selector, overrides = {}) {
    const ta = document.querySelector(selector);
    if (!ta) return null;

    const uploadUrl = ta.dataset.uploadUrl;
    const csrfToken = ta.dataset.csrfToken;

    const editor = Jodit.make(ta, {
        // Default ukuran kertas A4. applyPaperSize dipanggil setelah init
        // supaya tinggi container ikut kertas (lihat bagian bawah fungsi).
        //
        // PENTING: height WAJIB 'auto', BUKAN angka. Kalau angka (mis.
        // PAPER_SIZES['A4'].height + 160), plugin size Jodit mematok tinggi
        // container ke nilai FIX itu (css(container, 'height', ...)) dan
        // menimpa minHeight yang diset applyPaperSize. Akibatnya container
        // tidak pernah tumbuh melebihi satu halaman → konten halaman 2, 3,
        // dst (termasuk garis pembatas antar halaman) ke-clip di luar area
        // yang kelihatan (parent punya overflow:hidden, iframe overflow:clip).
        // Dengan 'auto', container bebas tumbuh mengikuti konten, dan
        // applyPaperSize cukup mengatur minHeight (batas bawah) biar editor
        // tidak kepetit saat dokumen masih pendek.
        height: 'auto',
        width: '100%',
        language: 'id',
        toolbarButtonSize: 'middle',
        toolbarAdaptive: false,   // jangan sembunyikan tombol ke menu "…" — semua tombol selalu tampil
        toolbarSticky: false,

        // Konten tampil seperti halaman kertas terpisah.
        // Pakai iframeStyle (bukan style) supaya paper look ikut diterapkan
        // ke dokumen iframe editor DAN dialog preview bawaan Jodit
        // (previewBox memanggil generateDocumentStructure.iframe, jadi
        // iframeStyle ter-inject di sana juga — preview dan editor selalu konsisten).
        //
        // PENTING: @import WAJIB jadi baris PALING ATAS di dalam iframeStyle,
        // sama seperti aturan @import di file CSS biasa — kalau tidak,
        // browser akan mengabaikannya diam-diam dan font tidak akan pernah
        // muncul di dalam iframe editor maupun di dialog preview.
        iframeStyle: buildIframeStyle(PAPER_SIZES['A4']),

        link: {
            followOnDblClick: true,
        },

        iframe: true,                               // isolasi area konten dari CSS Tailwind/daisyUI
        styleValues: { 'color-text': '#1a1a1a' },    // paksa warna teks UI Jodit (termasuk popup Link) jadi gelap
        textIcons: false,                            // WAJIB false → toolbar tampil ICON, bukan teks nama tombol

        buttons: [
            'source', '|',
            'bold', 'italic', 'underline', 'strikethrough', '|',
            'superscript', 'subscript', '|',
            'ul', 'ol', 'indent', 'outdent', '|',
            'font', 'fontsize', 'brush', 'paragraph', 'lineHeight', '|',
            'image', 'video', 'file', 'table', 'link', 'hr', '|',
            'align', '|',
            'paperSize', 'margin', '|',
            'undo', 'redo', 'eraser', 'copyformat', '|',
            'symbol', 'speechRecognize', '|',
            'cut', 'copy', 'paste', 'selectall', 'find', '|',
            'preview', 'print', 'fullsize', 'about',
        ],

        // Tombol image: langsung buka file picker & upload, tanpa tab URL
        controls: {
            // Daftar font custom (Google Fonts) yang muncul di dropdown toolbar "font"
            font: {
                list: Jodit.atom(FONT_LIST),
            },

            // Pilih ukuran kertas (A4/A5/A3/Letter/Legal). Child button exec
            // menerima args=[key, value] → key = nama ukuran.
            paperSize: {
                name: 'paperSize',
                tooltip: 'Ukuran Kertas',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
                list: Jodit.atom(PAPER_SIZES),
                childTemplate: (editor, key) => {
                    const s = PAPER_SIZES[key];
                    return s ? `${key} (${s.width}×${s.height}px)` : key;
                },
                exec: (editor, _, { control }) => {
                    const key = control.args?.[0];
                    const size = PAPER_SIZES[key];
                    if (size) applyPaperSize(editor, size);
                },
            },

            // Tombol margin manual: klik → muncul popup kecil isi 4 angka
            // (atas/kanan/bawah/kiri dalam px) → "Terapkan" langsung update
            // padding body di editor DAN iframeStyle yang dipakai print/export,
            // karena keduanya baca dari editor.currentMargin yang sama.
            margin: {
                name: 'margin',
                tooltip: 'Margin Halaman',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="1"/><rect x="7" y="7" width="10" height="10" stroke-dasharray="2 2"/></svg>',
                popup: (editor, _current, _self, close) => buildMarginPopup(editor, close),
            },

            image: {
                name: 'image',
                tooltip: 'Sisipkan Gambar',
                icon: 'image',
                exec: (jodit) => {
                    if (!uploadUrl) {
                        alert('Upload URL belum diset (data-upload-url kosong).');
                        return;
                    }

                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/jpeg,image/png,image/gif,image/webp';

                    input.onchange = async () => {
                        const file = input.files?.[0];
                        if (!file) return;

                        const formData = new FormData();
                        formData.append('files[]', file);

                        try {
                            const res = await fetch(uploadUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: formData,
                            });

                            const raw = await res.text();
                            let json;
                            try {
                                json = JSON.parse(raw);
                            } catch {
                                console.error('Response bukan JSON valid:', raw);
                                alert('Server tidak mengembalikan JSON.');
                                return;
                            }

                            const files = json?.data?.files;
                            if (Array.isArray(files) && files.length > 0) {
                                files.forEach((url) => jodit.s.insertImage(url, null, 400));
                            } else {
                                console.error('Format response tidak sesuai:', json);
                                alert('Upload gagal: ' + (json?.data?.msg || JSON.stringify(json)));
                            }
                        } catch (err) {
                            console.error('Fetch error:', err);
                            alert('Request upload gagal (network/CORS/419).');
                        }
                    };

                    input.click();
                },
            },

            file: {
                name: 'file',
                tooltip: 'Sisipkan File',
                icon: 'file',
                exec: (jodit) => {
                    if (!uploadUrl) {
                        alert('Upload URL belum diset.');
                        return;
                    }

                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar';

                    input.onchange = async () => {
                        const file = input.files?.[0];
                        if (!file) return;

                        const formData = new FormData();
                        formData.append('files[]', file);

                        try {
                            const res = await fetch(uploadUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: formData,
                            });

                            const raw = await res.text();
                            let json;
                            try { json = JSON.parse(raw); } catch {
                                console.error('Response bukan JSON valid:', raw);
                                alert('Server tidak mengembalikan JSON.');
                                return;
                            }

                            const files = json?.data?.files;
                            if (Array.isArray(files) && files.length > 0) {
                                files.forEach((url) => {
                                    const fileName = file.name;
                                    jodit.s.insertHTML(`<a href="${url}" target="_blank" rel="noopener noreferrer">${fileName}</a>`);
                                });
                            } else {
                                alert('Upload gagal: ' + (json?.data?.msg || JSON.stringify(json)));
                            }
                        } catch (err) {
                            console.error('Fetch error:', err);
                            alert('Request upload gagal.');
                        }
                    };
                    input.click();
                },
            },

            // Override tombol "print" bawaan Jodit. Bawaan memanggil
            // myWindow.print() langsung setelah body.innerHTML diisi, sebelum
            // gambar selesai dimuat → dialog print muncul dengan gambar kosong.
            // Di sini kita tunggu semua <img> selesai load dulu baru print.
            //
            // Fix tambahan (garis abu-abu, teks lompat halaman, & pagination
            // yang meleset dari editor):
            // 1. box-shadow dimatikan total saat print — sebelumnya cuma
            //    background-image (garis pemisah halaman di editor) yang
            //    dimatikan, box-shadow-nya lupa, jadi ke-print sebagai
            //    "border" gelap di tiap titik potong halaman.
            // 2. @page dipaksa pakai ukuran PERSIS SAMA dengan
            //    jodit.currentPaperSize (sumber kebenaran yang sama dipakai
            //    buildIframeStyle & repaginateEditor untuk simulasi halaman
            //    di editor). Sebelumnya ukuran kertas fisik saat print
            //    ditentukan oleh default browser (beda dari size.height +
            //    ada margin header/footer bawaan Chrome), sehingga titik
            //    potong halaman visual (di editor) dan titik potong fisik
            //    (saat export) adalah dua sumber kebenaran independen yang
            //    bisa meleset satu sama lain — itu sebabnya teks yang di
            //    editor kelihatan "masih di halaman 1" bisa kepental ke
            //    halaman 2 saat export. Dengan @page { size } dipaksa sama
            //    persis, keduanya dijamin sinkron.
            // 3. FIX BARU: getCleanValue dipanggil dengan { forPrint: true }
            //    supaya spacer pagination editor diubah jadi forced
            //    page-break asli (break-after:page) di posisi yang SAMA
            //    PERSIS, bukan dibuang lalu diserahkan ke reflow otomatis
            //    browser. Sebelumnya ini adalah sumber utama kenapa elemen
            //    yang di editor sudah "pindah ke halaman 2" bisa balik ke
            //    halaman 1 saat hasil export — browser motong halamannya
            //    sendiri, independen dari keputusan repaginateEditor.
            print: {
                name: 'print',
                tooltip: 'Print',
                exec: (jodit) => {
                    const iframe = jodit.create.element('iframe');
                    Object.assign(iframe.style, {
                        position: 'fixed',
                        right: 0,
                        bottom: 0,
                        width: 0,
                        height: 0,
                        border: 0,
                    });
                    jodit.container.appendChild(iframe);

                    const afterFinishPrint = () => {
                        jodit.e.off(jodit.ow, 'mousemove', afterFinishPrint);
                        iframe.remove();
                    };

                    const myWindow = iframe.contentWindow;
                    if (!myWindow) return;

                    jodit.e
                        .on(myWindow, 'onbeforeunload onafterprint', afterFinishPrint)
                        .on(jodit.ow, 'mousemove', afterFinishPrint);

                    // Bangun struktur iframe sama seperti bawaan (pakai iframeStyle
                    // yang sudah diset, biar paper look & font konsisten).
                    jodit.e.fire('generateDocumentStructure.iframe', myWindow.document, jodit);
                    // getCleanValue({ forPrint: true }): WAJIB — ini yang memaksa
                    // titik potong halaman saat export identik dengan yang sudah
                    // dihitung repaginateEditor (forced page-break persis di
                    // posisi spacer), bukan dihitung ulang oleh reflow browser.
                    myWindow.document.body.innerHTML = getCleanValue(jodit, { forPrint: true });

                    const size = jodit.currentPaperSize || PAPER_SIZES['A4'];
                    // FIX: margin di sini SUDAH pasti hasil clampMarginToPage()
                    // karena jodit.currentMargin hanya pernah diset lewat
                    // applyPaperSize().
                    const margin = jodit.currentMargin || DEFAULT_MARGIN;
                    // @page { size } & { margin } sama-sama tidak reliable pakai
                    // unit "px" di semua browser — spec-nya buat unit fisik
                    // (in/cm/mm). Convert ke inch (96px = 1in) biar pasti dikenali.
                    const widthIn = (size.width / 96).toFixed(4);
                    const heightIn = (size.height / 96).toFixed(4);
                    const mTopIn = (margin.top / 96).toFixed(4);
                    const mRightIn = (margin.right / 96).toFixed(4);
                    const mBottomIn = (margin.bottom / 96).toFixed(4);
                    const mLeftIn = (margin.left / 96).toFixed(4);
                    const style = myWindow.document.createElement('style');
                    style.innerHTML = `
                        @page {
                            size: ${widthIn}in ${heightIn}in;
                            /* Margin WAJIB lewat @page, bukan padding body —
                               @page margin otomatis diulang di SETIAP halaman
                               fisik saat browser memotong konten yang lebih
                               panjang dari satu halaman. Kalau margin cuma
                               ditaruh sebagai padding body (cara lama), padding
                               itu cuma muncul sekali di awal & akhir keseluruhan
                               konten — halaman 2, 3, dst di tengah dokumen jadi
                               tidak dapat margin atas/bawah sama sekali. */
                            margin: ${mTopIn}in ${mRightIn}in ${mBottomIn}in ${mLeftIn}in;
                        }
                        @media print {
                            html {
                                background: #fff !important;
                            }
                            body {
                                -webkit-print-color-adjust: exact;
                                print-color-adjust: exact;
                                box-shadow: none !important;
                                /* Body TIDAK boleh lagi punya padding/width sendiri
                                   saat print — area konten sudah otomatis dikurangi
                                   margin oleh @page di atas. Kalau body masih pakai
                                   padding manual, margin jadi dobel (padding body +
                                   @page margin) dan ukurannya meleset lagi. */
                                width: 100% !important;
                                min-height: 0 !important;
                                padding: 0 !important;
                                margin: 0 !important;
                            }
                            /* Titik potong halaman dipaksa persis di posisi yang
                               sudah dihitung repaginateEditor (lihat
                               getCleanValue forPrint), bukan diserahkan ke
                               reflow otomatis browser. */
                            [data-page-break] {
                                break-after: page;
                                page-break-after: always;
                            }
                        }
                    `;
                    myWindow.document.head.appendChild(style);

                    // FIX AKAR MASALAH "1 halaman kosong saat export dengan
                    // margin besar": sebelumnya di sini HANYA menunggu <img>
                    // selesai load sebelum print() dipanggil — TIDAK PERNAH
                    // menunggu Google Fonts (@import di iframeStyle) selesai
                    // dimuat di iframe print yang baru dibuat ini. Kalau
                    // print() sempat jalan sebelum font settle, teks sempat
                    // ke-render pakai font fallback (metrik beda dari font
                    // asli yang dipakai repaginateEditor saat menghitung
                    // titik potong halaman) → tinggi konten meleset dari
                    // perhitungan → sisa konten di akhir dorong ke halaman
                    // baru yang nyaris kosong. Efeknya paling kelihatan pas
                    // margin besar karena ruang tulis per halaman
                    // (contentPerPage) jadi sempit, jadi pergeseran tinggi
                    // sekecil apa pun sudah cukup mendorong konten meleber.
                    // waitForFontsAndImages menunggu document.fonts.ready
                    // SEJAJAR dengan gambar sebelum benar-benar print().
                    waitForFontsAndImages(myWindow, () => {
                        myWindow.focus();
                        myWindow.print();
                    });
                },
            },
        },


        uploader: uploadUrl ? {
            url: uploadUrl,
            format: 'json',
            filesVariableName: () => 'files[]',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            imagesAccept: 'image/jpg,image/png,image/jpeg,image/gif,image/webp',
            isSuccess: (resp) => !resp?.data?.error,
            process: (resp) => ({
                files: resp?.data?.files || [],
                path: resp?.data?.path || '',
                baseurl: resp?.data?.baseurl || '',
                error: resp?.data?.error ?? 0,
                msg: resp?.data?.msg || '',
            }),
            defaultHandlerSuccess(data) {
                (data.files || []).forEach((url) => this.selection.insertImage(url, null, 250));
            },
            error: (e) => console.error('Upload gagal:', e),
        } : undefined,

        ...overrides,
    });

    const storageKey = ta.dataset.liveStorage;

    const form = ta.closest('form');
    let draftSaved = false;
    if (form) form.addEventListener('submit', () => {
        // getCleanValue: WAJIB — tanpa ini elemen jeda pagination ikut
        // tersimpan ke database sebagai bagian dari konten dokumen. Ini
        // untuk disimpan ke DB, jadi TIDAK pakai forPrint — spacer dibuang
        // polos, bukan diganti page-break (DB harus menyimpan HTML "flat").
        ta.value = getCleanValue(editor);

        // FIX AKAR MASALAH "preview bermargin besar tapi hasil save seolah
        // belum dikasih margin": sebelumnya applyPaperSize() HANYA menulis
        // ukuran kertas & margin aktif ke localStorage (buat sinkron preview
        // di tab lain) — form submit ini TIDAK PERNAH mengirim paper_size/
        // paper_margin ke server, jadi kolomnya di DB tidak pernah ter-update.
        // Preview lalu memprioritaskan localStorage di atas data DB (lihat
        // initPreviewPagination), sehingga margin besar yang sempat dicoba di
        // editor "nyangkut" tampil di preview padahal tidak pernah tersimpan.
        // Sekarang nilai currentMargin/currentPaperSize yang aktif di editor
        // ikut disisipkan ke hidden input sebelum submit, supaya benar-benar
        // sampai ke server & tersimpan di kolom dokumen.
        const sizeInput = form.querySelector('[name="paper_size"]');
        const marginInput = form.querySelector('[name="paper_margin"]');
        if (sizeInput) {
            sizeInput.value = findPaperKey(editor.currentPaperSize || PAPER_SIZES['A4']) || 'A4';
        }
        if (marginInput) {
            marginInput.value = JSON.stringify(editor.currentMargin || DEFAULT_MARGIN);
        }

        // Draft sudah di-save → bersihkan, biar preview/show konsisten dari DB
        if (storageKey) {
            localStorage.removeItem(storageKey);
            // FIX: hapus juga key ':paper' — kalau dibiarkan, preview akan
            // TERUS membaca margin lama dari localStorage (yang tadi belum
            // tentu tersimpan ke DB) alih-alih nilai baru yang baru saja
            // disimpan server, karena initPreviewPagination memprioritaskan
            // localStorage di atas data DB.
            localStorage.removeItem(storageKey + ':paper');
            draftSaved = true;
        }
    });

    // Live preview sync: mirror content into localStorage for the preview page (other tab)
    if (storageKey) {
        // Restore unsaved draft from localStorage if it has real content
        const draft = localStorage.getItem(storageKey);
        if (draft && draft.trim().length) {
            const probe = document.createElement('div');
            probe.innerHTML = draft;
            const hasContent = probe.textContent.trim().length > 0 || probe.querySelector('img, table, iframe');
            if (hasContent) {
                editor.value = draft;
                ta.value = draft;
            }
        }

        let timer = null;
        editor.events.on('change', () => {
            if (draftSaved) return; // sudah di-save, jangan nulis draft kosong lagi
            clearTimeout(timer);
            // getCleanValue: WAJIB — draft di localStorage juga tidak boleh
            // ikut bawa elemen jeda pagination.
            timer = setTimeout(() => localStorage.setItem(storageKey, getCleanValue(editor)), 250);
        });
    }

    // Daftarkan instance agar bisa diakses dari luar (modal, dll)
    if (window.__joditInstances) {
        window.__joditInstances.set(ta.id, editor);
    }

    // Sinkronkan tinggi container dengan ukuran kertas default (A4).
    // Dipanggil di sini DAN pada afterInit karena plugin size Jodit
    // menimpa min-height body setelah init.
    applyPaperSize(editor, PAPER_SIZES['A4']);
    editor.e.on('afterInit', () => {
        applyPaperSize(editor, PAPER_SIZES['A4']);
        repaginateEditor(editor);
    });

    // JARING PENGAMAN: ternyata dua requestAnimationFrame di applyPaperSize
    // saja tidak selalu cukup — plugin resize/size internal Jodit kadang
    // baru selesai menyesuaikan ukuran container beberapa frame/tick
    // setelahnya (timing persisnya di luar kendali kode kita), sehingga
    // repaginateEditor yang sudah kepalang jalan duluan sempat "ketimpa"
    // ulang oleh reflow itu → garis pembatas halaman kelihatan hilang lagi
    // padahal baru saja disisipkan. Daripada nebak jumlah frame yang pas,
    // ResizeObserver ini AKTIF MENGAWASI ukuran container editor secara
    // langsung: begitu ukurannya beneran berubah (oleh applyPaperSize ATAU
    // oleh reflow internal Jodit sendiri, kapan pun itu terjadi), page-break
    // otomatis dihitung ulang lewat scheduleRepaginate (throttle rAF, jadi
    // tidak akan spam meski ResizeObserver terpicu berkali-kali beruntun).
    if (typeof ResizeObserver !== 'undefined') {
        const containerResizeObserver = new ResizeObserver(() => scheduleRepaginate(editor));
        containerResizeObserver.observe(editor.container);
        editor.e.on('beforeDestruct', () => containerResizeObserver.disconnect());
    }

    // JARING PENGAMAN KEDUA: Jodit kadang re-render isi <body> dari cache
    // internalnya (mis. saat ganti margin/ukuran kertas, plugin size
    // menulis ulang body dari nilai yang disimpan SEBELUM spacer disisipkan;
    // atau saat user PASTE teks — paste bisa memicu normalisasi DOM internal
    // Jodit yang menulis ulang isi body tanpa lewat childList langsung di
    // level body). MutationObserver ini mengawasi body secara langsung untuk
    // menangkap kejadian itu, lalu menjadwalkan ulang pagination otomatis.
    //
    // FIX (2 celah lama):
    // 1. `subtree: false` → sebelumnya observer BUTA terhadap perubahan yang
    //    terjadi DI DALAM elemen anak body (mis. paste teks dengan kursor di
    //    tengah paragraf yang sudah ada — body tidak kehilangan/menambah
    //    child langsung, cuma isi salah satu childnya yang berubah). Ini
    //    persis skenario "copas teks gak otomatis ada page break": paste-nya
    //    tidak terdeteksi sama sekali sampai event lain (mis. resize)
    //    kebetulan memicu repagination. Sekarang `subtree: true` +
    //    `characterData: true` supaya perubahan teks/anak-cucu di mana pun
    //    di dalam body ikut terdeteksi.
    // 2. Kondisi lama `!querySelector('[data-page-spacer]')` hanya terpicu
    //    kalau SEMUA spacer hilang sekaligus. Pada dokumen multi-halaman,
    //    reflow internal Jodit sering cuma menghapus SEBAGIAN spacer (bukan
    //    semua) — kondisi itu gagal terpicu, dan garis pembatas halaman
    //    lain yang hilang tidak pernah dihitung ulang. Ini match dengan
    //    gejala "ganti ukuran kertas → garis pembatas ikut hilang" karena
    //    plugin size Jodit menulis ulang body dari cache lama. Sekarang
    //    repagination dijadwalkan untuk SETIAP mutasi (aman: repaginateEditor
    //    sendiri sudah idempoten, di-guard _isRepaginating, dan
    //    scheduleRepaginate sudah di-throttle via rAF — jadi dipanggil lebih
    //    sering tidak menyebabkan spam maupun infinite loop).
    if (typeof MutationObserver !== 'undefined') {
        const spacerObserver = new MutationObserver(() => {
            if (editor._suppressSpacerObserver) return;
            scheduleRepaginate(editor);
        });
        spacerObserver.observe(editor.editor, {
            childList: true,
            subtree: true,
            characterData: true,
        });
        editor.e.on('beforeDestruct', () => spacerObserver.disconnect());
    }

    // Hitung ulang jeda antar halaman setiap user selesai mengetik/paste
    // (throttle rAF, lihat scheduleRepaginate). Guard `_isRepaginating` di
    // dalam repaginateEditor mencegah mutasi spacer ini memicu 'change' lagi
    // secara berulang tanpa henti.
    editor.events.on('change', () => scheduleRepaginate(editor));

    return editor;
}

// Registry instance untuk akses dari modal/script lain (jangan timpa window.Jodit!)
window.__joditInstances = window.__joditInstances || new Map();

// Ekspos ke window supaya preview.blade.php (yang render ulang konten live
// dari localStorage) bisa memanggil pagination ulang pada scope baru.
window.__initPreviewPagination = initPreviewPagination;
// Ekspos daftar ukuran kertas supaya halaman edit bisa mencocokkan nama
// ukuran (A4/A5/...) dari objek size yang aktif di editor.
window.__paperSizes = PAPER_SIZES;
// Ekspos findPaperKey supaya halaman edit (edit.blade.php) bisa mencocokkan
// nama ukuran (A4/A5/...) dari objek size aktif tanpa duplikasi logic saat
// mengisi hidden input paper_size sebelum submit.
window.__findPaperKey = findPaperKey;

// Ekspor untuk dipakai halaman preview (show / preview-version / preview).
export { initPreviewPagination, repaginatePreview, readStoredPaper };