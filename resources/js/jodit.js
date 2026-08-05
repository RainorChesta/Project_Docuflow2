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

export function initJoditEditor(selector, overrides = {}) {
    const ta = document.querySelector(selector);
    if (!ta) return null;

    const uploadUrl = ta.dataset.uploadUrl;
    const csrfToken = ta.dataset.csrfToken;

    const editor = Jodit.make(ta, {
        height: 600,
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
        iframeStyle: [
            `@import url('${GOOGLE_FONTS_URL}');`,
            'html { margin:0; padding:0; background:#e5e7eb; }',
            'body {',
            '  box-sizing:border-box;',
            '  width:794px;',
            '  margin:0 auto;',
            '  padding:48px 56px;',
            '  background:#fff;',
            '  min-height:1129px;',
            '  border:2px solid #6b7280;',
            '  border-top:none;',
            '  box-shadow:0 1px 3px rgba(0,0,0,0.1);',
            '  background-image:repeating-linear-gradient(to bottom, transparent 0, transparent 1129px, #d1d5db 1129px, #d1d5db 1135px);',
            '}',
            // Style tabel default Jodit — wajib dipertahankan karena iframeStyle
            // mengganti total bawaan (yang punya border th/td).
            'table { width:100%; border:none; border-collapse:collapse; empty-cells:show; max-width:100%; }',
            'th, td { padding:2px 5px; border:1px solid #ccc; }',
        ].join('\n'),

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
            // === TAMBAHKAN KODE DI BAWAH INI ===
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

            // === TAMBAHKAN KODE DI BAWAH INI ===
            // Override tombol "print" bawaan Jodit. Bawaan memanggil
            // myWindow.print() langsung setelah body.innerHTML diisi, sebelum
            // gambar selesai dimuat → dialog print muncul dengan gambar kosong.
            // Di sini kita tunggu semua <img> selesai load dulu baru print.
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
                    myWindow.document.body.innerHTML = jodit.value;

                    const style = myWindow.document.createElement('style');
                    style.innerHTML = `@media print { body { -webkit-print-color-adjust: exact; } }`;
                    myWindow.document.head.appendChild(style);

                    // Tunggu semua gambar di dalam konten selesai dimuat.
                    const imgs = Array.from(myWindow.document.querySelectorAll('img'));
                    if (imgs.length === 0) {
                        myWindow.focus();
                        myWindow.print();
                        return;
                    }
                    let remaining = imgs.length;
                    let done = false;
                    const finish = () => {
                        if (done) return;
                        done = true;
                        myWindow.focus();
                        myWindow.print();
                    };
                    imgs.forEach((img) => {
                        if (img.complete && img.naturalWidth > 0) {
                            remaining--;
                            if (remaining === 0) finish();
                            return;
                        }
                        img.addEventListener('load', () => {
                            remaining--;
                            if (remaining === 0) finish();
                        });
                        img.addEventListener('error', () => {
                            remaining--;
                            if (remaining === 0) finish();
                        });
                    });
                    // Jaring pengaman: kalau ada gambar yang tak kunjung load,
                    // tetap print setelah 3 detik.
                    setTimeout(finish, 3000);
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
        ta.value = editor.value;
        // Draft sudah di-save → bersihkan, biar preview/show konsisten dari DB
        if (storageKey) {
            localStorage.removeItem(storageKey);
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
            timer = setTimeout(() => localStorage.setItem(storageKey, editor.value), 250);
        });
    }

    // Daftarkan instance agar bisa diakses dari luar (modal, dll)
    if (window.__joditInstances) {
        window.__joditInstances.set(ta.id, editor);
    }

    return editor;
}

// Registry instance untuk akses dari modal/script lain (jangan timpa window.Jodit!)
window.__joditInstances = window.__joditInstances || new Map();