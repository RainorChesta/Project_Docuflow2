<?php
$pythonScript = <<<'PY'
import re, os, subprocess

# 1. Patch document_editor_service_worker.js
sw_path = '/var/www/onlyoffice/documentserver/sdkjs/common/serviceworker/document_editor_service_worker.js'
sw_clean = """
self.addEventListener('install', function(event) {
    self.skipWaiting();
});
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(keys.map(function(k) { return caches.delete(k); }));
        }).then(function() {
            return self.clients.claim();
        }).then(function() {
            return self.registration.unregister();
        })
    );
});
self.addEventListener('fetch', function(event) {
    return;
});
"""
with open(sw_path, 'w', encoding='utf-8') as f:
    f.write(sw_clean)
print('Patched document_editor_service_worker.js')

# 2. Patch index.html
index_path = '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main/index.html'
with open(index_path, 'r', encoding='utf-8') as f:
    idx_content = f.read()

new_sw_reg = """+function registerServiceWorker(){
    if ('caches' in window) {
        caches.keys().then(function(names) {
            names.forEach(function(name) { caches.delete(name); });
        });
    }
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            registrations.forEach(function(r) { r.unregister(); });
        });
    }
}();"""

idx_content = re.sub(r'\+function registerServiceWorker\(\)\{.*?\(\)\;', new_sw_reg, idx_content, flags=re.DOTALL)
with open(index_path, 'w', encoding='utf-8') as f:
    f.write(idx_content)
print('Patched index.html')

# 3. Patch app.js
app_path = '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main/app.js'
with open(app_path, 'r', encoding='utf-8') as f:
    app_content = f.read()

app_content = re.sub(r"urlArgs:\s*'_dc=[^']+'", "urlArgs: '_dc=f4_' + Date.now()", app_content)
with open(app_path, 'w', encoding='utf-8') as f:
    f.write(app_content)
print('Patched app.js')

# 4. Patch api.js
api_path = '/var/www/onlyoffice/documentserver/web-apps/apps/api/documents/api.js'
with open(api_path, 'r', encoding='utf-8') as f:
    api_content = f.read()

api_content = re.sub(r'var params = "\?_dc=9\.4\.0-[^"]+";', 'var params = "?_dc=9.4.0-f4-" + Date.now();', api_content)
with open(api_path, 'w', encoding='utf-8') as f:
    f.write(api_content)
print('Patched api.js')

# 5. Gzip all
files_to_gz = [
    sw_path,
    index_path,
    app_path,
    api_path,
    '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main/app/view/Toolbar.js',
    '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main/app/view/PageSizeDialog.js',
    '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main/app/view/FileMenuPanels.js',
    '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main/code.js',
    '/var/www/onlyoffice/documentserver/sdkjs/word/sdk-all-min.js'
]
for f in files_to_gz:
    if os.path.exists(f):
        subprocess.run(['gzip', '-k', '-f', f])
        print('Gzipped', f)

PY;

file_put_contents(__DIR__ . '/patch_sw_inner.py', $pythonScript);
exec('docker cp ' . escapeshellarg(__DIR__ . '/patch_sw_inner.py') . ' dokuflow-onlyoffice:/tmp/patch_sw_inner.py');
$out = shell_exec('docker exec dokuflow-onlyoffice python3 /tmp/patch_sw_inner.py');
echo $out . PHP_EOL;

shell_exec('docker exec dokuflow-onlyoffice nginx -s reload');
echo "Nginx reloaded.\n";
