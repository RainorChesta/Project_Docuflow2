import os
import re
import time
import subprocess
import gzip

ts = str(int(time.time() * 1000))
base_apps = '/var/www/onlyoffice/documentserver/web-apps/apps'
web_apps = f'{base_apps}/documenteditor/main'
pdf_apps = f'{base_apps}/pdfeditor/main'

print('==================================================')
print('  DocuFlow - OnlyOffice F4 & Overscroll Patcher   ')
print('==================================================')

def safe_write(file_path, content):
    try:
        os.chmod(file_path, 0o644)
    except Exception:
        pass
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

# 1. Ensure Main.js -> Keep native web print (canPreviewPrint only for desktop)
main_js = f'{web_apps}/app/controller/Main.js'
if os.path.exists(main_js):
    with open(main_js, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
    bad_target = 'this.appOptions.canPreviewPrint = this.appOptions.canPrint;'
    good_rep = 'this.appOptions.canPreviewPrint = this.appOptions.canPrint && !Common.Utils.isMac && this.appOptions.isDesktopApp;'
    if bad_target in content:
        content = content.replace(bad_target, good_rep)
        safe_write(main_js, content)
        print('[+] Main.js native web print restored')
    else:
        print('[i] Main.js native web print already active')

# 2. Patch FileMenuPanels.js -> F4 in print list & default print size F4
panels_js = f'{web_apps}/app/view/FileMenuPanels.js'
if os.path.exists(panels_js):
    with open(panels_js, 'r', encoding='utf-8', errors='ignore') as f:
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

    safe_write(panels_js, content)
    print('[+] FileMenuPanels.js patched')

# 3. Patch code.js for F4
code_js = f'{web_apps}/code.js'
if os.path.exists(code_js):
    with open(code_js, 'r', encoding='utf-8', errors='ignore') as f:
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

    safe_write(code_js, content)
    print('[+] code.js F4 patched')

# 4. Patch Toolbar.js & PageSizeDialog.js
tb_js = f'{web_apps}/app/view/Toolbar.js'
if os.path.exists(tb_js):
    with open(tb_js, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
    if 'caption: \'F4\'' not in content:
        content = content.replace(
            'caption: \'A4\',\n                                    subtitle: \'21cm x 29,7cm\',\n                                    template: pageSizeTemplate,\n                                    checkable: true,\n                                    toggleGroup: \'menuPageSize\',\n                                    value: [210, 297],\n                                    checked: true\n                                },',
            'caption: \'A4\',\n                                    subtitle: \'21cm x 29,7cm\',\n                                    template: pageSizeTemplate,\n                                    checkable: true,\n                                    toggleGroup: \'menuPageSize\',\n                                    value: [210, 297],\n                                    checked: true\n                                },\n                                {\n                                    caption: \'F4\',\n                                    subtitle: \'21cm x 33cm\',\n                                    template: pageSizeTemplate,\n                                    checkable: true,\n                                    toggleGroup: \'menuPageSize\',\n                                    value: [210, 330]\n                                },'
        )
        safe_write(tb_js, content)
        print('[+] Toolbar.js patched')

ps_js = f'{web_apps}/app/view/PageSizeDialog.js'
if os.path.exists(ps_js):
    with open(ps_js, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
    if 'F4 (21 x 33 cm)' not in content:
        content = content.replace(
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},',
            '{ value: 2, displayValue: \'A4\', size: [210, 297]},\n                    { value: 18, displayValue: \'F4 (21 x 33 cm)\', size: [210, 330]},'
        )
        safe_write(ps_js, content)
        print('[+] PageSizeDialog.js patched')

# 5. Patch Header.js -> CMH Group logo redirection
header_js = f'{base_apps}/common/main/lib/view/Header.js'
if os.path.exists(header_js):
    with open(header_js, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()

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

    content = content.replace(
        "// 'click #header-logo': function (e) {}",
        "'click #header-logo': function (e) { if (e) { e.preventDefault(); e.stopPropagation(); } window.open('https://cmhgroup.id', '_blank'); }"
    )

    safe_write(header_js, content)

# 6. Inject CMHGROUP Header SVG Logo & Dark Logo
header_img_dir = f'{base_apps}/common/main/resources/img/header'
if os.path.exists(header_img_dir):
    svg_light = """<svg xmlns="http://www.w3.org/2000/svg" width="126" height="20" viewBox="0 0 126 20" fill="none">
  <rect x="1" y="2" width="9" height="13" rx="1.5" fill="#2563EB"/>
  <rect x="2.5" y="1" width="7" height="10" rx="1" fill="#fff" opacity="0.95"/>
  <rect x="3.5" y="3" width="4.5" height="1" rx="0.4" fill="#94a3b8"/>
  <rect x="3.5" y="5" width="3.5" height="1" rx="0.4" fill="#94a3b8"/>
  <rect x="3.5" y="7" width="4.5" height="1" rx="0.4" fill="#94a3b8"/>
  <path d="M2 12.5 Q2.5 16.5 6.5 16.5 L9 16.5 Q12.5 16.5 12.5 13 L12.5 11 Q9.5 11 8 12.5 Z" fill="#10B981"/>
  <path d="M3.2 13.5 L6 13.5 L6 12.6 L7.5 13.9 L6 15.2 L6 14.3 L3.2 14.3 Z" fill="#fff"/>
  <circle cx="11.5" cy="15" r="2.6" fill="#059669" stroke="#fff" stroke-width="0.6"/>
  <path d="M10.3 15 L11.2 16 L12.9 13.8" stroke="#fff" stroke-width="0.85" stroke-linecap="round" stroke-linejoin="round"/>
  <text x="17" y="14.5" fill="#fff" font-family="system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif" font-size="11" font-weight="700" letter-spacing="0.3">CMHGROUP</text>
</svg>"""

    svg_dark = """<svg xmlns="http://www.w3.org/2000/svg" width="126" height="20" viewBox="0 0 126 20" fill="none">
  <rect x="1" y="2" width="9" height="13" rx="1.5" fill="#2563EB"/>
  <rect x="2.5" y="1" width="7" height="10" rx="1" fill="#fff" opacity="0.95"/>
  <rect x="3.5" y="3" width="4.5" height="1" rx="0.4" fill="#94a3b8"/>
  <rect x="3.5" y="5" width="3.5" height="1" rx="0.4" fill="#94a3b8"/>
  <rect x="3.5" y="7" width="4.5" height="1" rx="0.4" fill="#94a3b8"/>
  <path d="M2 12.5 Q2.5 16.5 6.5 16.5 L9 16.5 Q12.5 16.5 12.5 13 L12.5 11 Q9.5 11 8 12.5 Z" fill="#10B981"/>
  <path d="M3.2 13.5 L6 13.5 L6 12.6 L7.5 13.9 L6 15.2 L6 14.3 L3.2 14.3 Z" fill="#fff"/>
  <circle cx="11.5" cy="15" r="2.6" fill="#059669" stroke="#fff" stroke-width="0.6"/>
  <path d="M10.3 15 L11.2 16 L12.9 13.8" stroke="#fff" stroke-width="0.85" stroke-linecap="round" stroke-linejoin="round"/>
  <text x="17" y="14.5" fill="#333" font-family="system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif" font-size="11" font-weight="700" letter-spacing="0.3">CMHGROUP</text>
</svg>"""

    safe_write(f'{header_img_dir}/header-logo_s.svg', svg_light)
    safe_write(f'{header_img_dir}/dark-logo_s.svg', svg_dark)
    print('[+] CMHGROUP SVG logo files injected')

# 7. Inject DokuFlow Bidirectional Overscroll Bridge (Top & Bottom)
OVERSCROLL_MARKER = '/* ==========================================================================\n   DokuFlow ONLYOFFICE Overscroll Bridge'

OVERSCROLL_BRIDGE_JS = """/* ==========================================================================
   DokuFlow ONLYOFFICE Overscroll Bridge
   Allows scrolling upward to scroll the parent page when document is at top,
   and scrolling downward to scroll the parent page when document is at bottom.
   ========================================================================== */
(function initDokuflowOverscrollBridge() {
    if (typeof window === 'undefined' || window.__dokuflowOverscrollBridgeV2) return;
    window.__dokuflowOverscrollBridgeV2 = true;

    function hookScrollObject() {
        try {
            if (window.AscCommon && window.AscCommon.ScrollObject && !window.AscCommon.ScrollObject.__dokuflowHookedV2) {
                var origInit = window.AscCommon.ScrollObject.prototype._init;
                window.AscCommon.ScrollObject.prototype._init = function(elemID) {
                    var res = origInit.apply(this, arguments);
                    if (elemID === 'id_vertical_scroll') {
                        window.__ascMainVerticalScroll = this;
                    }
                    return res;
                };
                window.AscCommon.ScrollObject.__dokuflowHookedV2 = true;
            }
        } catch (e) {}
    }
    hookScrollObject();
    if (!window.AscCommon || !window.AscCommon.ScrollObject) {
        var hookInterval = setInterval(function() {
            if (window.AscCommon && window.AscCommon.ScrollObject) {
                hookScrollObject();
                clearInterval(hookInterval);
            }
        }, 150);
    }

    function getMainScrollObject() {
        try {
            var holder = document.getElementById('id_vertical_scroll');
            if (holder) {
                var canvases = holder.getElementsByTagName('canvas');
                for (var i = 0; i < canvases.length; i++) {
                    if (canvases[i] && canvases[i].that) {
                        window.__ascMainVerticalScroll = canvases[i].that;
                        return canvases[i].that;
                    }
                }
            }
            if (window.__ascMainVerticalScroll) {
                return window.__ascMainVerticalScroll;
            }
        } catch (e) {}
        return null;
    }

    function extractFromScrollObj(s) {
        if (!s) return null;
        var curY = typeof s.getCurScrolledY === 'function' ? s.getCurScrolledY() : (s.scrollVCurrentY || 0);
        var maxY = typeof s.getMaxScrolledY === 'function' ? s.getMaxScrolledY() : (s.maxScrollY || 0);
        if (typeof s.maxScrollY2 === 'number' && !isNaN(s.maxScrollY2) && s.maxScrollY2 >= 0) {
            maxY = Math.min(maxY, s.maxScrollY2);
        }
        curY = (typeof curY === 'number' && !isNaN(curY)) ? curY : 0;
        maxY = (typeof maxY === 'number' && !isNaN(maxY)) ? maxY : 0;

        // Also check visual scrollbar thumb position for rounded drag limits
        if (s.scroller && typeof s.scroller.y === 'number') {
            var minThumbY = (typeof s.dragMinY === 'number' ? s.dragMinY : (s.arrowPosition || 0));
            if (s.scroller.y <= minThumbY + 2 && curY <= 20) {
                curY = 0;
            }
            if (typeof s.dragMaxY === 'number' && s.dragMaxY > 0 && s.scroller.y >= s.dragMaxY - 2) {
                curY = maxY;
            }
        }

        return {
            scrollTop: curY,
            maxScrollTop: maxY
        };
    }

    function getDocScrollInfo() {
        try {
            // 1. Direct DOM #id_vertical_scroll canvas.that instance (works for Word, PDF, Slide)
            var mainScrollObj = getMainScrollObject();
            if (mainScrollObj) {
                return extractFromScrollObj(mainScrollObj);
            }

            var ed = (window.Asc && window.Asc.editor) || window.editor;
            if (!ed) return null;

            // 2. WordControl
            var wc = ed.WordControl || ed.m_oWordControl;
            if (wc) {
                var scrollApi = wc.m_oScrollVerApi || wc.m_oScrollVer_;
                if (scrollApi) {
                    return extractFromScrollObj(scrollApi);
                }
                if (typeof wc.m_dScrollY === 'number') {
                    return {
                        scrollTop: wc.m_dScrollY || 0,
                        maxScrollTop: wc.m_dScrollY_max || 0
                    };
                }
            }

            // 3. PDF DocumentRenderer / HtmlPage
            var dr = ed.DocumentRenderer || (ed.DrawingDocument && ed.DrawingDocument.m_oDocumentRenderer) || ed.HtmlPage || ed.m_oHtmlPage;
            if (dr) {
                var drApi = dr.m_oScrollVerApi || dr.m_oScrollVer_;
                if (drApi) {
                    return extractFromScrollObj(drApi);
                }
                if (typeof dr.scrollY === 'number') {
                    return {
                        scrollTop: dr.scrollY || 0,
                        maxScrollTop: dr.scrollMaxY || 0
                    };
                }
            }

            // 4. Spreadsheet Workbook
            if (ed.wb && typeof ed.wb.getWorksheet === 'function') {
                var ws = ed.wb.getWorksheet();
                if (ws) {
                    if (ws.vsbApi) {
                        return extractFromScrollObj(ws.vsbApi);
                    }
                    if (typeof ws.getScrollY === 'function') {
                        return {
                            scrollTop: ws.getScrollY() || 0,
                            maxScrollTop: typeof ws.getMaxScrollY === 'function' ? (ws.getMaxScrollY() || 0) : 999999
                        };
                    }
                }
            }
        } catch (err) {}
        return null;
    }

    function isInsideInternalScrollable(target, direction) {
        var el = target;
        while (el && el !== document.body && el !== document.documentElement) {
            if (el.id === 'id_main' || el.id === 'id_viewer' || el.id === 'id_main_view' || el.id === 'editor_sdk') {
                return false;
            }
            if (el.scrollHeight > el.clientHeight + 2) {
                var style = window.getComputedStyle(el);
                var overflowY = style ? style.overflowY : '';
                if (overflowY === 'auto' || overflowY === 'scroll') {
                    if (direction === 'up' && el.scrollTop > 0) {
                        return true;
                    }
                    if (direction === 'down' && el.scrollTop + el.clientHeight < el.scrollHeight - 1) {
                        return true;
                    }
                }
            }
            el = el.parentElement;
        }
        return false;
    }

    function sendOverscroll(deltaY, direction) {
        if (!window.parent || window.parent === window) return;
        window.parent.postMessage({
            type: 'onlyoffice-overscroll',
            deltaY: deltaY,
            direction: direction
        }, '*');
    }

    function normalizeWheelDeltaY(e) {
        var rawDelta = 0;
        if (typeof e.deltaY === 'number') {
            rawDelta = e.deltaY;
            if (e.deltaMode === 1) rawDelta *= 36;
            else if (e.deltaMode === 2) rawDelta *= 360;
        } else if (typeof e.wheelDeltaY === 'number') {
            rawDelta = -e.wheelDeltaY;
        } else if (typeof e.wheelDelta === 'number') {
            rawDelta = -e.wheelDelta;
        } else if (typeof e.detail === 'number') {
            rawDelta = e.detail * 36;
        }
        return rawDelta;
    }

    var lastWheelTime = 0;

    function handleWheel(e) {
        if (e.ctrlKey || e.metaKey) return;

        var now = Date.now();
        if (e.type !== 'wheel' && (now - lastWheelTime < 25)) return;
        if (e.type === 'wheel') lastWheelTime = now;

        var deltaPx = normalizeWheelDeltaY(e);
        if (!deltaPx) return;

        // Upward scroll
        if (deltaPx < 0) {
            if (isInsideInternalScrollable(e.target, 'up')) return;

            var info = getDocScrollInfo();
            if (info) {
                var isAtTop = (info.maxScrollTop <= 3) || (info.scrollTop <= 4);
                if (isAtTop) {
                    sendOverscroll(deltaPx, 'up');
                } else {
                    var beforeTop = info.scrollTop;
                    setTimeout(function() {
                        var afterInfo = getDocScrollInfo();
                        if (afterInfo && ((afterInfo.maxScrollTop <= 3) || (afterInfo.scrollTop <= 4))) {
                            var consumed = Math.max(0, beforeTop - afterInfo.scrollTop);
                            var overshoot = deltaPx + consumed;
                            if (overshoot < 0) {
                                sendOverscroll(overshoot, 'up');
                            }
                        }
                    }, 0);
                }
            } else {
                var hasVertScrollEl = document.getElementById('id_vertical_scroll');
                if (!hasVertScrollEl) {
                    sendOverscroll(deltaPx, 'up');
                }
            }
        }
        // Downward scroll
        else if (deltaPx > 0) {
            if (isInsideInternalScrollable(e.target, 'down')) return;

            var info = getDocScrollInfo();
            if (info) {
                var isAtBottom = (info.maxScrollTop <= 3) || (info.scrollTop >= info.maxScrollTop - 5);
                if (isAtBottom) {
                    sendOverscroll(deltaPx, 'down');
                } else {
                    var beforeTop = info.scrollTop;
                    setTimeout(function() {
                        var afterInfo = getDocScrollInfo();
                        if (afterInfo) {
                            var reachedBottom = (afterInfo.maxScrollTop <= 3) || (afterInfo.scrollTop >= afterInfo.maxScrollTop - 5);
                            if (reachedBottom) {
                                var consumed = Math.max(0, afterInfo.scrollTop - beforeTop);
                                var overshoot = deltaPx - consumed;
                                if (overshoot > 0) {
                                    sendOverscroll(overshoot, 'down');
                                }
                            }
                        }
                    }, 0);
                }
            } else {
                var hasVertScrollEl = document.getElementById('id_vertical_scroll');
                if (!hasVertScrollEl) {
                    sendOverscroll(deltaPx, 'down');
                }
            }
        }
    }

    window.addEventListener('wheel', handleWheel, { capture: true, passive: true });
    window.addEventListener('mousewheel', handleWheel, { capture: true, passive: true });
    window.addEventListener('DOMMouseScroll', handleWheel, { capture: true, passive: true });

    var touchStartY = null;
    var touchLastY = null;

    window.addEventListener('touchstart', function(e) {
        if (e.touches && e.touches.length === 1) {
            touchStartY = e.touches[0].clientY;
            touchLastY = touchStartY;
        } else {
            touchStartY = null;
            touchLastY = null;
        }
    }, { capture: true, passive: true });

    window.addEventListener('touchmove', function(e) {
        if (touchLastY === null || !e.touches || e.touches.length !== 1) return;
        var currentY = e.touches[0].clientY;
        var diffY = touchLastY - currentY;
        touchLastY = currentY;

        if (diffY < 0) {
            if (isInsideInternalScrollable(e.target, 'up')) return;
            var info = getDocScrollInfo();
            if (info && ((info.maxScrollTop <= 3) || (info.scrollTop <= 4))) {
                sendOverscroll(diffY * 1.5, 'up');
            } else if (!info && !document.getElementById('id_vertical_scroll')) {
                sendOverscroll(diffY * 1.5, 'up');
            }
        } else if (diffY > 0) {
            if (isInsideInternalScrollable(e.target, 'down')) return;
            var info = getDocScrollInfo();
            if (info && ((info.maxScrollTop <= 3) || (info.scrollTop >= info.maxScrollTop - 5))) {
                sendOverscroll(diffY * 1.5, 'down');
            } else if (!info && !document.getElementById('id_vertical_scroll')) {
                sendOverscroll(diffY * 1.5, 'down');
            }
        }
    }, { capture: true, passive: true });

    window.addEventListener('touchend', function() {
        touchStartY = null;
        touchLastY = null;
    }, { capture: true, passive: true });
})();
"""

PARENT_RECEIVER_MARKER = '/* DokuFlow Parent Overscroll Receiver */'
PARENT_RECEIVER_JS = """
/* DokuFlow Parent Overscroll Receiver */
(function initDokuflowParentOverscrollReceiver() {
    if (typeof window === 'undefined' || window._dokuflowOverscrollBound) return;
    window._dokuflowOverscrollBound = true;
    window.addEventListener('message', function(e) {
        if (!e.data || e.data.type !== 'onlyoffice-overscroll') return;
        if (typeof window._releaseOnlyOfficeScrollGuard === 'function') {
            window._releaseOnlyOfficeScrollGuard();
        }
        var deltaY = typeof e.data.deltaY === 'number' ? e.data.deltaY : 0;
        if (!deltaY) return;
        var mainEl = document.querySelector('main');
        var isMainScrollable = mainEl && (mainEl.scrollHeight > mainEl.clientHeight + 1);
        if (isMainScrollable) {
            var prevTop = mainEl.scrollTop;
            mainEl.scrollTop = Math.max(0, mainEl.scrollTop + deltaY);
            if (mainEl.scrollTop === prevTop && (document.documentElement.scrollTop > 0 || document.body.scrollTop > 0 || deltaY > 0)) {
                window.scrollBy({ top: deltaY, behavior: 'auto' });
            }
        } else {
            window.scrollBy({ top: deltaY, behavior: 'auto' });
        }
    });
})();
"""

js_targets = [
    f'{web_apps}/code.js',
    f'{web_apps}/app/controller/Main.js',
    f'{pdf_apps}/code.js',
    f'{pdf_apps}/app/controller/Main.js',
    f'{base_apps}/spreadsheeteditor/main/code.js',
    f'{base_apps}/presentationeditor/main/code.js',
]

for js_file in js_targets:
    if os.path.exists(js_file):
        with open(js_file, 'r', encoding='utf-8', errors='ignore') as f:
            c = f.read()
        if OVERSCROLL_MARKER in c:
            c = c[:c.index(OVERSCROLL_MARKER)].rstrip()
        c = c + '\n\n' + OVERSCROLL_BRIDGE_JS + '\n'
        safe_write(js_file, c)
        print(f'[+] Overscroll bridge injected into JS: {js_file}')

html_targets = [
    f'{web_apps}/index.html',
    f'{web_apps}/index_loader.html',
    f'{base_apps}/documenteditor/embed/index.html',
    f'{base_apps}/documenteditor/embed/index_loader.html',
    f'{pdf_apps}/index.html',
    f'{pdf_apps}/index_loader.html',
    f'{base_apps}/spreadsheeteditor/main/index.html',
    f'{base_apps}/spreadsheeteditor/main/index_loader.html',
    f'{base_apps}/spreadsheeteditor/embed/index.html',
    f'{base_apps}/spreadsheeteditor/embed/index_loader.html',
    f'{base_apps}/presentationeditor/main/index.html',
    f'{base_apps}/presentationeditor/main/index_loader.html',
    f'{base_apps}/presentationeditor/embed/index.html',
    f'{base_apps}/presentationeditor/embed/index_loader.html',
]

script_tag = f'    <script id="dokuflow-overscroll-bridge">\n{OVERSCROLL_BRIDGE_JS}    </script>\n'

for html_file in html_targets:
    if os.path.exists(html_file):
        with open(html_file, 'r', encoding='utf-8', errors='ignore') as f:
            c = f.read()
        # Remove any previous overscroll script block
        c = re.sub(r'\s*<script[^>]*>\s*/\*\s*={10,}\s*DokuFlow ONLYOFFICE Overscroll Bridge[\s\S]*?</script>', '', c)
        if '</body>' in c:
            c = c.replace('</body>', script_tag + '</body>', 1)
            safe_write(html_file, c)
            print(f'[+] Overscroll bridge injected into HTML: {html_file}')

# 8. Update cache busters & inject parent receiver into api.js
for app_js_path in [
    f'{web_apps}/app.js',
    f'{pdf_apps}/app.js',
    f'{base_apps}/spreadsheeteditor/main/app.js',
    f'{base_apps}/presentationeditor/main/app.js',
]:
    if os.path.exists(app_js_path):
        with open(app_js_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        if "urlArgs:" in content:
            content = re.sub(r"urlArgs:\s*'_dc=[^']+'", f"urlArgs: '_dc=f4_{ts}'", content)
        else:
            content = content.replace("baseUrl: '../../',", f"baseUrl: '../../',\n    urlArgs: '_dc=f4_{ts}',", 1)
        safe_write(app_js_path, content)

api_js = f'{base_apps}/api/documents/api.js'
if os.path.exists(api_js):
    with open(api_js, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
    content = re.sub(r'doceditor\.js\?_dc=[^"\']+', f'doceditor.js?_dc={ts}', content)
    content = re.sub(r'var params = "\?_dc=[^"]+";', f'var params = "?_dc={ts}";', content)
    if PARENT_RECEIVER_MARKER in content:
        content = content[:content.index(PARENT_RECEIVER_MARKER)].rstrip()
    content = content + '\n' + PARENT_RECEIVER_JS + '\n'
    safe_write(api_js, content)
    print('[+] api.js updated with cache buster & parent overscroll receiver')

# 9. Gzip all modified assets
files_to_gzip = [
    f'{web_apps}/app/controller/Main.js',
    f'{web_apps}/app/view/FileMenuPanels.js',
    f'{web_apps}/app/view/FileMenu.js',
    f'{web_apps}/app/view/PageSizeDialog.js',
    f'{web_apps}/app/view/Toolbar.js',
    f'{web_apps}/code.js',
    f'{web_apps}/app.js',
    f'{pdf_apps}/code.js',
    f'{pdf_apps}/app.js',
    f'{pdf_apps}/app/controller/Main.js',
    f'{base_apps}/spreadsheeteditor/main/code.js',
    f'{base_apps}/spreadsheeteditor/main/app.js',
    f'{base_apps}/presentationeditor/main/code.js',
    f'{base_apps}/presentationeditor/main/app.js',
    f'{header_img_dir}/header-logo_s.svg',
    f'{header_img_dir}/dark-logo_s.svg',
    header_js,
    api_js,
] + html_targets

for f in files_to_gzip:
    if os.path.exists(f):
        try:
            with open(f, 'rb') as f_in:
                data = f_in.read()
            gz_path = f + '.gz'
            if os.path.exists(gz_path):
                try:
                    os.chmod(gz_path, 0o644)
                except Exception:
                    pass
            with open(gz_path, 'wb') as f_out:
                f_out.write(gzip.compress(data, compresslevel=9))
            print(f'[+] Gzipped: {gz_path}')
        except Exception as e:
            print(f'[!] Warning: Failed to gzip {f}: {e}')

# 10. Reload Nginx
subprocess.run(['nginx', '-s', 'reload'], check=True)
print('[+] Nginx reloaded successfully')
print('[+] Done! F4 paper preset, CMH Group logo, and Bidirectional Overscroll Bridge applied.')
