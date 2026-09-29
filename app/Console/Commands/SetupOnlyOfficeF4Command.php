<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Signature('onlyoffice:setup-f4 
    {--container= : Nama container Docker OnlyOffice (default: dokuflow-onlyoffice)}
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
        $container = $this->option('container') ?: config('onlyoffice.container', 'dokuflow-onlyoffice');
        $sshHost   = $this->option('ssh-host') ?: config('onlyoffice.ssh_host');
        $sshPort   = (int) ($this->option('ssh-port') ?: config('onlyoffice.ssh_port', 22));
        $sshUser   = $this->option('ssh-user') ?: config('onlyoffice.ssh_user', 'root');
        $sshKey    = $this->option('ssh-key') ?: config('onlyoffice.ssh_key');

        $this->info("==================================================");
        $this->info("   DocuFlow - OnlyOffice F4 Setup & Patch Tool   ");
        $this->info("==================================================");
        $this->line("Target Container : <comment>{$container}</comment>");
        if ($sshHost) {
            $this->line("Target Server    : <comment>{$sshUser}@{$sshHost}:{$sshPort}</comment> (via SSH)");
            if ($sshKey) {
                $this->line("SSH Key          : <comment>{$sshKey}</comment>");
            }
        } else {
            $this->line("Target Mode      : <comment>Local Docker Daemon</comment>");
        }
        $this->newLine();

        // 1. Cek status container (lokal atau remote via SSH)
        $this->line("1. Memeriksa status Docker container [{$container}]...");
        $inspectProcess = $this->runDockerCommand(
            "docker inspect -f '{{.State.Running}}' {$container}",
            $sshHost,
            $sshPort,
            $sshUser,
            $sshKey
        );

        if (!$inspectProcess->isSuccessful() || trim($inspectProcess->getOutput()) !== 'true') {
            $this->error("   [ERROR] Container [{$container}] tidak ditemukan atau sedang mati.");
            if (!$sshHost) {
                $this->newLine();
                $this->warn("   💡 INFORMASI ARSITEKTUR MULTI-SERVER:");
                $this->line("   Jika Docker OnlyOffice berada di server terpisah (misalnya <comment>202.10.46.4</comment> sesuai config Nginx / ds-vpath),");
                $this->line("   jalankan command ini dengan menyertakan opsi <comment>--ssh-host</comment>:");
                $this->line("   <info>php artisan onlyoffice:setup-f4 --ssh-host=202.10.46.4 --ssh-user=root</info>");
                $this->newLine();
                $this->line("   Atau jalankan patch langsung di server OnlyOffice menggunakan script:");
                $this->line("   <info>docker exec -i {$container} python3 - < patch_onlyoffice_f4.py</info>");
            } else {
                $errorMsg = trim($inspectProcess->getErrorOutput() ?: $inspectProcess->getOutput());
                $this->line("   Output/Error: " . ($errorMsg ?: 'Connection refused or container not running'));
                $this->line("   Pastikan koneksi SSH ke [{$sshHost}] valid dan container [{$container}] sedang aktif.");
            }
            return Command::FAILURE;
        }
        $this->info("   [OK] Container [{$container}] sedang aktif.");
        $this->newLine();

        // 2. Siapkan python patch script untuk dieksekusi di dalam container
        $pythonScript = <<<'PY'
import os
import re
import time
import subprocess

ts = str(int(time.time() * 1000))
web_apps = '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main'

# 1. Patch Main.js -> Enable canPreviewPrint for web
main_js = f'{web_apps}/app/controller/Main.js'
if os.path.exists(main_js):
    with open(main_js, 'r', encoding='utf-8') as f:
        content = f.read()
    target = 'this.appOptions.canPreviewPrint = this.appOptions.canPrint && !Common.Utils.isMac && this.appOptions.isDesktopApp;'
    rep = 'this.appOptions.canPreviewPrint = this.appOptions.canPrint;'
    if target in content:
        content = content.replace(target, rep)
        with open(main_js, 'w', encoding='utf-8') as f:
            f.write(content)
        print('[+] Main.js canPreviewPrint patched')
    else:
        print('[i] Main.js already patched or target signature not found')

# 2. Patch FileMenuPanels.js -> F4 in print list & default print size F4
panels_js = f'{web_apps}/app/view/FileMenuPanels.js'
if os.path.exists(panels_js):
    with open(panels_js, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Add F4 to _defaultPaperSizeList if not present
    if '{ value: 18, displayValue: [\'F4\'' not in content:
        content = content.replace(
            '{ value: 2, displayValue: [\'A4\', \'21\', \'29,7\', \'cm\'], caption: \'A4\', size: [210, 297]},',
            '{ value: 2, displayValue: [\'A4\', \'21\', \'29,7\', \'cm\'], caption: \'A4\', size: [210, 297]},\n                { value: 18, displayValue: [\'F4\', \'21\', \'33\', \'cm\'], caption: \'F4 (21 x 33 cm)\', size: [210, 330]},'
        )
    
    # Default selection to F4
    f4_select_code = 'newSelectedOption = findOptionBySize(resultList, 210, 330);'
    if f4_select_code not in content:
        target_select = 'const _w = this._originalPageSize ? this._originalPageSize.w : 210'
        content = content.replace(
            target_select,
            f'{f4_select_code}\n                    if (!newSelectedOption) {{\n                        {target_select}'
        )
        if 'if (!newSelectedOption) {' in content and 'newSelectedOption = findOptionBySize(resultList, 210, 330);' in content:
            content = content.replace('newSelectedOption = findOptionBySize(resultList, _w, _h);\n                    }', 'newSelectedOption = findOptionBySize(resultList, _w, _h);\n                    }\n                    }')

    with open(panels_js, 'w', encoding='utf-8') as f:
        f.write(content)
    print('[+] FileMenuPanels.js patched')

# 3. Patch code.js
code_js = f'{web_apps}/code.js'
if os.path.exists(code_js):
    with open(code_js, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if '{ value: 18, displayValue: [\'F4\'' not in content:
        content = content.replace(
            '{ value: 2, displayValue: [\'A4\', \'21\', \'29,7\', \'cm\'], caption: \'A4\', size: [210, 297]},',
            '{ value: 2, displayValue: [\'A4\', \'21\', \'29,7\', \'cm\'], caption: \'A4\', size: [210, 297]},\n                { value: 18, displayValue: [\'F4\', \'21\', \'33\', \'cm\'], caption: \'F4 (21 x 33 cm)\', size: [210, 330]},'
        )
    
    if 'findOptionBySize(resultList, 210, 330)' not in content:
        target_select = 'const _w = this._originalPageSize ? this._originalPageSize.w : 210'
        if target_select in content:
            content = content.replace(
                target_select,
                f'newSelectedOption = findOptionBySize(resultList, 210, 330);\n                    if (!newSelectedOption) {{\n                        {target_select}'
            )
            content = content.replace('newSelectedOption = findOptionBySize(resultList, _w, _h);\n                    }', 'newSelectedOption = findOptionBySize(resultList, _w, _h);\n                    }\n                    }')

    # PageSizeDialog inside code.js
    if 'F4 (21 x 33 cm)' not in content:
        content = content.replace(
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},',
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},\n                    { value: 18, displayValue: \'F4 (21 x 33 cm)\', size: [210, 330]},'
        )

    with open(code_js, 'w', encoding='utf-8') as f:
        f.write(content)
    print('[+] code.js patched')

# 4. Patch Toolbar.js & PageSizeDialog.js
tb_js = f'{web_apps}/app/view/Toolbar.js'
if os.path.exists(tb_js):
    with open(tb_js, 'r', encoding='utf-8') as f:
        content = f.read()
    if 'caption: \'F4\'' not in content:
        content = content.replace(
            'caption: \'A4\',\n                                    subtitle: \'21cm x 29,7cm\',\n                                    template: pageSizeTemplate,\n                                    checkable: true,\n                                    toggleGroup: \'menuPageSize\',\n                                    value: [210, 297],\n                                    checked: true\n                                },',
            'caption: \'A4\',\n                                    subtitle: \'21cm x 29,7cm\',\n                                    template: pageSizeTemplate,\n                                    checkable: true,\n                                    toggleGroup: \'menuPageSize\',\n                                    value: [210, 297],\n                                    checked: true\n                                },\n                                {\n                                    caption: \'F4\',\n                                    subtitle: \'21cm x 33cm\',\n                                    template: pageSizeTemplate,\n                                    checkable: true,\n                                    toggleGroup: \'menuPageSize\',\n                                    value: [210, 330]\n                                },'
        )
        with open(tb_js, 'w', encoding='utf-8') as f:
            f.write(content)
        print('[+] Toolbar.js patched')

ps_js = f'{web_apps}/app/view/PageSizeDialog.js'
if os.path.exists(ps_js):
    with open(ps_js, 'r', encoding='utf-8') as f:
        content = f.read()
    if 'F4 (21 x 33 cm)' not in content:
        content = content.replace(
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},',
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},\n                    { value: 18, displayValue: \'F4 (21 x 33 cm)\', size: [210, 330]},'
        )
        with open(ps_js, 'w', encoding='utf-8') as f:
            f.write(content)
        print('[+] PageSizeDialog.js patched')

# 5. Patch Header.js -> CMH Group logo redirection
header_js = '/var/www/onlyoffice/documentserver/web-apps/apps/common/main/lib/view/Header.js'
if os.path.exists(header_js):
    with open(header_js, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace onlyoffice.com fallback URL with cmhgroup.id
    target_click = """            if ( me.logo )
                me.logo.children(0).on('click', function (e) {
                    var _url = !!me.branding && !!me.branding.logo && (me.branding.logo.url!==undefined) ?
                        me.branding.logo.url : 'https://www.onlyoffice.com';
                    if (_url) {
                        var newDocumentPage = window.open(_url);
                        newDocumentPage && newDocumentPage.focus();
                    }
                });"""

    rep_click = """            if ( me.logo ) {
                me.logo.off('click').on('click', function (e) {
                    if (e) { e.preventDefault(); e.stopPropagation(); }
                    window.open('https://cmhgroup.id', '_blank');
                });
                me.logo.find('i, img, svg').off('click').on('click', function (e) {
                    if (e) { e.preventDefault(); e.stopPropagation(); }
                    window.open('https://cmhgroup.id', '_blank');
                });
            }"""

    if target_click in content:
        content = content.replace(target_click, rep_click)
        print('[+] Header.js logo click handler patched')
    elif "'https://www.onlyoffice.com'" in content:
        content = content.replace("'https://www.onlyoffice.com'", "'https://cmhgroup.id'")
        print('[+] Header.js fallback url replaced with cmhgroup.id')
    else:
        print('[i] Header.js logo click already patched')

    # Also update events if present
    content = content.replace(
        "// 'click #header-logo': function (e) {}",
        "'click #header-logo': function (e) { if (e) { e.preventDefault(); e.stopPropagation(); } window.open('https://cmhgroup.id', '_blank'); }"
    )

    with open(header_js, 'w', encoding='utf-8') as f:
        f.write(content)

# 6. Update cache busters
app_js = f'{web_apps}/app.js'
if os.path.exists(app_js):
    with open(app_js, 'r', encoding='utf-8') as f:
        content = f.read()
    content = re.sub(r"urlArgs:\s*'_dc=[^']+'", f"urlArgs: '_dc=f4_{ts}'", content)
    with open(app_js, 'w', encoding='utf-8') as f:
        f.write(content)

api_js = '/var/www/onlyoffice/documentserver/web-apps/apps/api/documents/api.js'
if os.path.exists(api_js):
    with open(api_js, 'r', encoding='utf-8') as f:
        content = f.read()
    content = re.sub(r'doceditor\.js\?_dc=[^"\']+', f'doceditor.js?_dc={ts}', content)
    with open(api_js, 'w', encoding='utf-8') as f:
        f.write(content)

# 7. Gzip assets
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

for f in files_to_gzip:
    if os.path.exists(f):
        subprocess.run(['gzip', '-k', '-f', f], check=True)

# 8. Reload Nginx
subprocess.run(['nginx', '-s', 'reload'], check=True)
print('[+] Nginx inside OnlyOffice container reloaded successfully')
PY;

        // 3. Terapkan patch ke container via streaming stdin
        $this->line("2. Menyuntikkan patch F4 ke dalam OnlyOffice Document Server...");
        $execProcess = $this->runDockerCommand(
            "docker exec -i {$container} python3 -",
            $sshHost,
            $sshPort,
            $sshUser,
            $sshKey,
            $pythonScript
        );

        if (!$execProcess->isSuccessful()) {
            $this->error("   [ERROR] Gagal menjalankan patch: " . $execProcess->getErrorOutput());
            return Command::FAILURE;
        }

        $output = trim($execProcess->getOutput());
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
     * Jalankan perintah Docker, baik di mesin lokal atau melalui remote SSH.
     */
    protected function runDockerCommand(string $dockerCommand, ?string $sshHost, int $sshPort, string $sshUser, ?string $sshKey, ?string $stdinInput = null): Process
    {
        if (!empty($sshHost)) {
            $args = ['ssh', '-p', (string) $sshPort];
            if (!empty($sshKey)) {
                $args[] = '-i';
                $args[] = $sshKey;
            }
            $args[] = '-o';
            $args[] = 'StrictHostKeyChecking=no';
            $args[] = "{$sshUser}@{$sshHost}";
            $args[] = $dockerCommand;

            $process = new Process($args);
        } else {
            $process = Process::fromShellCommandline($dockerCommand);
        }

        if ($stdinInput !== null) {
            $process->setInput($stdinInput);
        }

        $process->setTimeout(300);
        $process->run();

        return $process;
    }
}
