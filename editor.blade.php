<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Editor Dokumen - Dokuflow</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        #editor-container { width: 100%; height: 100vh; }
    </style>
</head>
<body>

    <div id="editor-container"></div>

    {{-- Library resmi OnlyOffice, diambil dari container Docker yang jalan di port 8080 --}}
    <script type="text/javascript" src="http://localhost:8080/web-apps/apps/api/documents/api.js"></script>

    <script>
        const config = {
            document: {
                fileType: "docx",
                key: "{{ md5($filename . time()) }}", // ID unik tiap kali dibuka
                title: "{{ $filename }}",

                // PENTING: pakai host.docker.internal, bukan localhost/127.0.0.1,
                // karena URL ini diakses oleh CONTAINER OnlyOffice, bukan browsermu.
                // Port 8000 harus sama dengan port artisan serve.
                url: "http://host.docker.internal:8000/onlyoffice/file/{{ $filename }}"
            },
            documentType: "word",
            editorConfig: {
                callbackUrl: "http://host.docker.internal:8000/onlyoffice/callback/{{ $filename }}",
                user: {
                    id: "user-1",
                    name: "Pengguna Dokuflow"
                }
            }
        };

        const docEditor = new DocsAPI.DocEditor("editor-container", config);
    </script>

</body>
</html>