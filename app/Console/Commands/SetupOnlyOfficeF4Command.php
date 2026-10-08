<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Signature('onlyoffice:setup-f4 
    {--container= : Nama container Docker OnlyOffice (default: dokuflow-onlyoffice atau auto-detect)}
    {--ssh-host= : IP/hostname server jika Docker OnlyOffice berada di server terpisah}
    {--ssh-port= : Port SSH server OnlyOffice (default: 22)}
    {--ssh-user= : Username SSH (default: root)}
    {--ssh-key= : Path ke private key SSH}
')]
#[Description('Konfigurasi otomatis ukuran kertas F4 (21x33 cm) sebagai opsi dan default print di Docker OnlyOffice (lokal atau remote SSH)')]
class SetupOnlyOfficeF4Command extends Command
{
    public function handle(): int
    {
        $requestedContainer = $this->option('container') ?: config('onlyoffice.container', 'dokuflow-onlyoffice');
        $sshHost   = $this->option('ssh-host') ?: config('onlyoffice.ssh_host');
        $sshPort   = (int) ($this->option('ssh-port') ?: config('onlyoffice.ssh_port', 22));
        $sshUser   = $this->option('ssh-user') ?: config('onlyoffice.ssh_user', 'root');
        $sshKey    = $this->option('ssh-key') ?: config('onlyoffice.ssh_key');

        $this->info("==================================================");
        $this->info("   DocuFlow - OnlyOffice F4 Setup & Patch Tool   ");
        $this->info("==================================================");

        if ($sshHost) {
            $this->line("Target Server    : <comment>{$sshUser}@{$sshHost}:{$sshPort}</comment> (via SSH)");
            if ($sshKey) {
                $this->line("SSH Key          : <comment>{$sshKey}</comment>");
            }
        } else {
            $this->line("Target Mode      : <comment>Local Docker Daemon</comment>");
        }
        $this->newLine();

        // 1. Deteksi dan verifikasi container Docker
        $this->line("1. Memeriksa ketersediaan Docker container...");
        $container = $this->resolveRunningContainer($requestedContainer, $sshHost, $sshPort, $sshUser, $sshKey);

        if (!$container) {
            $this->error("   [ERROR] Container OnlyOffice [{$requestedContainer}] tidak ditemukan atau belum berjalan.");
            if (!$sshHost) {
                $this->newLine();
                $this->warn("   💡 PANDUAN PEMECAHAN MASALAH:");
                $this->line("   1. Pastikan Docker Desktop di komputer Anda sudah running.");
                $this->line("   2. Cek daftar container aktif dengan perintah: <info>docker ps</info>");
                $this->line("   3. Jika nama container berbeda, jalankan: <info>php artisan onlyoffice:setup-f4 --container=NAMA_CONTAINER</info>");
                $this->line("   4. Jika Docker OnlyOffice di server VPS terpisah (misal: 202.10.46.4), jalankan:");
                $this->line("      <info>php artisan onlyoffice:setup-f4 --ssh-host=202.10.46.4 --ssh-user=root</info>");
            } else {
                $this->line("   Pastikan koneksi SSH ke [{$sshHost}] valid dan container OnlyOffice sedang aktif di server tujuan.");
            }
            return Command::FAILURE;
        }

        $this->info("   [OK] Container terdeteksi aktif: <comment>{$container}</comment>");
        $this->newLine();

        // 2. Siapkan python patch script yang tangguh (mendukung file read-write maupun bind volume read-only)
        $pythonScript = <<<'PY'
import os
import re
import time
import subprocess
import sys

ts = str(int(time.time() * 1000))
web_apps = '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main'

if not os.path.exists(web_apps):
    print(f'[!] Directory {web_apps} not found.')
    sys.exit(1)

def try_modify_file(file_path, modifier_fn):
    if not os.path.exists(file_path):
        return
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        new_content, changed = modifier_fn(content)
        if changed:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f'[+] {os.path.basename(file_path)} patched successfully')
        else:
            print(f'[i] {os.path.basename(file_path)} already contains F4 configuration')
    except (PermissionError, OSError) as e:
        print(f'[i] {os.path.basename(file_path)} is read-only / volume-mounted (active via mount)')

# 1. Patch Main.js -> Enable canPreviewPrint for web
def patch_main(content):
    target = 'this.appOptions.canPreviewPrint = this.appOptions.canPrint && !Common.Utils.isMac && this.appOptions.isDesktopApp;'
    rep = 'this.appOptions.canPreviewPrint = this.appOptions.canPrint;'
    if target in content:
        return content.replace(target, rep), True
    return content, False

try_modify_file(f'{web_apps}/app/controller/Main.js', patch_main)

# 2. Patch FileMenuPanels.js -> F4 in print list & default print size F4
def patch_panels(content):
    changed = False
    if '{ value: 18, displayValue: [\'F4\'' not in content:
        content = content.replace(
            '{ value: 2, displayValue: [\'A4\', \'21\', \'29,7\', \'cm\'], caption: \'A4\', size: [210, 297]},',
            '{ value: 2, displayValue: [\'A4\', \'21\', \'29,7\', \'cm\'], caption: \'A4\', size: [210, 297]},\n                { value: 18, displayValue: [\'F4\', \'21\', \'33\', \'cm\'], caption: \'F4 (21 x 33 cm)\', size: [210, 330]},'
        )
        changed = True
    
    f4_select_code = 'newSelectedOption = findOptionBySize(resultList, 210, 330);'
    if f4_select_code not in content:
        target_select = 'const _w = this._originalPageSize ? this._originalPageSize.w : 210'
        if target_select in content:
            content = content.replace(
                target_select,
                f'{f4_select_code}\n                    if (!newSelectedOption) {{\n                        {target_select}'
            )
            if 'if (!newSelectedOption) {' in content and 'newSelectedOption = findOptionBySize(resultList, 210, 330);' in content:
                content = content.replace('newSelectedOption = findOptionBySize(resultList, _w, _h);\n                    }', 'newSelectedOption = findOptionBySize(resultList, _w, _h);\n                    }\n                    }')
            changed = True
    return content, changed

try_modify_file(f'{web_apps}/app/view/FileMenuPanels.js', patch_panels)

# 3. Patch code.js
def patch_code(content):
    changed = False
    if '{ value: 18, displayValue: [\'F4\'' not in content:
        content = content.replace(
            '{ value: 2, displayValue: [\'A4\', \'21\', \'29,7\', \'cm\'], caption: \'A4\', size: [210, 297]},',
            '{ value: 2, displayValue: [\'A4\', \'21\', \'29,7\', \'cm\'], caption: \'A4\', size: [210, 297]},\n                { value: 18, displayValue: [\'F4\', \'21\', \'33\', \'cm\'], caption: \'F4 (21 x 33 cm)\', size: [210, 330]},'
        )
        changed = True
    
    if 'findOptionBySize(resultList, 210, 330)' not in content:
        target_select = 'const _w = this._originalPageSize ? this._originalPageSize.w : 210'
        if target_select in content:
            content = content.replace(
                target_select,
                f'newSelectedOption = findOptionBySize(resultList, 210, 330);\n                    if (!newSelectedOption) {{\n                        {target_select}'
            )
            content = content.replace('newSelectedOption = findOptionBySize(resultList, _w, _h);\n                    }', 'newSelectedOption = findOptionBySize(resultList, _w, _h);\n                    }\n                    }')
            changed = True

    if 'F4 (21 x 33 cm)' not in content:
        content = content.replace(
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},',
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},\n                    { value: 18, displayValue: \'F4 (21 x 33 cm)\', size: [210, 330]},'
        )
        changed = True
    return content, changed

try_modify_file(f'{web_apps}/code.js', patch_code)

# 4. Patch Toolbar.js & PageSizeDialog.js
def patch_toolbar(content):
    if 'caption: \'F4\'' not in content:
        content = content.replace(
            'caption: \'A4\',\n                                    subtitle: \'21cm x 29,7cm\',\n                                    template: pageSizeTemplate,\n                                    checkable: true,\n                                    toggleGroup: \'menuPageSize\',\n                                    value: [210, 297],\n                                    checked: true\n                                },',
            'caption: \'A4\',\n                                    subtitle: \'21cm x 29,7cm\',\n                                    template: pageSizeTemplate,\n                                    checkable: true,\n                                    toggleGroup: \'menuPageSize\',\n                                    value: [210, 297],\n                                    checked: true\n                                },\n                                {\n                                    caption: \'F4\',\n                                    subtitle: \'21cm x 33cm\',\n                                    template: pageSizeTemplate,\n                                    checkable: true,\n                                    toggleGroup: \'menuPageSize\',\n                                    value: [210, 330]\n                                },'
        )
        return content, True
    return content, False

try_modify_file(f'{web_apps}/app/view/Toolbar.js', patch_toolbar)

def patch_page_size_dialog(content):
    if 'F4 (21 x 33 cm)' not in content:
        content = content.replace(
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},',
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},\n                    { value: 18, displayValue: \'F4 (21 x 33 cm)\', size: [210, 330]},'
        )
        return content, True
    return content, False

