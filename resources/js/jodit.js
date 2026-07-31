import { Jodit } from 'jodit';
import 'jodit/es2021/jodit.min.css';
import 'jodit/esm/plugins/all.js';

export function initJoditEditor(selector, overrides = {}) {
    const ta = document.querySelector(selector);
    if (!ta) return null;

    const uploadUrl = ta.dataset.uploadUrl;
    const csrfToken = ta.dataset.csrfToken;

    const editor = Jodit.make(ta, {
        height: 600,
        language: 'id',
        toolbarButtonSize: 'middle',
        toolbarAdaptive: true,
        toolbarSticky: true,
        toolbarStickyOffset: 64,

        buttons: [
            'undo', 'redo', '|',
            'bold', 'italic', 'underline', 'strikethrough', '|',
            'font', 'fontsize', 'brush', 'paragraph', '|',
            'ul', 'ol', 'outdent', 'indent', '|',
            'align', '|',
            'image', 'link', 'table', '|',
            'hr', 'eraser', '|',
            'source', 'fullsize',
        ],

        // Ganti total popup default (URL + Upload + Browser) jadi: klik -> pilih file -> upload -> insert.
        controls: {
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
                            } catch (parseErr) {
                                console.error('Response bukan JSON valid:', raw);
                                alert('Server tidak mengembalikan JSON. Cek console untuk detail response mentah.');
                                return;
                            }

                            console.log('Upload response:', json);

                            const files = json?.data?.files;
                            if (Array.isArray(files) && files.length > 0) {
                                files.forEach((url) => jodit.s.insertImage(url, null, 400));
                            } else {
                                console.error('Format response tidak sesuai:', json);
                                alert('Upload gagal atau format response salah: ' + (json?.data?.msg || JSON.stringify(json)));
                            }
                        } catch (err) {
                            console.error('Fetch error:', err);
                            alert('Request upload gagal (network/CORS/419). Cek console + tab Network.');
                        }
                    };

                    input.click();
                },
            },
        },

        ...overrides,
    });

    const form = ta.closest('form');
    if (form) form.addEventListener('submit', () => { ta.value = editor.value; });

    return editor;
}