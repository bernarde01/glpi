/**
 * List views: status/priority pills + entity badges on tab datatables.
 */
(function () {
    if (window.BrandkitListStatus) {
        return;
    }
    window.BrandkitListStatus = true;

    const listTableSelector = 'table.search-results, body.central [data-glpi-tab-content] table.table';

    const collectListTables = function (scope) {
        const isDoc = scope === document;
        const root = isDoc ? document : scope;
        const tables = [];
        if (!root || !root.querySelectorAll) {
            return tables;
        }
        if (root.matches && root.matches('table')) {
            if (root.classList.contains('search-results')
                || (root.classList.contains('table') && root.closest('[data-glpi-tab-content]'))) {
                tables.push(root);
            }
        }
        root.querySelectorAll(listTableSelector).forEach(function (table) {
            tables.push(table);
        });
        return tables.filter(function (table, index, all) {
            return all.indexOf(table) === index;
        });
    };

    const extractLabel = function (span) {
        const hidden = span.querySelector('.visually-hidden');
        if (hidden) {
            const fromHidden = (hidden.textContent || '').replace(/\s+/g, ' ').trim();
            if (fromHidden) {
                return fromHidden;
            }
        }
        const statusIcon = span.querySelector('i.itilstatus');
        if (statusIcon && statusIcon.getAttribute('title')) {
            const fromTitle = statusIcon.getAttribute('title').replace(/\s+/g, ' ').trim();
            if (fromTitle) {
                return fromTitle;
            }
        }

        let label = '';
        span.childNodes.forEach(function (node) {
            if (node.nodeType === Node.TEXT_NODE) {
                label += node.textContent;
            }
        });
        label = label.replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
        if (!label) {
            const clone = span.cloneNode(true);
            clone.querySelectorAll('.itilstatus, .visually-hidden').forEach(function (el) {
                el.remove();
            });
            label = (clone.textContent || '').replace(/\s+/g, ' ').trim();
        }
        return label;
    };

    const escapeHtml = function (text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    const isEntityColumnHeader = function (label) {
        const t = label.replace(/\s+/g, ' ').trim();
        return t === 'Entity'
            || t === 'Entities'
            || /^entit(y|ies|é|e)/i.test(t);
    };

    const buildEntityBadgeHtml = function (text) {
        const names = text.split(/\s*>\s*/).filter(function (part) {
            return part.length > 0;
        });
        if (!names.length) {
            return '';
        }
        const breadcrumbs = names.map(function (name) {
            return '<span class="text-nowrap">' + escapeHtml(name) + '</span>';
        }).join('<i class="ti ti-caret-right-filled mx-1" aria-hidden="true"></i>');

        return '<span class="glpi-badge" title="' + escapeHtml(text) + '">' + breadcrumbs + '</span>';
    };

    const decorateEntityCells = function (table) {
        const headerRow = table.querySelector('thead tr:last-of-type') || table.querySelector('thead tr');
        if (!headerRow) {
            return;
        }
        const headers = headerRow.querySelectorAll('th');
        let entityIndex = -1;
        headers.forEach(function (th, index) {
            if (isEntityColumnHeader(th.textContent || '')) {
                entityIndex = index;
            }
        });
        if (entityIndex < 0) {
            return;
        }

        table.querySelectorAll('tbody tr').forEach(function (row) {
            const cells = row.querySelectorAll('td');
            const cell = cells[entityIndex];
            if (!cell || cell.dataset.brandkitEntity === '1' || cell.querySelector('.glpi-badge')) {
                return;
            }
            const text = (cell.textContent || '').replace(/\s+/g, ' ').trim();
            if (!text) {
                return;
            }
            cell.innerHTML = buildEntityBadgeHtml(text);
            cell.dataset.brandkitEntity = '1';
        });
    };

    const splitStatusLabel = function (label) {
        const match = label.match(/^(.+?)\s+(\([^)]+\))$/);
        if (match) {
            return { title: match[1], detail: match[2] };
        }
        return { title: label, detail: '' };
    };

    const buildPill = function (accent, parts, iconHost, singleLine) {
        const pill = document.createElement('span');
        pill.className = 'brandkit-status-pill' + (singleLine ? ' brandkit-status-pill--single' : '');
        pill.style.setProperty('--brandkit-status-accent', accent);

        const tile = document.createElement('span');
        tile.className = 'brandkit-status-pill__tile';
        tile.setAttribute('aria-hidden', 'true');

        const content = document.createElement('span');
        content.className = 'brandkit-status-pill__content';

        const body = document.createElement('span');
        body.className = 'brandkit-status-pill__body';

        const titleEl = document.createElement('span');
        titleEl.className = 'brandkit-status-pill__title';
        titleEl.textContent = parts.title;
        body.appendChild(titleEl);

        if (parts.detail) {
            const detailEl = document.createElement('span');
            detailEl.className = 'brandkit-status-pill__detail';
            detailEl.textContent = parts.detail;
            body.appendChild(detailEl);
        }

        if (iconHost) {
            content.appendChild(iconHost);
        }
        content.appendChild(body);
        pill.appendChild(tile);
        pill.appendChild(content);

        return pill;
    };

    const decorateSpan = function (span) {
        if (!span || span.dataset.brandkitListPill === '1') {
            return;
        }
        if (span.querySelector('.brandkit-status-pill')) {
            span.dataset.brandkitListPill = '1';
            return;
        }
        const icon = span.querySelector('i.itilstatus');
        if (!icon || icon.closest('.brandkit-status-pill')) {
            return;
        }

        const label = extractLabel(span);
        if (!label) {
            return;
        }

        const parts = splitStatusLabel(label);
        const accent = window.getComputedStyle(icon).color || 'currentColor';
        const iconHost = icon.parentElement && icon.parentElement !== span
            ? icon.parentElement
            : icon;

        const pill = buildPill(accent, parts, iconHost, false);

        while (span.firstChild) {
            span.removeChild(span.firstChild);
        }
        span.classList.remove('text-nowrap');
        span.classList.add('brandkit-status-cell');
        span.appendChild(pill);
        span.dataset.brandkitListPill = '1';
    };

    const decorateBadgeBlock = function (block) {
        if (block.dataset.brandkitListPill === '1') {
            return;
        }
        const colorTile = block.querySelector(':scope > span');
        if (!colorTile) {
            return;
        }

        const accent = window.getComputedStyle(colorTile).backgroundColor
            || window.getComputedStyle(block).borderColor
            || 'currentColor';

        const label = (block.textContent || '').replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
        if (!label) {
            return;
        }

        const pill = buildPill(accent, { title: label, detail: '' }, null, true);

        block.classList.add('brandkit-list-pill-host');
        block.removeAttribute('style');
        while (block.firstChild) {
            block.removeChild(block.firstChild);
        }
        block.appendChild(pill);
        block.dataset.brandkitListPill = '1';
    };

    const decorateTimers = {};

    const scheduleDecorate = function (root) {
        const scope = root && (root.querySelectorAll || root === document) ? root : document;
        const key = scope === document ? 'document' : (scope.dataset.brandkitDecorateKey
            || (scope.dataset.brandkitDecorateKey = 'bk-' + String(Math.random()).slice(2)));
        window.clearTimeout(decorateTimers[key]);
        decorateTimers[key] = window.setTimeout(function () {
            decorateSearchListPills(scope);
        }, 50);
    };

    const decorateSearchListPills = function (root) {
        const scope = root && (root.querySelectorAll || root === document) ? root : document;
        collectListTables(scope).forEach(function (table) {
            table.querySelectorAll('td span.text-nowrap, td span.brandkit-status-cell').forEach(function (span) {
                if (span.dataset.brandkitListPill === '1') {
                    return;
                }
                decorateSpan(span);
            });
            table.querySelectorAll('td i.itilstatus').forEach(function (icon) {
                if (icon.closest('.brandkit-status-pill, [data-brandkit-list-pill="1"]')) {
                    return;
                }
                const host = icon.closest('td span.text-nowrap, td span.brandkit-status-cell, td > span');
                if (host) {
                    decorateSpan(host);
                }
            });
            table.querySelectorAll('td .badge_block').forEach(function (block) {
                if (block.closest('.brandkit-status-pill')) {
                    return;
                }
                decorateBadgeBlock(block);
            });
            decorateEntityCells(table);
        });
    };

    const bind = function () {
        decorateSearchListPills(document);

        document.querySelectorAll(
            '.search-container, .ajax-container.search-display-data, .search-card, [data-glpi-tab-content]'
        ).forEach(function (node) {
            if (node.dataset.brandkitStatusObserver === '1') {
                return;
            }
            node.dataset.brandkitStatusObserver = '1';
            new MutationObserver(function () {
                scheduleDecorate(node);
            }).observe(node, { childList: true, subtree: true });
        });

        if (typeof $ !== 'undefined') {
            $(document).on('search_refresh', 'table.search-results', function () {
                decorateSearchListPills(this);
            });
            $(document).ajaxComplete(function () {
                scheduleDecorate(document);
            });
        }
        if (typeof $ !== 'undefined') {
            $(document).on('glpi.tab.loaded', function () {
                scheduleDecorate(document);
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
    window.addEventListener('load', function () {
        scheduleDecorate(document);
    });
})();
