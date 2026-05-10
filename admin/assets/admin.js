// Admin Panel — shared utilities

const Admin = {
  csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',

  toast(message, type = 'info', duration = 3500) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity .25s';
      setTimeout(() => toast.remove(), 250);
    }, duration);
  },

  async api(endpoint, data = {}, method = 'POST') {
    const opts = {
      method,
      headers: { 'X-CSRF-Token': this.csrf },
    };
    if (method !== 'GET') {
      if (data instanceof FormData) {
        data.append('csrf', this.csrf);
        opts.body = data;
      } else {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify({ ...data, csrf: this.csrf });
      }
    }
    try {
      const r = await fetch(endpoint, opts);
      const json = await r.json();
      return json;
    } catch (e) {
      return { ok: false, error: e.message };
    }
  },

  confirm(message) {
    return new Promise(resolve => {
      const ok = window.confirm(message);
      resolve(ok);
    });
  },

  modal: {
    open(html) {
      let overlay = document.getElementById('admin-modal');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'admin-modal';
        overlay.className = 'modal-overlay';
        document.body.appendChild(overlay);
      }
      overlay.innerHTML = `<div class="modal">${html}</div>`;
      overlay.classList.add('show');
      overlay.addEventListener('click', e => {
        if (e.target === overlay) Admin.modal.close();
      });
    },
    close() {
      const o = document.getElementById('admin-modal');
      if (o) o.classList.remove('show');
    }
  }
};

window.Admin = Admin;