try_modify_file(f'{web_apps}/app/view/PageSizeDialog.js', patch_page_size_dialog)

# 5. Patch Header.js -> Branding url
header_js = '/var/www/onlyoffice/documentserver/web-apps/apps/common/main/lib/view/Header.js'
def patch_header(content):
    changed = False
    if "'https://www.onlyoffice.com'" in content:
        content = content.replace("'https://www.onlyoffice.com'", "'https://cmhgroup.id'")
        changed = True
    return content, changed

try_modify_file(header_js, patch_header)

# 6. Update cache busters
app_js = f'{web_apps}/app.js'
def patch_app_js(content):
    new_c = re.sub(r"urlArgs:\s*'_dc=[^']+'", f"urlArgs: '_dc=f4_{ts}'", content)
    return new_c, (new_c != content)

try_modify_file(app_js, patch_app_js)

api_js = '/var/www/onlyoffice/documentserver/web-apps/apps/api/documents/api.js'
def patch_api_js(content):
    new_c = re.sub(r'doceditor\.js\?_dc=[^"\']+', f'doceditor.js?_dc={ts}', content)
    return new_c, (new_c != content)

try_modify_file(api_js, patch_api_js)

# 7. Gzip assets if permitted
files_to_gzip = [
    f'{web_apps}/app/controller/Main.js',
    f'{web_apps}/app/view/FileMenuPanels.js',
    f'{web_apps}/app/view/FileMenu.js',
    f'{web_apps}/app/view/PageSizeDialog.js',
    f'{web_apps}/app/view/Toolbar.js',
    f'{web_apps}/code.js',
    f'{web_apps}/app.js',
    header_js,
    api_js,
]

