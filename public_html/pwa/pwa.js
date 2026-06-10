(() => {
  const statusEl = document.getElementById('pwaStatusLabel');

  function setStatus() {
    if (!statusEl) return;
    const online = navigator.onLine;
    statusEl.textContent = online ? 'Online' : 'Offline';
    statusEl.style.color = online ? 'rgba(255,255,255,.85)' : '#FFD54F';
  }

  async function registerSW() {
    if (!('serviceWorker' in navigator)) return;
    const path = location.pathname || '';
    const idx = path.indexOf('/pwa');
    const base = idx >= 0 ? path.slice(0, idx) : '';
    try {
      await navigator.serviceWorker.register(`${base}/pwa/sw.js`, { scope: `${base}/pwa/` });
    } catch (e) {}
  }

  const QUEUE_KEY = 'pwa_sync_queue_v1';

  function loadQueue() {
    try {
      const raw = localStorage.getItem(QUEUE_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  function saveQueue(queue) {
    try {
      localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
    } catch (e) {}
  }

  function enqueue(item) {
    const queue = loadQueue();
    queue.push(item);
    saveQueue(queue);
  }

  async function flushQueue() {
    if (!navigator.onLine) return;
    const queue = loadQueue();
    if (!queue.length) return;

    const remaining = [];
    for (const item of queue) {
      try {
        const res = await fetch(item.url, {
          method: item.method,
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: item.body
        });
        if (!res.ok) {
          remaining.push(item);
        }
      } catch (e) {
        remaining.push(item);
      }
    }
    saveQueue(remaining);
  }

  function serializeForm(form) {
    const fd = new FormData(form);
    const params = new URLSearchParams();
    for (const [k, v] of fd.entries()) {
      if (typeof v === 'string') params.append(k, v);
    }
    return params.toString();
  }

  function formHasPassword(form) {
    return !!form.querySelector('input[type="password"]');
  }

  function bindOfflineQueue() {
    document.addEventListener('submit', (ev) => {
      const form = ev.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (!form.closest('.pwa-shell')) return;
      if (formHasPassword(form)) return;
      if (navigator.onLine) return;

      const method = (form.getAttribute('method') || 'GET').toUpperCase();
      if (method !== 'POST') return;
      const action = form.getAttribute('action') || location.href;

      ev.preventDefault();
      enqueue({ url: action, method: 'POST', body: serializeForm(form), ts: Date.now() });
      setStatus();
    }, true);
  }

  window.addEventListener('online', () => { setStatus(); flushQueue(); });
  window.addEventListener('offline', () => { setStatus(); });

  setStatus();
  registerSW();
  bindOfflineQueue();
  flushQueue();
})();
