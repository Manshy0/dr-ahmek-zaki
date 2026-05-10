/**
 * Inline Visual Editor
 * Injected into the served HTML page (inside the editor iframe).
 * Lets users click on any text/image to edit it, then sends edits to the
 * parent window for saving via /admin/api/save-content.php.
 *
 * Element addressing: every editable element is tagged on the server with
 * data-ie-id="N". We send that id back to the server which uses the same
 * deterministic walk to resolve the matching node in the source file.
 */

(function () {
  'use strict';

  const FILE = window.__EDITOR_FILE__;
  const API = window.__EDITOR_API__;
  const CSRF = window.__EDITOR_CSRF__;

  // Block all link clicks and form submissions inside editor iframe
  document.addEventListener('click', e => {
    const a = e.target.closest('a');
    if (a && a.href && !e.target.isContentEditable) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);
  document.addEventListener('submit', e => { e.preventDefault(); }, true);

  function ieId(el) {
    return el && el.getAttribute ? el.getAttribute('data-ie-id') : null;
  }

  // Track edits as a Map keyed by `${id}|${type}|${attr}`
  const edits = new Map();
  function setEdit(id, type, value, attr = '') {
    if (id === null) return;
    edits.set(`${id}|${type}|${attr}`, { id, type, value, attr });
    notifyParent();
  }
  function notifyParent() {
    parent.postMessage({ type: 'editor:dirty', count: edits.size }, '*');
  }

  // Tags that hold pure text we can edit inline
  const TEXT_TAGS = new Set([
    'P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6',
    'SPAN', 'A', 'LI', 'BLOCKQUOTE', 'TD', 'TH',
    'STRONG', 'EM', 'BUTTON', 'LABEL', 'FIGCAPTION',
    'SUMMARY', 'DT', 'DD'
  ]);

  function isPureText(el) {
    if (!el || !TEXT_TAGS.has(el.tagName)) return false;
    if (el.closest('script, style, head, .ie-toolbar, .ie-overlay, .ie-image-controls')) return false;
    for (const c of el.children) {
      if (['DIV','SECTION','ARTICLE','HEADER','FOOTER','NAV','UL','OL','TABLE','FORM','MAIN','ASIDE','FIGURE'].includes(c.tagName)) return false;
    }
    if (el.textContent.trim() === '' && !el.querySelector('img')) return false;
    return true;
  }

  function attachTextEditor(el) {
    if (el.dataset.ieAttached) return;
    el.dataset.ieAttached = '1';
    el.classList.add('ie-editable');

    el.addEventListener('mouseenter', () => el.classList.add('ie-hover'));
    el.addEventListener('mouseleave', () => el.classList.remove('ie-hover'));

    el.addEventListener('click', (e) => {
      if (el.contentEditable !== 'true') {
        e.preventDefault();
        e.stopPropagation();
        el.contentEditable = 'true';
        el.classList.add('ie-active');
        if (el.dataset.ieOriginal === undefined) {
          el.dataset.ieOriginal = el.innerHTML;
        }
        el.focus();
        const range = document.createRange();
        range.selectNodeContents(el);
        range.collapse(false);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
      }
    }, true);

    el.addEventListener('blur', () => {
      el.contentEditable = 'false';
      el.classList.remove('ie-active');
      const current = el.innerHTML;
      if (el.dataset.ieOriginal !== undefined && current !== el.dataset.ieOriginal) {
        const id = ieId(el);
        const hasChildElements = el.children.length > 0;
        setEdit(id, hasChildElements ? 'html' : 'text', current);
      }
    });

    el.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        el.innerHTML = el.dataset.ieOriginal || el.innerHTML;
        el.blur();
      }
      if (e.key === 'Enter' && !e.shiftKey && !el.tagName.match(/^(P|DIV|LI)$/)) {
        e.preventDefault();
        el.blur();
      }
    });
  }

  function attachImageEditor(img) {
    if (img.dataset.ieAttached) return;
    if (img.closest('.ie-toolbar, .ie-overlay')) return;
    img.dataset.ieAttached = '1';
    img.classList.add('ie-editable-img');

    img.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      openImageDialog(img);
    });
  }

  function attachBgEditor(el) {
    const style = el.getAttribute('style') || '';
    if (!/background-image\s*:\s*url/i.test(style)) return;
    if (el.dataset.ieAttached) return;
    el.dataset.ieAttached = '1';
    el.classList.add('ie-editable-bg');

    el.addEventListener('click', (e) => {
      if (e.target !== el) return;
      e.preventDefault();
      e.stopPropagation();
      openBgDialog(el);
    });
  }

  function openImageDialog(img) {
    const overlay = document.createElement('div');
    overlay.className = 'ie-overlay';
    overlay.innerHTML = `
      <div class="ie-modal">
        <div class="ie-modal-header">
          <strong>Edit image</strong>
          <button class="ie-close" aria-label="Close">&times;</button>
        </div>
        <div class="ie-modal-body">
          <img src="${img.src}" class="ie-preview" alt="">
          <div class="ie-row">
            <button class="ie-btn ie-btn-primary" data-action="upload">Upload new image</button>
            <button class="ie-btn" data-action="url">Set from URL</button>
          </div>
          <div class="ie-row">
            <label class="ie-label">Alt text</label>
            <input type="text" class="ie-input" id="ie-alt" value="${(img.alt || '').replace(/"/g, '&quot;')}">
          </div>
        </div>
        <div class="ie-modal-footer">
          <button class="ie-btn" data-action="cancel">Cancel</button>
          <button class="ie-btn ie-btn-primary" data-action="apply">Apply</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    let newSrc = null;

    overlay.querySelector('[data-action="upload"]').onclick = () => {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.onchange = async () => {
        const file = input.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        fd.append('csrf', CSRF);
        overlay.querySelector('.ie-modal-body').classList.add('ie-loading');
        const r = await fetch(API + '/upload-image.php', { method: 'POST', body: fd });
        const j = await r.json().catch(() => ({ ok: false }));
        overlay.querySelector('.ie-modal-body').classList.remove('ie-loading');
        if (j.ok) {
          newSrc = j.url;
          overlay.querySelector('.ie-preview').src = j.url;
        } else {
          alert('Upload failed: ' + (j.error || 'Unknown error'));
        }
      };
      input.click();
    };

    overlay.querySelector('[data-action="url"]').onclick = () => {
      const url = prompt('Enter image URL:', img.src);
      if (url) {
        newSrc = url;
        overlay.querySelector('.ie-preview').src = url;
      }
    };

    overlay.querySelector('[data-action="cancel"]').onclick = () => overlay.remove();
    overlay.querySelector('.ie-close').onclick = () => overlay.remove();

    overlay.querySelector('[data-action="apply"]').onclick = () => {
      const id = ieId(img);
      if (newSrc && newSrc !== img.src) {
        img.src = newSrc;
        if (img.hasAttribute('srcset')) img.removeAttribute('srcset');
        setEdit(id, 'image-src', newSrc);
      }
      const newAlt = overlay.querySelector('#ie-alt').value;
      if (newAlt !== img.alt) {
        img.alt = newAlt;
        setEdit(id, 'image-alt', newAlt);
      }
      overlay.remove();
    };
  }

  function openBgDialog(el) {
    const overlay = document.createElement('div');
    overlay.className = 'ie-overlay';
    const style = el.getAttribute('style') || '';
    const m = style.match(/background-image\s*:\s*url\(['"]?([^'")]+)['"]?\)/i);
    const currentUrl = m ? m[1] : '';
    overlay.innerHTML = `
      <div class="ie-modal">
        <div class="ie-modal-header">
          <strong>Edit background image</strong>
          <button class="ie-close" aria-label="Close">&times;</button>
        </div>
        <div class="ie-modal-body">
          <img src="${currentUrl}" class="ie-preview" alt="">
          <div class="ie-row">
            <button class="ie-btn ie-btn-primary" data-action="upload">Upload new image</button>
            <button class="ie-btn" data-action="url">Set from URL</button>
          </div>
        </div>
        <div class="ie-modal-footer">
          <button class="ie-btn" data-action="cancel">Cancel</button>
          <button class="ie-btn ie-btn-primary" data-action="apply">Apply</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
    let newSrc = null;
    overlay.querySelector('[data-action="upload"]').onclick = () => {
      const input = document.createElement('input');
      input.type = 'file'; input.accept = 'image/*';
      input.onchange = async () => {
        const file = input.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        fd.append('csrf', CSRF);
        const r = await fetch(API + '/upload-image.php', { method: 'POST', body: fd });
        const j = await r.json().catch(() => ({ ok: false }));
        if (j.ok) {
          newSrc = j.url;
          overlay.querySelector('.ie-preview').src = j.url;
        } else alert('Upload failed: ' + (j.error || 'Unknown'));
      };
      input.click();
    };
    overlay.querySelector('[data-action="url"]').onclick = () => {
      const url = prompt('Enter image URL:', currentUrl);
      if (url) { newSrc = url; overlay.querySelector('.ie-preview').src = url; }
    };
    overlay.querySelector('[data-action="cancel"]').onclick = () => overlay.remove();
    overlay.querySelector('.ie-close').onclick = () => overlay.remove();
    overlay.querySelector('[data-action="apply"]').onclick = () => {
      if (newSrc) {
        const id = ieId(el);
        let s = el.getAttribute('style') || '';
        const newBg = "background-image: url('" + newSrc + "')";
        if (/background-image\s*:[^;]+/i.test(s)) s = s.replace(/background-image\s*:[^;]+/i, newBg);
        else s = (s + '; ' + newBg).replace(/^;\s*/, '');
        el.setAttribute('style', s);
        setEdit(id, 'bg-image', newSrc);
      }
      overlay.remove();
    };
  }

  function scanAll() {
    document.querySelectorAll('p, h1, h2, h3, h4, h5, h6, span, a, li, blockquote, td, th, strong, em, button, label, figcaption, summary, dt, dd').forEach(el => {
      if (isPureText(el)) attachTextEditor(el);
    });
    document.querySelectorAll('img').forEach(attachImageEditor);
    document.querySelectorAll('[style*="background-image"]').forEach(attachBgEditor);
  }

  // Listen for parent commands
  window.addEventListener('message', async (e) => {
    if (!e.data || !e.data.type) return;
    if (e.data.type === 'editor:save') {
      const payload = { file: FILE, edits: Array.from(edits.values()), csrf: CSRF };
      try {
        const r = await fetch(API + '/save-content.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
          body: JSON.stringify(payload),
        });
        const j = await r.json();
        parent.postMessage({ type: 'editor:saved', result: j }, '*');
        if (j.ok) edits.clear();
      } catch (err) {
        parent.postMessage({ type: 'editor:saved', result: { ok: false, error: err.message } }, '*');
      }
    } else if (e.data.type === 'editor:discard') {
      window.location.reload();
    } else if (e.data.type === 'editor:rescan') {
      scanAll();
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scanAll);
  } else {
    scanAll();
  }

  let scanTimer;
  const observer = new MutationObserver(() => {
    clearTimeout(scanTimer);
    scanTimer = setTimeout(scanAll, 300);
  });
  observer.observe(document.body, { childList: true, subtree: true });

  parent.postMessage({ type: 'editor:ready', file: FILE }, '*');
})();
