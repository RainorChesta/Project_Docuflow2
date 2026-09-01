<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $template->title }} - Preview</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        #onlyoffice-editor-container {
            width: 100%;
            height: 100%;
        }
        #onlyoffice-fallback {
            display: none;
            position: absolute;
            inset: 0;
            background: #ffffff;
            z-index: 50;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }
        #onlyoffice-fallback.active {
            display: flex;
        }
        .fallback-box {
            max-width: 420px;
            padding: 24px;
            border-radius: 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .fallback-title {
            font-weight: 700;
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .fallback-msg {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .fallback-url {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div id="onlyoffice-editor-container"></div>

    <div id="onlyoffice-fallback">
        <div class="fallback-box">
            <div style="font-size: 36px; margin-bottom: 12px;">⚠️</div>
            <div class="fallback-title">Gagal Menghubungi ONLYOFFICE</div>
            <div class="fallback-msg">
                Tidak dapat terhubung ke server ONLYOFFICE di <span class="fallback-url">{{ config('onlyoffice.url') }}</span>.
                <br>Pastikan container Docker ONLYOFFICE sedang berjalan.
            </div>
        </div>
    </div>

    <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js"
            onerror="document.getElementById('onlyoffice-fallback').classList.add('active');"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof DocsAPI === 'undefined') {
                document.getElementById('onlyoffice-fallback').classList.add('active');
                return;
            }

            try {
                const config = @json($config);
                // Match settings from editor.blade.php
                config.type = 'desktop';
                config.editorConfig = config.editorConfig || {};
                config.editorConfig.mode = 'view';
                config.editorConfig.customization = config.editorConfig.customization || {};
                config.editorConfig.customization.compactHeader = true;
                config.editorConfig.customization.toolbarNoTabs = true;
                config.editorConfig.customization.mobile = { force: false };

                config.events = config.events || {};
                config.events.onError = function(event) {
                    console.error('ONLYOFFICE error event:', event);
                };

                window.docEditor = new DocsAPI.DocEditor("onlyoffice-editor-container", config);
            } catch (e) {
                console.error('ONLYOFFICE initialization error:', e);
                document.getElementById('onlyoffice-fallback').classList.add('active');
            }
        });
    </script>
</body>
</html>
