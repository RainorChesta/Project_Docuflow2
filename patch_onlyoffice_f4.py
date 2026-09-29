import os
import re
import time
import subprocess

ts = str(int(time.time() * 1000))
web_apps = '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main'

print('==================================================')
print('  DocuFlow - OnlyOffice F4 Setup & Patch Tool     ')
print('==================================================')

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
        print('[i] Main.js already patched or target signature changed')

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

# 6. Inject CMHGROUP Header SVG Logo & Dark Logo
header_img_dir = '/var/www/onlyoffice/documentserver/web-apps/apps/common/main/resources/img/header'
if os.path.exists(header_img_dir):
    svg_light = """<svg xmlns="http://www.w3.org/2000/svg" width="126" height="20" viewBox="0 0 126 20" fill="none">
  <!-- Dokuflow icon: document (blue bg) -->
  <rect x="1" y="2" width="9" height="13" rx="1.5" fill="#2563EB"/>
  <!-- white paper -->
  <rect x="2.5" y="1" width="7" height="10" rx="1" fill="#fff" opacity="0.95"/>
  <!-- paper lines -->
  <rect x="3.5" y="3" width="4.5" height="1" rx="0.4" fill="#94a3b8"/>
  <rect x="3.5" y="5" width="3.5" height="1" rx="0.4" fill="#94a3b8"/>
  <rect x="3.5" y="7" width="4.5" height="1" rx="0.4" fill="#94a3b8"/>
  <!-- green bottom sweep -->
  <path d="M2 12.5 Q2.5 16.5 6.5 16.5 L9 16.5 Q12.5 16.5 12.5 13 L12.5 11 Q9.5 11 8 12.5 Z" fill="#10B981"/>
  <!-- arrow -->
  <path d="M3.2 13.5 L6 13.5 L6 12.6 L7.5 13.9 L6 15.2 L6 14.3 L3.2 14.3 Z" fill="#fff"/>
  <!-- check badge -->
  <circle cx="11.5" cy="15" r="2.6" fill="#059669" stroke="#fff" stroke-width="0.6"/>
  <path d="M10.3 15 L11.2 16 L12.9 13.8" stroke="#fff" stroke-width="0.85" stroke-linecap="round" stroke-linejoin="round"/>
  <!-- CMHGROUP -->
  <text x="17" y="14.5" fill="#fff" font-family="system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif" font-size="11" font-weight="700" letter-spacing="0.3">CMHGROUP</text>
</svg>"""

    svg_dark = """<svg xmlns="http://www.w3.org/2000/svg" width="126" height="20" viewBox="0 0 126 20" fill="none">
  <!-- Dokuflow icon: document (blue bg) -->
  <rect x="1" y="2" width="9" height="13" rx="1.5" fill="#2563EB"/>
  <!-- white paper -->
  <rect x="2.5" y="1" width="7" height="10" rx="1" fill="#fff" opacity="0.95"/>
  <!-- paper lines -->
  <rect x="3.5" y="3" width="4.5" height="1" rx="0.4" fill="#94a3b8"/>
  <rect x="3.5" y="5" width="3.5" height="1" rx="0.4" fill="#94a3b8"/>
  <rect x="3.5" y="7" width="4.5" height="1" rx="0.4" fill="#94a3b8"/>
  <!-- green bottom sweep -->
  <path d="M2 12.5 Q2.5 16.5 6.5 16.5 L9 16.5 Q12.5 16.5 12.5 13 L12.5 11 Q9.5 11 8 12.5 Z" fill="#10B981"/>
  <!-- arrow -->
  <path d="M3.2 13.5 L6 13.5 L6 12.6 L7.5 13.9 L6 15.2 L6 14.3 L3.2 14.3 Z" fill="#fff"/>
  <!-- check badge -->
  <circle cx="11.5" cy="15" r="2.6" fill="#059669" stroke="#fff" stroke-width="0.6"/>
  <path d="M10.3 15 L11.2 16 L12.9 13.8" stroke="#fff" stroke-width="0.85" stroke-linecap="round" stroke-linejoin="round"/>
  <!-- CMHGROUP -->
  <text x="17" y="14.5" fill="#333" font-family="system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif" font-size="11" font-weight="700" letter-spacing="0.3">CMHGROUP</text>
</svg>"""

    with open(f'{header_img_dir}/header-logo_s.svg', 'w', encoding='utf-8') as f:
        f.write(svg_light)
    with open(f'{header_img_dir}/dark-logo_s.svg', 'w', encoding='utf-8') as f:
        f.write(svg_dark)
    print('[+] CMHGROUP SVG logo files injected')

# 7. Update cache busters
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

# 8. Gzip assets
files_to_gzip = [
    f'{web_apps}/app/controller/Main.js',
    f'{web_apps}/app/view/FileMenuPanels.js',
    f'{web_apps}/app/view/FileMenu.js',
    f'{web_apps}/app/view/PageSizeDialog.js',
    f'{web_apps}/app/view/Toolbar.js',
    f'{web_apps}/code.js',
    f'{web_apps}/app.js',
    f'{header_img_dir}/header-logo_s.svg',
    f'{header_img_dir}/dark-logo_s.svg',
    header_js,
    api_js,
]

for f in files_to_gzip:
    if os.path.exists(f):
        subprocess.run(['gzip', '-k', '-f', f], check=True)

# 9. Reload Nginx
subprocess.run(['nginx', '-s', 'reload'], check=True)
print('[+] Nginx reloaded successfully')
print('[+] Done! F4 paper preset and CMH Group logo applied.')

