// Ukuran kertas (px @96dpi). key = label yang tampil di dropdown.
const PAPER_SIZES = {
    'F4': { width: 794, height: 1247 },
    'A4': { width: 794, height: 1123 },
    'A5': { width: 559, height: 794 },
    'A3': { width: 1123, height: 1587 },
    'Letter': { width: 816, height: 1056 },
    'Legal': { width: 816, height: 1344 },
};

const DEFAULT_MARGIN = { top: 96, right: 96, bottom: 96, left: 96 };
const MIN_PAGE_CONTENT_PX = 60;
const BOUNDARY_EPS = 0.5;

function findPaperKey(size) {
    for (const key of Object.keys(PAPER_SIZES)) {
        if (PAPER_SIZES[key] === size) return key;
    }
    return null;
}

function clampMarginToPage(size, margin) {
    const clamped = { ...margin };
    if (clamped.top + clamped.bottom > size.height - MIN_PAGE_CONTENT_PX) {
        clamped.top = Math.max(0, size.height - MIN_PAGE_CONTENT_PX - clamped.bottom);
    }
    if (clamped.left + clamped.right > size.width - MIN_PAGE_CONTENT_PX) {
        clamped.left = Math.max(0, size.width - MIN_PAGE_CONTENT_PX - clamped.right);
    }
    return clamped;
}

function buildSpacerElement(margin, gap, extraAttrs) {
    const spacer = document.createElement('div');
    spacer.setAttribute('data-page-spacer', 'true');
    spacer.setAttribute('contenteditable', 'false');
    if (extraAttrs) {
        Object.entries(extraAttrs).forEach(([k, v]) => spacer.setAttribute(k, v));
    }
    Object.assign(spacer.style, {
        margin: `0 -${margin.right}px 0 -${margin.left}px`,
        pointerEvents: 'none',
        userSelect: 'none',
    });

    const gapBandHeight = 20;
    const remaining = gap - gapBandHeight;
    const beforeHeight = gap > 0 ? Math.round(remaining * (margin.bottom / gap)) : 0;
    const afterHeight = remaining - beforeHeight;

    const endPart = document.createElement('div');
    Object.assign(endPart.style, {
        height: beforeHeight + 'px',
        background: '#fff',
    });

    const gapLine = document.createElement('div');
    Object.assign(gapLine.style, {
        height: gapBandHeight + 'px',
        background: '#f8f9fa',
        borderTop: '1px solid #dadce0',
        borderBottom: '1px solid #dadce0',
        boxSizing: 'border-box',
    });

    const startPart = document.createElement('div');
    Object.assign(startPart.style, {
        height: afterHeight + 'px',
        background: '#fff',
    });

    spacer.appendChild(endPart);
    spacer.appendChild(gapLine);
    spacer.appendChild(startPart);
    return spacer;
}

function mergeSplitLists(container) {
    container.querySelectorAll(':scope > [data-page-spacer][data-list-continuation]').forEach((spacer) => {
        const prevList = spacer.previousElementSibling;
        const nextList = spacer.nextElementSibling;
        if (
            prevList && nextList &&
            prevList.tagName === nextList.tagName &&
            (prevList.tagName === 'OL' || prevList.tagName === 'UL')
        ) {
            while (nextList.firstChild) prevList.appendChild(nextList.firstChild);
            nextList.remove();
        }
        spacer.remove();
    });
}

