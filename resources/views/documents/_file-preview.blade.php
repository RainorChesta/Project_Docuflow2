@php
    $isPdf = str_contains($version->file_mime ?? '', 'pdf');
    $fileUrl = route('documents.file', [$document, $version]);
@endphp

<div class="p-4">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3 px-2">
        <div class="text-sm text-base-content/60">
            <span class="font-medium text-base-content">{{ $version->file_original_name ?? ($document->title . '.docx') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('documents.download', $document) }}" class="btn btn-primary btn-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                {{ __('Download DOCX') }}
            </a>
        </div>
    </div>

    @if($isPdf)
        <iframe src="{{ $fileUrl }}" class="w-full border-0" style="height: 80vh;" title="Pratinjau dokumen"></iframe>
    @else
        @if(isset($onlyOfficeConfig))
            <div class="w-full border border-base-300 rounded-lg overflow-hidden" style="width: 100%; height: 850px; min-height: 80vh;">
                <div id="docx-preview-{{ $version->id }}"></div>
            </div>

            <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof DocsAPI === 'undefined') {
                        document.getElementById('docx-preview-{{ $version->id }}').innerHTML =
                            '<p class="text-error text-sm p-4">Gagal memuat editor ONLYOFFICE.</p>';
                        return;
                    }
                    try {
                        const config = @json($onlyOfficeConfig);
                        const savedY = window.scrollY;
                        config.events = config.events || {};
                        const origOnAppReady = config.events.onAppReady;
                        config.events.onAppReady = function() {
                            if (window.scrollY !== savedY && savedY < 200) {
                                window.scrollTo({ top: savedY, behavior: 'instant' });
                            }
                            if (typeof origOnAppReady === 'function') origOnAppReady();

                            // Execute macro to resolve pending signatures
                            const approvedSignatures = @json($approvedSignatures ?? []);
                            if (approvedSignatures && approvedSignatures.length > 0 && window.docEditorPreview && window.docEditorPreview.createConnector) {
                                const connector = window.docEditorPreview.createConnector();
                                const script = `
                                    var oDocument = Api.GetDocument();
                                    var aContentControls = oDocument.GetAllContentControls();
                                    var approved = ${JSON.stringify(approvedSignatures)};
                                    
                                    for (var i = 0; i < aContentControls.length; i++) {
                                        var label = aContentControls[i].GetLabel();
                                        if (label && label.indexOf("pending_sig_") === 0) {
                                            var reqId = parseInt(label.split("_")[2]);
                                            var match = null;
                                            for (var j = 0; j < approved.length; j++) {
                                                if (approved[j].request_id === reqId) {
                                                    match = approved[j];
                                                    break;
                                                }
                                            }
                                            if (match && match.url) {
                                                aContentControls[i].RemoveAllElements();
                                                var oParagraph = Api.CreateParagraph();
                                                var oImage = Api.CreateImage(match.url, 140 * 36000, 140 * 36000);
                                                oParagraph.AddElement(oImage, 0);
                                                aContentControls[i].AddElement(oParagraph, 0);
                                                aContentControls[i].SetLabel("resolved_sig_" + reqId);
                                            }
                                        }
                                    }
                                `;
                                connector.callCommand(new Function(script), function() {
                                    console.log("Pending signatures replaced automatically in preview.");
                                });
                            }
                        };
                        window.docEditorPreview = new DocsAPI.DocEditor("docx-preview-{{ $version->id }}", config);
                    } catch (e) {
                        console.error('ONLYOFFICE initialization error:', e);
                        document.getElementById('docx-preview-{{ $version->id }}').innerHTML =
                            '<p class="text-error text-sm p-4">Gagal memuat pratinjau. Silakan unduh dokumen untuk melihat isinya.</p>';
                    }
                });
            </script>
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
    @endif
</div>
