/**
 * GLPI omits .active on sector Dashboard links (not in menu content[]).
 * Core fix belongs in menu.html.twig; this runs in BrandKit-only dev mounts.
 */
(function () {
    const run = function () {
        const path = window.location.pathname || '';
        const m = path.match(/\/front\/dashboard_(assets|helpdesk)\.php$/);
        if (!m) {
            return;
        }
        const tail = 'dashboard_' + m[1] + '.php';
        const root = (typeof CFG_GLPI !== 'undefined' && CFG_GLPI.root_doc) ? CFG_GLPI.root_doc : '';
        const hrefNeedle = root + '/front/' + tail;

        document.querySelectorAll('#navbar-menu a.dropdown-item[href]').forEach(function (link) {
            const href = link.getAttribute('href') || '';
            if (href.indexOf(tail) === -1 && href !== hrefNeedle) {
                return;
            }
            const menu = link.closest('.dropdown-menu');
            if (!menu) {
                return;
            }
            menu.querySelectorAll('.dropdown-item.active').forEach(function (el) {
                if (el !== link) {
                    el.classList.remove('active');
                    el.removeAttribute('aria-current');
                }
            });
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
            const navItem = menu.closest('.nav-item');
            if (navItem) {
                navItem.classList.add('active');
                const toggle = navItem.querySelector('.nav-link');
                if (toggle) {
                    toggle.classList.add('active');
                }
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
