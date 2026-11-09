/**
 * BrandKit Style tab.
 *
 * GLPI 12 loads entity tabs over AJAX *after* plugin footer scripts run, so
 * this file must not require #brandkit-live-preview at parse time. Clicks are
 * delegated on document; preview re-inits on glpi.tab.loaded.
 */
(function () {
    if (window.BrandkitStyle && window.BrandkitStyle.__bound) {
        return;
    }

    const shadows = {
        none: 'none',
        soft: '0 1px 3px rgb(15 23 42 / 0.10)',
        medium: '0 8px 24px rgb(15 23 42 / 0.14)',
        strong: '0 18px 40px rgb(15 23 42 / 0.18)',
        glow: '0 0 0 1px rgb(14 116 144 / 0.35), 0 10px 28px rgb(14 116 144 / 0.35)',
    };

    const readConfig = function () {
        const el = document.getElementById('brandkit-ui-config');
        if (!el) {
            return { previewCss: '', templateUrl: '', canEdit: false };
        }
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            return { previewCss: '', templateUrl: '', canEdit: false };
        }
    };

    const readGallery = function () {
        const el = document.getElementById('brandkit-gallery-json');
        if (!el) {
            return [];
        }
        try {
            const data = JSON.parse(el.textContent);
            return Array.isArray(data) ? data : [];
        } catch (e) {
            return [];
        }
    };

    const findTemplate = function (id) {
        const list = readGallery();
        for (let i = 0; i < list.length; i++) {
            if (list[i] && list[i].id === id) {
                return list[i];
            }
        }
        return null;
    };

    const styleForm = function () {
        const preview = document.getElementById('brandkit-live-preview');
        if (preview) {
            const form = preview.closest('form');
            if (form) {
                return form;
            }
        }
        return document.querySelector('form[action*="brandkit/Branding"]')
            || document.querySelector('form[action*="brandkit"]');
    };

    const namedEls = function (name) {
        const form = styleForm();
        if (!form) {
            return [];
        }
        return Array.prototype.slice.call(form.querySelectorAll('[name="' + name + '"]'));
    };

    const field = function (name) {
        const els = namedEls(name);
        const checkbox = els.find(function (el) { return el.type === 'checkbox'; });
        if (checkbox) {
            return checkbox.checked ? '1' : '0';
        }
        const el = els[0];
        if (!el) {
            return '';
        }
        return el.value;
    };

    const setNamed = function (name, value) {
        const els = namedEls(name);
        if (!els.length) {
            return;
        }
        // GLPI checkboxes are a hidden 0 plus a checkbox 1 with the same name.
        // querySelector hits the hidden input first, so toggling that alone
        // leaves a still-checked box which wins on submit (last value = 1).
        const checkbox = els.find(function (el) { return el.type === 'checkbox'; });
        if (checkbox) {
            checkbox.checked = value === 1 || value === '1' || value === true;
            els.forEach(function (el) {
                if (el.type === 'hidden') {
                    el.value = '0';
                }
            });
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            if (window.jQuery) {
                window.jQuery(checkbox).trigger('change');
            }
            return;
        }
        const el = els[0];
        const str = value === null || value === undefined ? '' : String(value);
        el.value = str;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        if (window.jQuery) {
            const $el = window.jQuery(el);
            $el.val(str).trigger('change');
            if ($el.spectrum) {
                try {
                    $el.spectrum('set', str);
                } catch (e) { /* picker not initialised on this field */ }
            }
        }
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

    const applyPreviewFrom = function (style, colors) {
        const preview = document.getElementById('brandkit-live-preview');
        if (!preview) {
            return;
        }
        style = style || {};
        colors = colors || {};
        const radius = style.radius_cards || style.radius_global || field('style[radius_cards]') || '8';
        const btnR = style.radius_buttons || radius;
        const shadow = shadows[style.shadow_cards || field('style[shadow_cards]')] || shadows.soft;
        const density = style.density || field('style[density]') || 'comfortable';
        const pad = density === 'compact' ? '0.6rem' : density === 'spacious' ? '1.4rem' : '1rem';
        const font = style.font_size || field('style[font_size]') || '14';
        const speed = style.transition_speed || field('style[transition_speed]') || 'normal';
        const disabled = style.disable_animations === 1 || style.disable_animations === '1';
        const duration = disabled || speed === 'instant' ? '0s'
            : (speed === 'fast' ? '120ms' : speed === 'slow' ? '400ms' : '220ms');
        const primary = colors.color_primary || field('color_primary') || '#0e7490';
        const sidebar = colors.color_sidebar || field('color_sidebar') || '#0f172a';
        const header = colors.color_header || field('color_header') || '#0e7490';
        const accent = colors.color_accent || field('color_accent') || '#fe9c5c';

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
        preview.dataset.sidebar = style.sidebar_preset || field('style[sidebar_preset]') || 'default';
        preview.dataset.hover = style.hover_cards || field('style[hover_cards]') || 'none';
        preview.dataset.header = style.header_style || field('style[header_style]') || 'flat';
        preview.dataset.active = style.nav_active_style || field('style[nav_active_style]') || 'background';

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

    const applyPreview = function () {
        applyPreviewFrom({}, {});
    };

    const fillStyle = function (style) {
        if (!style || typeof style !== 'object') {
            return;
        }
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
    };

    const fillColors = function (colors) {
        colors = colors && typeof colors === 'object' ? colors : {};
        ['color_primary', 'color_secondary', 'color_accent', 'color_sidebar', 'color_header',
            'login_background_color', 'dashboard_card_bg', 'dashboard_card_text'].forEach(function (key) {
            setNamed(key, colors[key] || '');
        });
    };

    const tryitStyle = function () {
        return document.getElementById('brandkit-tryit');
    };

    const setTrying = function (on) {
        const btn = document.getElementById('brandkit-try-cancel');
        if (btn) {
            btn.hidden = !on;
        }
        document.documentElement.classList.toggle('brandkit-trying', !!on);
    };

    const clearTryIt = function () {
        const el = tryitStyle();
        if (el && el.parentNode) {
            el.parentNode.removeChild(el);
        }
        setTrying(false);
        applyPreview();
    };

    const injectCss = function (css) {
        let el = tryitStyle();
        if (!el) {
            el = document.createElement('style');
            el.id = 'brandkit-tryit';
            document.head.appendChild(el);
        }
        el.textContent = css;
        setTrying(true);
    };

    const tryTemplate = function (tpl) {
        if (!tpl) {
            return;
        }
        applyPreviewFrom(tpl.style || {}, tpl.colors || {});
        const cfg = readConfig();
        if (!cfg.previewCss || !tpl.id) {
            return;
        }
        const url = cfg.previewCss + (cfg.previewCss.indexOf('?') >= 0 ? '&' : '?') + 'template=' + encodeURIComponent(tpl.id);
        fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/css' } })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('preview css ' + res.status);
                }
                return res.text();
            })
            .then(injectCss)
            .catch(function () { /* keep pane preview even if page CSS fails */ });
    };

    const applyTemplate = function (tpl) {
        if (!tpl) {
            return;
        }
        fillStyle(tpl.style || {});
        fillColors(tpl.colors || {});
        applyPreviewFrom(tpl.style || {}, tpl.colors || {});
        tryTemplate(tpl);
        document.querySelectorAll('.brandkit-gallery-card').forEach(function (card) {
            card.classList.toggle('is-active', card.getAttribute('data-template') === tpl.id);
        });
    };

    const collectCurrent = function () {
        const form = styleForm();
        const style = {};
        const colors = {};
        if (!form) {
            return { style: style, colors: colors };
        }
        form.querySelectorAll('[name^="style["]').forEach(function (el) {
            const name = el.getAttribute('name');
            const m = name && name.match(/^style\[([^\]]+)\](?:\[([^\]]+)\])?$/);
            if (!m) {
                return;
            }
            let value;
            if (el.type === 'checkbox') {
                value = el.checked ? 1 : 0;
            } else {
                value = el.value;
            }
            if (m[2]) {
                if (!style[m[1]] || typeof style[m[1]] !== 'object') {
                    style[m[1]] = {};
                }
                style[m[1]][m[2]] = value;
            } else {
                style[m[1]] = value;
            }
        });
        ['color_primary', 'color_secondary', 'color_accent', 'color_sidebar', 'color_header',
            'login_background_color', 'dashboard_card_bg', 'dashboard_card_text'].forEach(function (key) {
            const v = field(key);
            if (v) {
                colors[key] = v;
            }
        });
        return { style: style, colors: colors };
    };

    const setStatus = function (text, isError) {
        const el = document.getElementById('brandkit-template-status');
        if (!el) {
            if (text) {
                window.alert(text);
            }
            return;
        }
        el.hidden = !text;
        el.textContent = text || '';
        el.classList.toggle('text-danger', !!isError);
        el.classList.toggle('text-muted', !isError);
    };

    const appendCustomCard = function (item) {
        const gallery = document.getElementById('brandkit-gallery');
        if (!gallery || !item || !item.id) {
            return;
        }
        const jsonEl = document.getElementById('brandkit-gallery-json');
        if (jsonEl) {
            const list = readGallery();
            list.push(item);
            jsonEl.textContent = JSON.stringify(list);
        }
        const colors = item.colors || {};
        const style = item.style || {};
        const article = document.createElement('article');
        article.className = 'brandkit-gallery-card is-custom';
        article.setAttribute('data-template', item.id);
        if (item.db_id) {
            article.setAttribute('data-db-id', String(item.db_id));
        }
        article.style.setProperty('--g-primary', colors.color_primary || '#0e7490');
        article.style.setProperty('--g-sidebar', colors.color_sidebar || '#0f172a');
        article.style.setProperty('--g-header', colors.color_header || '#0e7490');
        article.style.setProperty('--g-accent', colors.color_accent || '#fe9c5c');
        article.style.setProperty('--g-radius', (style.radius_cards || 8) + 'px');

        const hit = document.createElement('button');
        hit.type = 'button';
        hit.className = 'brandkit-gallery-hit brandkit-try';
        hit.setAttribute('aria-label', 'Try ' + (item.name || 'template'));

        const thumb = document.createElement('div');
        thumb.className = 'brandkit-gallery-thumb';
        thumb.setAttribute('data-sidebar', style.sidebar_preset || 'default');
        thumb.setAttribute('data-header', style.header_style || 'flat');
        thumb.innerHTML = '<div class="g-side"></div><div class="g-main"><div class="g-head"></div><div class="g-card"></div></div>';

        const name = document.createElement('h3');
        name.className = 'brandkit-gallery-name';
        name.textContent = item.name || 'Custom';
        const desc = document.createElement('p');
        desc.className = 'brandkit-gallery-desc';
        desc.textContent = item.description || '';
        hit.appendChild(thumb);
        hit.appendChild(name);
        hit.appendChild(desc);

        const actions = document.createElement('div');
        actions.className = 'brandkit-gallery-actions';
        const tryBtn = document.createElement('button');
        tryBtn.type = 'button';
        tryBtn.className = 'btn btn-sm btn-outline-primary brandkit-try';
        tryBtn.setAttribute('data-template', item.id);
        tryBtn.textContent = 'Try it';
        const applyBtn = document.createElement('button');
        applyBtn.type = 'button';
        applyBtn.className = 'btn btn-sm btn-primary brandkit-apply';
        applyBtn.setAttribute('data-template', item.id);
        applyBtn.textContent = 'Apply';
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'btn btn-sm btn-outline-danger brandkit-delete';
        delBtn.setAttribute('data-db-id', String(item.db_id || ''));
        delBtn.textContent = 'Delete';
        actions.appendChild(tryBtn);
        actions.appendChild(applyBtn);
        actions.appendChild(delBtn);

        article.appendChild(hit);
        article.appendChild(actions);
        gallery.appendChild(article);
    };

    const saveTemplate = function () {
        const cfg = readConfig();
        if (!cfg.templateUrl) {
            return;
        }
        const form = styleForm();
        const nameEl = form && form.querySelector('[name="template_name"]');
        const descEl = form && form.querySelector('[name="template_description"]');
        const name = (nameEl && nameEl.value) || '';
        const description = (descEl && descEl.value) || '';
        const current = collectCurrent();
        const entities = (form && form.querySelector('[name="entities_id"]') || {}).value || '0';
        fetch(cfg.templateUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                action: 'save',
                name: name,
                description: description,
                entities_id: parseInt(entities, 10) || 0,
                style: current.style,
                colors: current.colors,
            }),
        }).then(function (res) { return res.json(); }).then(function (data) {
            if (!data || !data.ok) {
                setStatus((data && data.error) || 'Unable to save template', true);
                return;
            }
            if (nameEl) {
                nameEl.value = '';
            }
            if (descEl) {
                descEl.value = '';
            }
            appendCustomCard(data.item);
            setStatus('Saved “' + (data.item && data.item.name ? data.item.name : 'template') + '” to the gallery. Entity Save still needed to persist this look.', false);
        }).catch(function () {
            setStatus('Unable to save template', true);
        });
    };

    const deleteTemplate = function (id) {
        const cfg = readConfig();
        if (!cfg.templateUrl || !id) {
            return;
        }
        if (!window.confirm('Delete this custom template?')) {
            return;
        }
        const body = new URLSearchParams();
        body.set('action', 'delete');
        body.set('id', String(id));
        fetch(cfg.templateUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
            body: body.toString(),
        }).then(function (res) { return res.json(); }).then(function (data) {
            if (data && data.ok) {
                const card = document.querySelector('.brandkit-gallery-card[data-db-id="' + id + '"]');
                if (card) {
                    card.remove();
                }
            }
        });
    };

    const onClick = function (ev) {
        const cancel = ev.target.closest('#brandkit-try-cancel');
        if (cancel) {
            ev.preventDefault();
            clearTryIt();
            return;
        }
        const saveBtn = ev.target.closest('#brandkit-save-template-btn');
        if (saveBtn) {
            ev.preventDefault();
            saveTemplate();
            return;
        }
        const del = ev.target.closest('.brandkit-delete');
        if (del) {
            ev.preventDefault();
            deleteTemplate(del.getAttribute('data-db-id'));
            return;
        }
        const apply = ev.target.closest('.brandkit-apply, .brandkit-apply-preset');
        if (apply) {
            ev.preventDefault();
            const id = apply.getAttribute('data-template') || apply.getAttribute('data-preset');
            applyTemplate(findTemplate(id));
            return;
        }
        const tryBtn = ev.target.closest('.brandkit-try');
        if (tryBtn) {
            ev.preventDefault();
            const card = tryBtn.closest('[data-template]');
            const id = tryBtn.getAttribute('data-template') || (card && card.getAttribute('data-template'));
            tryTemplate(findTemplate(id));
        }
    };

    const bindForm = function () {
        const form = styleForm();
        if (!form || form.dataset.brandkitBound === '1') {
            return;
        }
        form.dataset.brandkitBound = '1';
        form.addEventListener('input', applyPreview);
        form.addEventListener('change', applyPreview);
        applyPreview();
    };

    document.addEventListener('click', onClick, true);

    if (window.jQuery) {
        window.jQuery(document).on('glpi.tab.loaded', bindForm);
    }
    document.addEventListener('DOMContentLoaded', bindForm);
    bindForm();

    const obs = new MutationObserver(function () {
        if (document.getElementById('brandkit-live-preview')) {
            bindForm();
        }
    });
    obs.observe(document.documentElement, { childList: true, subtree: true });

    window.BrandkitStyle = {
        __bound: true,
        applyPreview: applyPreview,
        applyTemplate: applyTemplate,
        tryTemplate: tryTemplate,
        clearTryIt: clearTryIt,
    };
})();
