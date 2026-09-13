import re, os, subprocess

# 1. Patch FileMenuPanels.js
fmp_path = '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main/app/view/FileMenuPanels.js'
with open(fmp_path, 'r', encoding='utf-8') as f:
    fmp = f.read()

target = """                } else {
                    const _w = this._originalPageSize ? this._originalPageSize.w : 210,
                        _h = this._originalPageSize ? this._originalPageSize.h : 297;
                    // If no matching option is found, look for the default size 210x297 (A4)
                    if (!newSelectedOption) {
                        newSelectedOption = findOptionBySize(resultList, _w, _h);
                    }"""

replacement = """                } else {
                    // Requirement: Default print paper size is always F4 (210 x 330)
                    newSelectedOption = findOptionBySize(resultList, 210, 330);
                    if (!newSelectedOption) {
                        const _w = this._originalPageSize ? this._originalPageSize.w : 210,
                            _h = this._originalPageSize ? this._originalPageSize.h : 330;
                        newSelectedOption = findOptionBySize(resultList, _w, _h);
                    }"""

if target in fmp:
    fmp = fmp.replace(target, replacement)
    with open(fmp_path, 'w', encoding='utf-8') as f:
        f.write(fmp)
    print('Patched FileMenuPanels.js')
else:
    print('Target not found in FileMenuPanels.js')

# 2. Patch code.js
code_path = '/var/www/onlyoffice/documentserver/web-apps/apps/documenteditor/main/code.js'
with open(code_path, 'r', encoding='utf-8') as f:
    code = f.read()

if target in code:
    code = code.replace(target, replacement)
    with open(code_path, 'w', encoding='utf-8') as f:
        f.write(code)
    print('Patched code.js')
else:
    print('Target not found in code.js')

# Gzip
for f in [fmp_path, code_path]:
    subprocess.run(['gzip', '-k', '-f', f])
    print('Gzipped', f)
