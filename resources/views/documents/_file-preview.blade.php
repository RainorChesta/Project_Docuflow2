@php
    $isPdf = str_contains($version->file_mime ?? '', 'pdf');
    $fileUrl = route('documents.file', [$document, $version]);
@endphp

<div class="w-full">
    @if($isPdf)
        <iframe src="{{ $fileUrl }}" class="w-full border-0 h-[72vh] sm:h-[80vh] lg:h-[1123px] min-h-[520px] sm:min-h-[650px] lg:min-h-[1123px] block" title="{{ __('Pratinjau dokumen') }}"></iframe>
    @else
        @if(isset($onlyOfficeConfig))
            <div class="w-full bg-base-100 h-[72vh] sm:h-[80vh] lg:h-[1150px] min-h-[520px] sm:min-h-[650px] lg:min-h-[1123px]">
                <div id="docx-preview-{{ $version->id }}" class="w-full h-full"></div>
            </div>

            <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof DocsAPI === 'undefined') {
                        document.getElementById('docx-preview-{{ $version->id }}').innerHTML =
                            '<p class="text-error text-sm p-4">' + @json(__('Gagal memuat editor ONLYOFFICE.')) + '</p>';
                        return;
                    }
                    try {
                        const config = @json($onlyOfficeConfig);
                        const isMobileOrTablet = window.innerWidth < 1024;

                        // Always use desktop type to bypass ONLYOFFICE Community Edition mobile license restriction
                        config.type = 'desktop';
                        config.editorConfig = config.editorConfig || {};
                        config.editorConfig.customization = config.editorConfig.customization || {};
                        config.editorConfig.customization.compactHeader = true;
                        config.editorConfig.customization.autoFocus = false;
                        config.editorConfig.customization.mobile = { force: false };

                        if (isMobileOrTablet) {
                            // Responsive mode for small/shrinking viewports:
                            config.editorConfig.customization.compactToolbar = true;
                            config.editorConfig.customization.leftMenu = false;
                            config.editorConfig.customization.rightMenu = false;
                            config.editorConfig.customization.ruler = false;
                            config.editorConfig.customization.toolbarHideFileName = true;
                            config.editorConfig.customization.zoom = -2; // Fit to Width
                        } else {
                            // Desktop screen - preserve standard layout intact
                            config.editorConfig.customization.compactToolbar = false;
                            config.editorConfig.customization.leftMenu = true;
                            config.editorConfig.customization.rightMenu = true;
                            config.editorConfig.customization.ruler = true;
                            config.editorConfig.customization.toolbarHideFileName = false;
                            config.editorConfig.customization.zoom = 100;
                        }

                        const mainEl = document.querySelector('main') || document.documentElement;
                        const initialScrollTop = mainEl.scrollTop || 0;
                        let guardActive = true;

                        // Allow normal user scrolling immediately upon any user interaction
                        const releaseGuard = function() {
                            guardActive = false;
                        };
                        window.addEventListener('wheel', releaseGuard, { passive: true, capture: true });
                        window.addEventListener('touchmove', releaseGuard, { passive: true, capture: true });
                        window.addEventListener('pointerdown', releaseGuard, { passive: true, capture: true });
                        window.addEventListener('mousedown', releaseGuard, { passive: true, capture: true });
                        window.addEventListener('keydown', releaseGuard, { passive: true, capture: true });

                        function restoreScrollIfAutofocused() {
                            if (guardActive && mainEl && mainEl.scrollTop !== initialScrollTop) {
                                mainEl.scrollTop = initialScrollTop;
                            }
                        }

                        // Release guard after 1.5s max so it never blocks scrolling
                        setTimeout(releaseGuard, 1500);

                        config.events = config.events || {};
                        const origOnAppReady = config.events.onAppReady;
                        config.events.onAppReady = function() {
                            if (typeof origOnAppReady === 'function') origOnAppReady();
                            restoreScrollIfAutofocused();

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
                        
                        const origOnDocumentReady = config.events.onDocumentReady;
                        config.events.onDocumentReady = function() {
                            restoreScrollIfAutofocused();
                            setTimeout(restoreScrollIfAutofocused, 50);
                            setTimeout(releaseGuard, 300);

                            if (typeof origOnDocumentReady === 'function') origOnDocumentReady();
                        };

                        window.docEditorPreview = new DocsAPI.DocEditor("docx-preview-{{ $version->id }}", config);
                    } catch (e) {
                        console.error('ONLYOFFICE initialization error:', e);
                        document.getElementById('docx-preview-{{ $version->id }}').innerHTML =
                            '<p class="text-error text-sm p-4">' + @json(__('Gagal memuat pratinjau. Silakan unduh dokumen untuk melihat isinya.')) + '</p>';
                    }
                });
            </script>
        @else
            <div id="docx-preview-{{ $version->id }}" class="prose max-w-none p-6 sm:p-8 bg-base-100" style="min-height: 1123px;">
                <p class="text-base-content/50 text-sm">{{ __('Memuat isi dokumen...') }}</p>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/mammoth@1.7.0/mammoth.browser.min.js"></script>
            <script>
                (function () {
                    fetch('{{ $fileUrl }}')
                        .then(function (res) { return res.arrayBuffer(); })
                        .then(function (buffer) { return mammoth.convertToHtml({ arrayBuffer: buffer }); })
                        .then(function (result) {
                            document.getElementById('docx-preview-{{ $version->id }}').innerHTML =
                                result.value || ('<p class="text-base-content/50 text-sm">' + @json(__('Dokumen kosong.')) + '</p>');
                        })
                        .catch(function () {
                            document.getElementById('docx-preview-{{ $version->id }}').innerHTML =
                                '<p class="text-error text-sm">' + @json(__('Gagal memuat pratinjau. Silakan unduh dokumen untuk melihat isinya.')) + '</p>';
                        });
                })();
            </script>
        @endif
    @endif
</div>