function paginateList(list, containerTop, paddingTop, contentPerPage, gap, margin, startBoundary) {
    let nextBoundary = startBoundary;
    let current = list;

    while (current) {
        const items = Array.from(current.children).filter((el) => el.tagName === 'LI');
        if (items.length === 0) break;

        let splitAt = null;
        for (const li of items) {
            const rect = li.getBoundingClientRect();
            const relTop = rect.top - containerTop - paddingTop;
            const relBottom = relTop + rect.height;

            while (relTop >= nextBoundary + contentPerPage - BOUNDARY_EPS) {
                nextBoundary += contentPerPage + gap;
            }

            if (
                (relBottom > nextBoundary + BOUNDARY_EPS && relTop < nextBoundary - BOUNDARY_EPS) ||
                (relTop >= nextBoundary - BOUNDARY_EPS && relTop < nextBoundary + contentPerPage - BOUNDARY_EPS)
            ) {
                splitAt = li;
                const tallerThanPage = rect.height > contentPerPage;
                nextBoundary += contentPerPage + gap;
                if (!tallerThanPage) {
                    let stillCrossing = true;
                    while (stillCrossing) {
                        const r2 = li.getBoundingClientRect();
                        const rt2 = r2.top - containerTop - paddingTop;
                        const rb2 = rt2 + r2.height;
                        stillCrossing = rb2 > nextBoundary + BOUNDARY_EPS && rt2 < nextBoundary - BOUNDARY_EPS;
                        if (stillCrossing) nextBoundary += contentPerPage + gap;
                    }
                }
                break;
            }
        }

        if (!splitAt) break;

        if (splitAt === items[0]) {
            const spacer = buildSpacerElement(margin, gap);
            current.parentNode.insertBefore(spacer, current);
            continue;
        }

        const newList = document.createElement(current.tagName);
        Array.from(current.attributes).forEach((attr) => newList.setAttribute(attr.name, attr.value));
        if (current.tagName === 'OL') {
            const priorStart = parseInt(current.getAttribute('start') || '1', 10);
            const movedCount = items.indexOf(splitAt);
            newList.setAttribute('start', String(priorStart + movedCount));
        }

        let node = splitAt;
        while (node) {
            const nextNode = node.nextSibling;
            newList.appendChild(node);
            node = nextNode;
        }

        const spacer = buildSpacerElement(margin, gap, { 'data-list-continuation': 'true' });
        current.parentNode.insertBefore(spacer, current.nextSibling);
        current.parentNode.insertBefore(newList, spacer.nextSibling);

        current = newList;
    }

    return { nextBoundary, nextChild: current ? current.nextElementSibling : null };
}

function paginateContainer(container, contentPerPage, gap, margin) {
    const paddingTop = parseFloat(getComputedStyle(container).paddingTop) || 0;
    const containerTop = container.getBoundingClientRect().top;
    let nextBoundary = contentPerPage;
    let child = container.firstElementChild;

    while (child) {
        if (child.tagName === 'OL' || child.tagName === 'UL') {
            const result = paginateList(child, containerTop, paddingTop, contentPerPage, gap, margin, nextBoundary);
            nextBoundary = result.nextBoundary;
            child = result.nextChild;
            continue;
        }

        const rect = child.getBoundingClientRect();
        const relTop = rect.top - containerTop - paddingTop;
        const relBottom = relTop + rect.height;
        const elementTallerThanPage = rect.height > contentPerPage;

        while (relTop >= nextBoundary + contentPerPage - BOUNDARY_EPS) {
            nextBoundary += contentPerPage + gap;
        }

        while (
            (relBottom > nextBoundary + BOUNDARY_EPS && relTop < nextBoundary - BOUNDARY_EPS) ||
            (relTop >= nextBoundary - BOUNDARY_EPS && relTop < nextBoundary + contentPerPage - BOUNDARY_EPS)
        ) {
            const spacer = buildSpacerElement(margin, gap);
            child.parentNode.insertBefore(spacer, child);
            nextBoundary += contentPerPage + gap;
            if (elementTallerThanPage) break;
        }

        child = child.nextElementSibling;
    }
}