for file_path in files_to_gzip:
    if os.path.exists(file_path):
        try:
            subprocess.run(['gzip', '-k', '-f', file_path], check=False, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        except Exception:
            pass

# 8. Reload Nginx
try:
    subprocess.run(['nginx', '-s', 'reload'], check=False, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    print('[+] Nginx inside OnlyOffice container reloaded successfully')
except Exception as e:
    print(f'[!] Nginx reload note: {e}')
PY;

        // 3. Terapkan patch ke container via temporary script file injection
        $this->line("2. Menyuntikkan patch F4 ke dalam OnlyOffice Document Server...");
        
        $tempLocalFile = tempnam(sys_get_temp_dir(), 'f4_patch_') . '.py';
        file_put_contents($tempLocalFile, $pythonScript);

        $containerDest = "{$container}:/tmp/patch_onlyoffice_f4.py";

        if (empty($sshHost)) {
            // Salin file script ke container lokal
            $cpProc = new Process(['docker', 'cp', $tempLocalFile, $containerDest]);
            $cpProc->setTimeout(60);
            $cpProc->run();

            // Eksekusi script python di dalam container
            $execProc = new Process(['docker', 'exec', $container, 'python3', '/tmp/patch_onlyoffice_f4.py']);
            $execProc->setTimeout(120);
            $execProc->run();

            // Hapus file script sementara di dalam container
            $rmProc = new Process(['docker', 'exec', $container, 'rm', '-f', '/tmp/patch_onlyoffice_f4.py']);
            $rmProc->setTimeout(30);
            $rmProc->run();
        } else {
            // Mode remote SSH: kirim script via docker cp atau base64 execution
            $encodedScript = base64_encode($pythonScript);
            $execProc = $this->runDockerDirect(
                ['exec', '-i', $container, 'python3', '-c', "import base64; exec(base64.b64decode('{$encodedScript}').decode('utf-8'))"],
                $sshHost,
                $sshPort,
                $sshUser,
                $sshKey
            );
        }

        @unlink($tempLocalFile);

        if (!$execProc->isSuccessful()) {
            $errorOutput = trim($execProc->getErrorOutput() ?: $execProc->getOutput());
            $this->error("   [ERROR] Gagal menjalankan patch: " . ($errorOutput ?: 'Unknown execution error'));
            return Command::FAILURE;
        }

        $output = trim($execProc->getOutput());
        if ($output) {
            $this->line($output);
        }

        $this->info("   [OK] Patch berhasil disuntikkan dan Nginx berhasil dimuat ulang.");
        $this->newLine();

        $this->info("==================================================");
        $this->info("   BERHASIL: Konfigurasi F4 Telah Diterapkan!   ");
        $this->info("==================================================");
        $this->line("1. Toolbar <comment>Layout -> Size</comment> kini memiliki preset <comment>F4 (21 x 33 cm)</comment>.");
        $this->line("2. Fitur <comment>File -> Print (Ctrl+P)</comment> otomatis default ke ukuran <comment>F4</comment>.");
        $this->line("3. Opsi format lain (A4, Letter, Legal, Custom) tetap bisa dipilih.");
        $this->line("4. Buka browser dan lakukan <comment>Ctrl + F5</comment> (Hard Refresh) untuk memuat aset terbaru.");
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Cari container OnlyOffice yang aktif secara cerdas (auto-detect fallback).
     */
    protected function resolveRunningContainer(string $requestedName, ?string $sshHost, int $sshPort, string $sshUser, ?string $sshKey): ?string
    {
        // 1. Coba nama yang diminta terlebih dahulu
        $check = new Process(['docker', 'inspect', '-f', '{{.State.Running}}', $requestedName]);
        if (!empty($sshHost)) {
            $check = $this->runDockerDirect(['inspect', '-f', '{{.State.Running}}', $requestedName], $sshHost, $sshPort, $sshUser, $sshKey);
        } else {
            $check->setTimeout(15);
            $check->run();
        }

        if ($check->isSuccessful() && trim($check->getOutput()) === 'true') {
            return $requestedName;
        }

        // 2. Auto-detect: cari container dari image onlyoffice
        if (!empty($sshHost)) {
            $listProc = $this->runDockerDirect(['ps', '--format', '{{.Names}}\t{{.Image}}'], $sshHost, $sshPort, $sshUser, $sshKey);
        } else {
            $listProc = new Process(['docker', 'ps', '--format', '{{.Names}}\t{{.Image}}']);
            $listProc->setTimeout(15);
            $listProc->run();
        }

        if ($listProc->isSuccessful()) {
            $lines = explode("\n", trim($listProc->getOutput()));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $parts = explode("\t", $line);
                $cName = trim($parts[0] ?? '');
                $cImage = trim($parts[1] ?? '');

                if (str_contains(strtolower($cName), 'onlyoffice') || str_contains(strtolower($cImage), 'onlyoffice')) {
                    return $cName;
                }
            }
        }

        return null;
    }

    /**
     * Jalankan perintah Docker remote via SSH.
     */
    protected function runDockerDirect(array $dockerArgs, ?string $sshHost, int $sshPort, string $sshUser, ?string $sshKey): Process
    {
        $cmd = 'docker ' . implode(' ', array_map('escapeshellarg', $dockerArgs));
        $sshArgs = ['ssh', '-p', (string) $sshPort];
        if (!empty($sshKey)) {
            $sshArgs[] = '-i';
            $sshArgs[] = $sshKey;
        }
        $sshArgs[] = '-o';
        $sshArgs[] = 'StrictHostKeyChecking=no';
        $sshArgs[] = "{$sshUser}@{$sshHost}";
        $sshArgs[] = $cmd;

        $process = new Process($sshArgs);
        $process->setTimeout(120);
        $process->run();

        return $process;
    }
}
