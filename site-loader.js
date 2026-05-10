/**
 * Dr. Ahmed Zaki - Site Loader
 * Applies admin panel settings to the live website
 * Include on every page: <script src="/site-loader.js" defer></script>
 */
(function() {
    'use strict';

    const API_URL = '/admin/api/site-config.php';
    const CACHE_KEY = 'drazaki_site_config';
    const CACHE_TTL = 60 * 1000;

    // Use cached config for instant first paint
    let cached = null;
    try {
        const raw = sessionStorage.getItem(CACHE_KEY);
        if (raw) {
            const p = JSON.parse(raw);
            if (Date.now() - p.cachedAt < CACHE_TTL) cached = p.data;
        }
    } catch (e) {}
    if (cached) applyConfig(cached);

    // Fetch fresh config from server
    fetch(API_URL, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                try {
                    sessionStorage.setItem(CACHE_KEY, JSON.stringify({
                        cachedAt: Date.now(), data: data
                    }));
                } catch (e) {}
                applyConfig(data);
            }
        })
        .catch(err => console.warn('[site-loader]', err));

    function applyConfig(data) {
        const settings = data.settings || {};
        const replacements = data.image_replacements || {};
        applyImageReplacements(replacements);
        applyLogo(settings);
        applyFavicon(settings);
        applyTopBar(settings);
        applyPrimaryColor(settings);
        applyWhatsApp(settings);
        applySocialLinks(settings);
        applyContactInfo(settings);
    }

    function applyImageReplacements(replacements) {
        if (!replacements || typeof replacements !== 'object') return;
        const keys = Object.keys(replacements);
        if (!keys.length) return;

        document.querySelectorAll('img').forEach(img => {
            replaceAttr(img, 'src', replacements);
            replaceAttr(img, 'data-src', replacements);
            replaceSrcset(img, 'srcset', replacements);
            replaceSrcset(img, 'data-srcset', replacements);
        });
        document.querySelectorAll('source').forEach(src => {
            replaceSrcset(src, 'srcset', replacements);
            replaceSrcset(src, 'data-srcset', replacements);
        });
        document.querySelectorAll('[style*="url("]').forEach(el => {
            const style = el.getAttribute('style');
            let newStyle = style;
            keys.forEach(orig => {
                if (style.indexOf(orig) !== -1) {
                    newStyle = newStyle.split(orig).join(replacements[orig]);
                }
            });
            if (newStyle !== style) el.setAttribute('style', newStyle);
        });
        document.querySelectorAll('meta[property="og:image"], meta[name="twitter:image"]').forEach(m => {
            const v = m.getAttribute('content');
            const norm = normalizeUrl(v);
            if (norm && replacements[norm]) m.setAttribute('content', replacements[norm]);
        });
    }

    function replaceAttr(el, attr, replacements) {
        const v = el.getAttribute(attr);
        if (!v) return;
        const norm = normalizeUrl(v);
        if (replacements[norm]) el.setAttribute(attr, replacements[norm]);
    }

    function replaceSrcset(el, attr, replacements) {
        const v = el.getAttribute(attr);
        if (!v) return;
        const parts = v.split(',').map(part => {
            const trimmed = part.trim();
            const segs = trimmed.split(/\s+/);
            const url = segs[0];
            const size = segs.slice(1).join(' ');
            const norm = normalizeUrl(url);
            if (replacements[norm]) {
                return replacements[norm] + (size ? ' ' + size : '');
            }
            return trimmed;
        });
        el.setAttribute(attr, parts.join(', '));
    }

    function normalizeUrl(url) {
        if (!url) return '';
        try {
            const u = url.replace(/^https?:\/\/[^/]+/, '');
            return u.charAt(0) === '/' ? u : '/' + u;
        } catch (e) { return url; }
    }

    function applyLogo(s) {
        if (!s.logo_url) return;
        const selectors = [
            'img[src*="P-1"]',
            'img[src*="logo"]',
            'img[src*="Logo"]',
            '.logo img',
            '.site-logo img',
            '.custom-logo',
            '.brand img',
            'header img[alt*="logo" i]',
            'header img[alt*="Dr" i]',
            'a[href="index.htm"] img',
            'a[href="/"] img'
        ];
        const seen = new Set();
        selectors.forEach(sel => {
            try {
                document.querySelectorAll(sel).forEach(img => {
                    if (seen.has(img)) return;
                    seen.add(img);
                    img.src = s.logo_url;
                    img.removeAttribute('srcset');
                    if (s.logo_alt) img.alt = s.logo_alt;
                });
            } catch (e) {}
        });
    }

    function applyFavicon(s) {
        if (!s.favicon_url) return;
        document.querySelectorAll('link[rel*="icon"]').forEach(l => l.remove());
        const link = document.createElement('link');
        link.rel = 'icon';
        link.href = s.favicon_url;
        document.head.appendChild(link);
        const apple = document.createElement('link');
        apple.rel = 'apple-touch-icon';
        apple.href = s.favicon_url;
        document.head.appendChild(apple);
    }

    function applyTopBar(s) {
        const enabled = s.topbar_enabled === '1' || s.topbar_enabled === 1;
        const existing = document.getElementById('drazaki-topbar');
        if (!enabled) { if (existing) existing.remove(); return; }

        const bg = s.topbar_bg_color || '#0a4d68';
        const fg = s.topbar_text_color || '#ffffff';
        const html = buildTopBarHTML(s, fg);

        if (existing) {
            existing.innerHTML = html;
            existing.style.background = bg;
            existing.style.color = fg;
            return;
        }
        const bar = document.createElement('div');
        bar.id = 'drazaki-topbar';
        bar.style.cssText = 'background:' + bg + ';color:' + fg + ';padding:8px 0;font-size:13px;font-family:inherit;position:relative;z-index:100;';
        bar.innerHTML = html;
        document.body.insertBefore(bar, document.body.firstChild);
    }

    function buildTopBarHTML(s, fg) {
        const items = [];
        if (s.topbar_text) items.push('<span style="opacity:.95;">' + esc(s.topbar_text) + '</span>');
        if (s.topbar_phone) {
            const link = s.topbar_phone_link || 'tel:' + s.topbar_phone.replace(/\s/g, '');
            items.push('<a href="' + esc(link) + '" style="color:' + fg + ';text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.37 1.9.72 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0122 16.92z"/></svg>' + esc(s.topbar_phone) + '</a>');
        }
        if (s.topbar_email) {
            items.push('<a href="mailto:' + esc(s.topbar_email) + '" style="color:' + fg + ';text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>' + esc(s.topbar_email) + '</a>');
        }
        if (s.topbar_location) {
            items.push('<span style="display:inline-flex;align-items:center;gap:6px;opacity:.9;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' + esc(s.topbar_location) + '</span>');
        }
        if (s.topbar_hours) {
            items.push('<span style="display:inline-flex;align-items:center;gap:6px;opacity:.9;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>' + esc(s.topbar_hours) + '</span>');
        }
        return '<div style="max-width:1200px;margin:0 auto;padding:0 20px;display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:center;">' + items.join('') + '</div>';
    }

    function applyPrimaryColor(s) {
        if (!s.primary_color) return;
        const r = document.documentElement;
        r.style.setProperty('--primary-color', s.primary_color);
        r.style.setProperty('--brand-color', s.primary_color);
        r.style.setProperty('--e-global-color-primary', s.primary_color);
    }

    function applyWhatsApp(s) {
        if (!s.whatsapp_number) return;
        const num = s.whatsapp_number.replace(/[^0-9]/g, '');
        if (!num) return;
        const link = 'https://wa.me/' + num;
        document.querySelectorAll('a[href*="wa.me"], a[href*="whatsapp.com"]').forEach(a => {
            if (a.href.indexOf('share') === -1) a.href = link;
        });
        if (!document.querySelector('.drazaki-whatsapp-float') && !document.querySelector('a[href*="wa.me"]')) {
            const btn = document.createElement('a');
            btn.className = 'drazaki-whatsapp-float';
            btn.href = link;
            btn.target = '_blank';
            btn.rel = 'noopener';
            btn.setAttribute('aria-label', 'WhatsApp');
            btn.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
            btn.style.cssText = 'position:fixed;bottom:20px;right:20px;width:56px;height:56px;background:#25d366;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.2);z-index:9999;text-decoration:none;transition:transform 0.2s;';
            btn.onmouseover = () => btn.style.transform = 'scale(1.1)';
            btn.onmouseout = () => btn.style.transform = 'scale(1)';
            document.body.appendChild(btn);
        }
    }

    function applySocialLinks(s) {
        const map = {
            'facebook.com': s.social_facebook,
            'instagram.com': s.social_instagram,
            'twitter.com': s.social_twitter,
            'x.com': s.social_twitter,
            'linkedin.com': s.social_linkedin,
            'youtube.com': s.social_youtube
        };
        Object.keys(map).forEach(domain => {
            if (!map[domain]) return;
            document.querySelectorAll('a[href*="' + domain + '"]').forEach(a => {
                if (a.href.indexOf('share') !== -1 || a.href.indexOf('sharer') !== -1) return;
                a.href = map[domain];
            });
        });
    }

    function applyContactInfo(s) {
        if (s.contact_email) {
            document.querySelectorAll('a[href^="mailto:"]').forEach(a => {
                a.href = 'mailto:' + s.contact_email;
                if (a.textContent && a.textContent.indexOf('@') !== -1) a.textContent = s.contact_email;
            });
        }
        if (s.contact_phone) {
            document.querySelectorAll('a[href^="tel:"]').forEach(a => {
                a.href = 'tel:' + s.contact_phone.replace(/\s/g, '');
            });
        }
    }

    function esc(s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
})();
