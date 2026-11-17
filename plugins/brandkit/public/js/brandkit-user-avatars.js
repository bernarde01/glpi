/**
 * User initials: pastel tile background + darker same-hue text (GLPI hash colours).
 */
(function () {
    if (window.BrandkitUserAvatars) {
        return;
    }
    window.BrandkitUserAvatars = true;

    const selector = 'body.central #itil-object-container .avatar.rounded, '
        + 'body.central table.search-results .avatar.rounded, '
        + 'body.central .asset .avatar.rounded';

    const parseRgb = function (str) {
        if (!str) {
            return null;
        }
        const hex = str.trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (hex) {
            let h = hex[1];
            if (h.length === 3) {
                h = h.split('').map(function (c) { return c + c; }).join('');
            }
            return {
                r: parseInt(h.slice(0, 2), 16),
                g: parseInt(h.slice(2, 4), 16),
                b: parseInt(h.slice(4, 6), 16),
            };
        }
        const m = str.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
        if (!m) {
            return null;
        }
        return { r: +m[1], g: +m[2], b: +m[3] };
    };

    const mix = function (a, b, t) {
        return {
            r: Math.round(a.r * (1 - t) + b.r * t),
            g: Math.round(a.g * (1 - t) + b.g * t),
            b: Math.round(a.b * (1 - t) + b.b * t),
        };
    };

    const toCss = function (rgb) {
        return 'rgb(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ')';
    };

    const decorateAvatar = function (el) {
        if (el.dataset.brandkitAvatar === '1') {
            return;
        }
        const bgImage = el.style.backgroundImage || window.getComputedStyle(el).backgroundImage;
        const hasPhoto = bgImage && bgImage !== 'none';

        let base = parseRgb(el.style.backgroundColor);
        if (!base || el.style.backgroundColor === 'inherit') {
            base = parseRgb(window.getComputedStyle(el).backgroundColor);
        }
        if (!base || hasPhoto) {
            if (hasPhoto) {
                el.dataset.brandkitAvatar = '1';
            }
            return;
        }

        const pastel = mix(base, { r: 255, g: 255, b: 255 }, 0.68);
        const ink = mix(base, { r: 0, g: 0, b: 0 }, 0.42);

        el.style.backgroundColor = toCss(pastel);
        el.style.color = toCss(ink);
        el.dataset.brandkitAvatar = '1';
    };

    const decorateAll = function (root) {
        const scope = root && root.querySelectorAll ? root : document;
        if (scope.matches && scope.matches('.avatar.rounded')) {
            decorateAvatar(scope);
            return;
        }
        scope.querySelectorAll(selector).forEach(function (el) {
            if (el.closest('header.navbar')) {
                return;
            }
            decorateAvatar(el);
        });
    };

    const observe = function () {
        const itil = document.getElementById('itil-object-container');
        if (itil && itil.dataset.brandkitAvatarObserver !== '1') {
            itil.dataset.brandkitAvatarObserver = '1';
            new MutationObserver(function () {
                window.setTimeout(function () { decorateAll(itil); }, 0);
            }).observe(itil, { childList: true, subtree: true });
        }

        const search = document.querySelector('.search-container');
        if (search && search.dataset.brandkitAvatarObserver !== '1') {
            search.dataset.brandkitAvatarObserver = '1';
            new MutationObserver(function () {
                window.setTimeout(function () { decorateAll(search); }, 0);
            }).observe(search, { childList: true, subtree: true });
        }
    };

    const boot = function () {
        if (!document.body.classList.contains('central')) {
            return;
        }
        decorateAll(document);
        observe();
    };

    if (typeof $ !== 'undefined') {
        $(document).ajaxComplete(function () {
            window.setTimeout(boot, 0);
        });
    }
    document.addEventListener('glpi.tab.loaded', function () {
        window.setTimeout(boot, 0);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
    window.addEventListener('load', function () {
        window.setTimeout(boot, 0);
    });
})();