export function repaginatePreview(paperEl, size, margin) {
    if (!paperEl) return;
    size = size || PAPER_SIZES['A4'];
    margin = clampMarginToPage(size, margin || DEFAULT_MARGIN);
    const gap = margin.top + margin.bottom;

    const originalZoom = paperEl.style.zoom;
    const originalTransform = paperEl.style.transform;
    paperEl.style.zoom = 1;
    paperEl.style.transform = 'none';

    const currentScrollHeight = paperEl.scrollHeight;
    paperEl.style.minHeight = currentScrollHeight + 'px';

    paperEl.style.width = size.width + 'px';
    paperEl.style.padding = `${margin.top}px ${margin.right}px ${margin.bottom}px ${margin.left}px`;

    mergeSplitLists(paperEl);
    paperEl.querySelectorAll(':scope > [data-page-spacer]').forEach((el) => el.remove());

    if (!paperEl.firstElementChild) {
        paperEl.style.minHeight = size.height + 'px';
        paperEl.style.zoom = originalZoom;
        paperEl.style.transform = originalTransform;
        return;
    }

    const contentPerPage = Math.max(size.height - margin.top - margin.bottom, 1);
    paginateContainer(paperEl, contentPerPage, gap, margin);

    let contentHeight = 0;
    const lastChild = paperEl.lastElementChild;
    if (lastChild) {
        const bodyRect = paperEl.getBoundingClientRect();
        const lastRect = lastChild.getBoundingClientRect();
        const pb = parseFloat(getComputedStyle(paperEl).paddingBottom) || 0;
        contentHeight = (lastRect.bottom - bodyRect.top) + pb;
    }

    let numPages = Math.ceil((contentHeight - 2) / size.height);
    if (numPages < 1) numPages = 1;
    paperEl.style.minHeight = (numPages * size.height) + 'px';

    paperEl.style.zoom = originalZoom;
    paperEl.style.transform = originalTransform;
}

export function readStoredPaper(storageKey) {
    try {
        const raw = localStorage.getItem(storageKey + ':paper');
        if (!raw) return null;
        const data = JSON.parse(raw);
        const size = typeof data.size === 'string' && PAPER_SIZES[data.size]
            ? PAPER_SIZES[data.size]
            : (data.size && data.size.width ? data.size : null);
        const margin = data.margin && data.margin.top != null ? data.margin : null;
        if (!size || !margin) return null;
        return { size, margin };
    } catch (e) {
        return null;
    }
}

export function initPreviewPagination(scopeSelector = '.doku-paper-scope') {
    const scope = typeof scopeSelector === 'string'
        ? document.querySelector(scopeSelector)
        : scopeSelector;
    const paper = scope?.querySelector('.doku-paper');
    if (!paper) return;

    const storageKey = scope.dataset?.liveStorage;
    const stored = storageKey ? readStoredPaper(storageKey) : null;
    let size = stored?.size || null;
    let margin = stored?.margin || null;
    if (!size && scope.dataset.paperSize && PAPER_SIZES[scope.dataset.paperSize]) {
        size = PAPER_SIZES[scope.dataset.paperSize];
    }
    if (!margin && scope.dataset.paperMargin) {
        try {
            const m = JSON.parse(scope.dataset.paperMargin);
            if (m && m.top != null) margin = m;
        } catch (e) { /* ignore */ }
    }
    size = size || PAPER_SIZES['A4'];
    margin = margin || DEFAULT_MARGIN;

    repaginatePreview(paper, size, margin);

    if (document.fonts?.ready) {
        document.fonts.ready
            .then(() => repaginatePreview(paper, size, margin))
            .catch(() => { /* ignore */ });
    }

    const select = scope.querySelector('[data-paper-size-select]');
    if (select) {
        select.value = findPaperKey(size) || 'F4';
        select.addEventListener('change', () => {
            const key = select.value;
            const newSize = PAPER_SIZES[key];
            if (!newSize) return;
            repaginatePreview(paper, newSize, margin);
            if (storageKey) {
                try {
                    localStorage.setItem(storageKey + ':paper', JSON.stringify({ size: key, margin }));
                } catch (e) { /* ignore */ }
            }
        });
    }
}

window.__initPreviewPagination = initPreviewPagination;
