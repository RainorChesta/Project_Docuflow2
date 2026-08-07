@php
    $isPdf = str_contains($version->file_mime ?? '', 'pdf');
    $fileUrl = route('documents.file', [$document, $version]);
@endphp

<div class="p-4">
    <div class="flex items-center justify-between mb-3 px-2">
        <div class="text-sm text-base-content/60">
            <span class="font-medium text-base-content">{{ $version->file_original_name }}</span>
            — dokumen diunggah, bukan hasil editor.
        </div>
        <a href="{{ $fileUrl }}" class="btn btn-ghost btn-xs">Unduh</a>
    </div>

    @if($isPdf)
        <iframe src="{{ $fileUrl }}" class="w-full border-0" style="height: 80vh;" title="Pratinjau dokumen"></iframe>
    @else
        <div id="docx-preview-{{ $version->id }}" class="prose max-w-none px-4 py-6 border border-base-300 rounded-lg min-h-[200px]">
            <p class="text-base-content/50 text-sm">Memuat isi dokumen…</p>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/mammoth@1.7.0/mammoth.browser.min.js"></script>
        <script>
            (function () {
                fetch('{{ $fileUrl }}')
                    .then(function (res) { return res.arrayBuffer(); })
                    .then(function (buffer) { return mammoth.convertToHtml({ arrayBuffer: buffer }); })
                    .then(function (result) {
                        document.getElementById('docx-preview-{{ $version->id }}').innerHTML =
                            result.value || '<p class="text-base-content/50 text-sm">Dokumen kosong.</p>';
                    })
                    .catch(function () {
                        document.getElementById('docx-preview-{{ $version->id }}').innerHTML =
                            '<p class="text-error text-sm">Gagal memuat pratinjau. Silakan unduh dokumen untuk melihat isinya.</p>';
                    });
            })();
        </script>
    @endif
</div>
