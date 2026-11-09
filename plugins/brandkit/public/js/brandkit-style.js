/**
 * BrandKit Style tab: live preview, presets, contrast hint.
 */
(function () {
    const preview = document.getElementById('brandkit-live-preview');
    if (!preview) {
        return;
    }

    const form = preview.closest('form') || document.querySelector('form[action*="brandkit"]');
    if (!form) {
        return;
    }

    const shadows = {
        none: 'none',
        soft: '0 1px 3px rgb(15 23 42 / 0.10)',
        medium: '0 8px 24px rgb(15 23 42 / 0.14)',
        strong: '0 18px 40px rgb(15 23 42 / 0.18)',
        glow: '0 0 0 1px rgb(14 116 144 / 0.35), 0 10px 28px rgb(14 116 144 / 0.35)',
    };

    const field = function (name) {
        const el = form.querySelector('[name="' + name + '"]');
        if (!el) {
            return '';
        }
        if (el.type === 'checkbox') {
            return el.checked ? '1' : '0';
        }
        return el.value;
    };

    const hexLum = function (hex) {
        if (!hex || !/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(hex)) {
            return null;
        }
        let h = hex.slice(1);
        if (h.length === 3) {
            h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
        }
        const r = parseInt(h.slice(0, 2), 16) / 255;
        const g = parseInt(h.slice(2, 4), 16) / 255;
        const b = parseInt(h.slice(4, 6), 16) / 255;
        const lin = function (c) {
            return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        };
        return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
    };

    const contrastRatio = function (a, b) {
        const la = hexLum(a);
        const lb = hexLum(b);
        if (la === null || lb === null) {
            return null;
        }
        const hi = Math.max(la, lb);
        const lo = Math.min(la, lb);
        return (hi + 0.05) / (lo + 0.05);
    };

    const px = function (n) {
        return String(n) === '999' ? '999px' : n + 'px';
    };

    const applyPreview = function () {
        const radius = field('style[radius_cards]') || field('style[radius_global]') || '8';
        const btnR = field('style[radius_buttons]') || radius;
        const shadow = shadows[field('style[shadow_cards]')] || shadows.soft;
        const density = field('style[density]') || 'comfortable';
        const pad = density === 'compact' ? '0.6rem' : density === 'spacious' ? '1.4rem' : '1rem';
        const font = field('style[font_size]') || '14';
        const speed = field('style[transition_speed]') || 'normal';
        const duration = field('style[disable_animations]') === '1' || speed === 'instant'
            ? '0s'
            : (speed === 'fast' ? '120ms' : speed === 'slow' ? '400ms' : '220ms');
        const primary = field('color_primary') || '#0e7490';
        const sidebar = field('color_sidebar') || '#0f172a';
        const header = field('color_header') || '#0e7490';
        const accent = field('color_accent') || '#fe9c5c';

        preview.style.setProperty('--p-radius', px(radius));
        preview.style.setProperty('--p-btn-radius', px(btnR));
        preview.style.setProperty('--p-shadow', shadow);
        preview.style.setProperty('--p-pad', pad);
        preview.style.setProperty('--p-font', font + 'px');
        preview.style.setProperty('--p-duration', duration);
        preview.style.setProperty('--p-primary', primary);
        preview.style.setProperty('--p-sidebar', sidebar);
        preview.style.setProperty('--p-header', header);
        preview.style.setProperty('--p-accent', accent);

        preview.dataset.sidebar = field('style[sidebar_preset]') || 'default';
        preview.dataset.hover = field('style[hover_cards]') || 'none';
        preview.dataset.header = field('style[header_style]') || 'flat';
        preview.dataset.active = field('style[nav_active_style]') || 'background';

        const warn = document.getElementById('brandkit-contrast-warn');
        if (warn) {
            const ratio = contrastRatio(primary, '#ffffff');
            if (ratio !== null && ratio < 3) {
                warn.textContent = 'Primary colour contrast against white is low (' + ratio.toFixed(1) + ':1). Consider a darker primary for buttons.';
                warn.classList.remove('d-none');
            } else {
                warn.classList.add('d-none');
            }
        }
    };

    const fillStyle = function (style) {
        Object.keys(style).forEach(function (key) {
            const value = style[key];
            if (value && typeof value === 'object' && !Array.isArray(value)) {
                Object.keys(value).forEach(function (sub) {
                    setNamed('style[' + key + '][' + sub + ']', value[sub]);
                });
                return;
            }
            setNamed('style[' + key + ']', value);
        });
        applyPreview();
    };

    const setNamed = function (name, value) {
        const el = form.querySelector('[name="' + name + '"]');
        if (!el) {
            return;
        }
        if (el.type === 'checkbox') {
            el.checked = value === 1 || value === '1' || value === true;
            el.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }
        el.value = value;
        el.dispatchEvent(new Event('change', { bubbles: true }));
        if (window.jQuery) {
            window.jQuery(el).trigger('change');
        }
    };

    form.addEventListener('input', applyPreview);
    form.addEventListener('change', applyPreview);

    document.querySelectorAll('.brandkit-apply-preset').forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            const json = document.getElementById('brandkit-preset-json');
            if (!json) {
                return;
            }
            let presets;
            try {
                presets = JSON.parse(json.textContent);
            } catch (e) {
                return;
            }
            const key = btn.getAttribute('data-preset');
            if (presets[key] && presets[key].style) {
                fillStyle(presets[key].style);
            }
        });
    });

    applyPreview();
})();
