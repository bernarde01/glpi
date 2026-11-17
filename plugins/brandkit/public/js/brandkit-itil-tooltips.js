/**
 * ITIL ticket footer: move panel-width tooltips off the wrapper span (core GLPI quirk).
 */
(function () {
    if (window.BrandkitItilTooltipCleanup) {
        return;
    }

    const start = function () {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
            return false;
        }
        if (window.BrandkitItilTooltipCleanup) {
            return true;
        }
        window.BrandkitItilTooltipCleanup = true;

        const footerSwitchSpan = function () {
            return document.querySelector('.itil-footer .form-buttons > span:first-child');
        };

        const purgeOrphanTooltips = function () {
            document.querySelectorAll('body > .tooltip').forEach(function (node) {
                node.remove();
            });
        };

        const hideItilPanelTooltips = function () {
            document.querySelectorAll(
                '.itil-footer .form-buttons > span:first-child, .itil-footer .switch-panel-width, .itil-footer .collapse-panel'
            ).forEach(function (el) {
                const instance = bootstrap.Tooltip.getInstance(el);
                if (instance) {
                    instance.hide();
                }
            });
            purgeOrphanTooltips();
        };

        const moveFooterTooltipsOntoButtons = function () {
            const span = footerSwitchSpan();
            if (!span || span.dataset.brandkitTooltipsMoved === '1') {
                return;
            }

            const widthTitle = span.getAttribute('title')
                || span.getAttribute('data-bs-original-title')
                || '';
            const spanTip = bootstrap.Tooltip.getInstance(span);
            if (spanTip) {
                spanTip.dispose();
            }

            span.removeAttribute('data-bs-toggle');
            span.removeAttribute('data-bs-placement');
            span.removeAttribute('title');
            span.removeAttribute('data-bs-original-title');

            const switchBtn = span.querySelector('.switch-panel-width');
            const collapseBtn = span.querySelector('.collapse-panel');
            if (switchBtn && widthTitle) {
                switchBtn.setAttribute('data-bs-toggle', 'tooltip');
                switchBtn.setAttribute('data-bs-placement', 'top');
                switchBtn.setAttribute('title', widthTitle);
            }
            if (collapseBtn) {
                const collapseTitle = collapseBtn.getAttribute('aria-label') || '';
                if (collapseTitle) {
                    collapseBtn.setAttribute('data-bs-toggle', 'tooltip');
                    collapseBtn.setAttribute('data-bs-placement', 'top');
                    collapseBtn.setAttribute('title', collapseTitle);
                }
            }

            span.dataset.brandkitTooltipsMoved = '1';
            const initFn = typeof window.initTooltips === 'function'
                ? window.initTooltips
                : (typeof initTooltips === 'function' ? initTooltips : null);
            if (initFn) {
                initFn(span);
            } else {
                [switchBtn, collapseBtn].forEach(function (btn) {
                    if (!btn || !btn.hasAttribute('data-bs-toggle')) {
                        return;
                    }
                    if (!bootstrap.Tooltip.getInstance(btn)) {
                        new bootstrap.Tooltip(btn, {
                            delay: { show: 50, hide: 50 },
                            placement: 'top',
                        });
                    }
                });
            }
        };

        const guardCollapsedTooltips = function (e) {
            const container = document.getElementById('itil-object-container');
            if (container && container.classList.contains('right-collapsed')) {
                e.preventDefault();
                hideItilPanelTooltips();
                return;
            }
            const el = e.target;
            if (el && el.classList
                && (el.classList.contains('switch-panel-width') || el.classList.contains('collapse-panel'))) {
                const style = window.getComputedStyle(el);
                if (style.display === 'none' || style.visibility === 'hidden') {
                    e.preventDefault();
                    hideItilPanelTooltips();
                }
            }
        };

        document.addEventListener('show.bs.tooltip', guardCollapsedTooltips, true);
        document.addEventListener('click', function (ev) {
            const btn = ev.target && ev.target.closest
                ? ev.target.closest('.collapse-panel, .switch-panel-width')
                : null;
            if (btn) {
                window.setTimeout(hideItilPanelTooltips, 0);
            }
        }, true);

        const containerNode = document.getElementById('itil-object-container');
        if (containerNode) {
            new MutationObserver(function () {
                if (containerNode.classList.contains('right-collapsed')) {
                    hideItilPanelTooltips();
                }
            }).observe(containerNode, { attributes: true, attributeFilter: ['class'] });
        }

        document.addEventListener('scroll', hideItilPanelTooltips, true);
        document.addEventListener('pointerdown', function (ev) {
            if (!document.querySelector('body > .tooltip.show')) {
                return;
            }
            const trigger = ev.target && ev.target.closest
                ? ev.target.closest('.itil-footer [data-bs-toggle="tooltip"]')
                : null;
            if (!trigger) {
                hideItilPanelTooltips();
            }
        }, true);

        const boot = function () {
            moveFooterTooltipsOntoButtons();
            hideItilPanelTooltips();
        };

        boot();
        window.setTimeout(boot, 100);
        window.setTimeout(boot, 350);
        if (typeof $ !== 'undefined') {
            $(document).ajaxComplete(function () {
                window.setTimeout(boot, 0);
            });
        }
        document.addEventListener('glpi.tab.loaded', function () {
            window.setTimeout(boot, 0);
        });

        return true;
    };

    const schedule = function () {
        if (start()) {
            return;
        }
        [50, 200, 500, 1200].forEach(function (ms) {
            window.setTimeout(start, ms);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', schedule);
    } else {
        schedule();
    }
    window.addEventListener('load', schedule);
})();
