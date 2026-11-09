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

    const run = function () {
        applyIcon();
        applyTitlePrefix();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
