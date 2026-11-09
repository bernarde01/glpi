/**
 * BrandKit: replace the hardcoded GLPI favicon and optional title prefix.
 * Core still emits a shortcut icon after plugin header tags, so we rewrite
 * document.head after DOMContentLoaded.
 */
(function () {
    const applyIcon = function () {
        const meta = document.querySelector('meta[name="brandkit-icon"]');
        if (!meta || !meta.getAttribute('content')) {
            return;
        }
        const href = meta.getAttribute('content');
        document.querySelectorAll('link[rel="shortcut icon"], link[rel="icon"]').forEach(function (el) {
            el.parentNode.removeChild(el);
        });
        const link = document.createElement('link');
        link.setAttribute('rel', 'icon');
        link.setAttribute('href', href);
        document.head.appendChild(link);
    };

    const applyTitlePrefix = function () {
        const meta = document.querySelector('meta[name="brandkit-title-prefix"]');
        if (!meta) {
            return;
        }
        const prefix = (meta.getAttribute('content') || '').trim();
        if (!prefix) {
            return;
        }
        if (document.title.indexOf(prefix) !== 0) {
            document.title = prefix + ' ' + document.title;
        }
    };

    const applyNoMotion = function () {
        if (document.querySelector('meta[name="brandkit-no-motion"]')) {
            document.documentElement.classList.add('brandkit-no-motion');
        }
    };

    const applyWatermark = function () {
        if (document.querySelector('.brandkit-watermark')) {
            return;
        }
        const meta = document.querySelector('meta[name="brandkit-watermark"]');
        if (!meta || !meta.getAttribute('content') || meta.getAttribute('content') === 'none') {
            return;
        }
        const el = document.createElement('div');
        el.className = 'brandkit-watermark';
        el.setAttribute('aria-hidden', 'true');
        el.dataset.kind = meta.getAttribute('content');
        document.body.appendChild(el);
    };

    const bindFileNames = function (root) {
        (root || document).querySelectorAll('.brandkit-file-input').forEach(function (input) {
            if (input.dataset.brandkitBound) {
                return;
            }
            input.dataset.brandkitBound = '1';
            input.addEventListener('change', function () {
                const nameEl = input.closest('.brandkit-file')
                    && input.closest('.brandkit-file').querySelector('.brandkit-file-name');
                if (!nameEl) {
                    return;
                }
                const empty = nameEl.getAttribute('data-empty') || '';
                nameEl.textContent = (input.files && input.files[0] && input.files[0].name) ? input.files[0].name : empty;
            });
        });
    };

    const run = function () {
        applyIcon();
        applyTitlePrefix();
        applyNoMotion();
        applyWatermark();
        bindFileNames(document);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
    document.addEventListener('glpi.tab.loaded', function (ev) {
        bindFileNames((ev && ev.detail && ev.detail.tab) || document);
    });
})();
