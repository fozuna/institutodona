// EspaÃ§o para scripts da UI; mantido mÃ­nimo nesta entrega
console.log('SIS+ - UI carregada');
document.addEventListener('DOMContentLoaded', function () {
  if (window.feather && typeof window.feather.replace === 'function') {
    window.feather.replace();
  }
  var shell = document.querySelector('[data-app-shell]');
  var sidebar = document.getElementById('appSidebar');
  var sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');
  var sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
  var sidebarStorageKey = 'sidebar:collapsed';
  var mediaQuery = null;
  try { mediaQuery = window.matchMedia('(max-width: 900px)'); } catch (e) {}
  var mobileOpen = false;
  var collapsed = false;
  try { collapsed = localStorage.getItem(sidebarStorageKey) === 'true'; } catch (e) {}

  var syncSidebar = function () {
    if (!shell || !sidebar) { return; }
    var isMobile = !!(mediaQuery && mediaQuery.matches);
    shell.setAttribute('data-sidebar-viewport', isMobile ? 'mobile' : 'desktop');
    shell.setAttribute('data-sidebar-collapsed', !isMobile && collapsed ? 'true' : 'false');
    shell.setAttribute('data-sidebar-open', isMobile ? (mobileOpen ? 'true' : 'false') : 'true');
    sidebarToggles.forEach(function (toggle) {
      var active = isMobile ? mobileOpen : collapsed;
      toggle.classList.toggle('is-active', active);
      toggle.setAttribute('aria-expanded', isMobile ? (mobileOpen ? 'true' : 'false') : (collapsed ? 'false' : 'true'));
      toggle.setAttribute('aria-label', isMobile
        ? (mobileOpen ? 'Fechar menu lateral' : 'Abrir menu lateral')
        : (collapsed ? 'Expandir menu lateral' : 'Recolher menu lateral'));
      toggle.setAttribute('title', isMobile
        ? (mobileOpen ? 'Fechar menu lateral' : 'Abrir menu lateral')
        : (collapsed ? 'Expandir menu lateral' : 'Recolher menu lateral'));
    });
  };

  var toggleSidebar = function () {
    if (!shell || !sidebar) { return; }
    var isMobile = !!(mediaQuery && mediaQuery.matches);
    if (isMobile) {
      mobileOpen = !mobileOpen;
    } else {
      collapsed = !collapsed;
      try { localStorage.setItem(sidebarStorageKey, collapsed ? 'true' : 'false'); } catch (e) {}
    }
    syncSidebar();
  };

  if (shell && sidebar) {
    syncSidebar();
    sidebarToggles.forEach(function (toggle) {
      toggle.addEventListener('click', toggleSidebar);
    });
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', function () {
        mobileOpen = false;
        syncSidebar();
      });
    }
    if (mediaQuery && typeof mediaQuery.addEventListener === 'function') {
      mediaQuery.addEventListener('change', function (e) {
        if (!e.matches) { mobileOpen = false; }
        syncSidebar();
      });
    } else if (mediaQuery && typeof mediaQuery.addListener === 'function') {
      mediaQuery.addListener(function (e) {
        if (!e.matches) { mobileOpen = false; }
        syncSidebar();
      });
    }
  }
  var html = document.documentElement;
  var saved = null;
  try { saved = localStorage.getItem('theme'); } catch (e) {}
  var prefersDark = false;
  try { prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches; } catch (e) {}
  if (saved === 'dark' || (!saved && prefersDark)) {
    html.classList.add('dark');
  }
  var btn = document.getElementById('themeToggle');
  if (btn) {
    btn.addEventListener('click', function () {
      var isDark = html.classList.toggle('dark');
      try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch (e) {}
    });
  }

  document.querySelectorAll('[data-submenu-trigger]').forEach(function (trigger) {
    var key = trigger.getAttribute('data-submenu-trigger');
    var panel = document.querySelector('[data-submenu-panel="' + key + '"]');
    if (!panel) { return; }

    var defaultOpen = trigger.getAttribute('data-default-open') === 'true';
    var stored = null;
    try { stored = localStorage.getItem('submenu:' + key); } catch (e) {}
    var isOpen = stored === null ? defaultOpen : stored === 'true';

    var sync = function (open) {
      panel.classList.toggle('hidden', !open);
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    sync(isOpen);

    trigger.addEventListener('click', function () {
      if (shell && shell.getAttribute('data-sidebar-viewport') === 'desktop' && shell.getAttribute('data-sidebar-collapsed') === 'true') {
        collapsed = false;
        try { localStorage.setItem(sidebarStorageKey, 'false'); } catch (e) {}
        syncSidebar();
      }
      isOpen = !panel.classList.contains('hidden');
      sync(!isOpen);
      try { localStorage.setItem('submenu:' + key, (!isOpen) ? 'true' : 'false'); } catch (e) {}
      if (window.feather && typeof window.feather.replace === 'function') {
        window.feather.replace();
      }
    });
  });

  // Navegacao reversa global com preservacao de estado (formularios, scroll e hash).
  var nav = (function () {
    var stackKey = 'nav:stack:v1';
    var maxItems = 60;
    var currentUrl = window.location.pathname + window.location.search + window.location.hash;
    var restoreParam = '__nav_restore';
    var restoreIdParam = '__nav_restore_id';
    var currentEntryId = null;

    function readStack() {
      try {
        var raw = sessionStorage.getItem(stackKey);
        var arr = raw ? JSON.parse(raw) : [];
        return Array.isArray(arr) ? arr : [];
      } catch (e) {
        return [];
      }
    }

    function writeStack(items) {
      try { sessionStorage.setItem(stackKey, JSON.stringify(items.slice(-maxItems))); } catch (e) {}
    }

    function stateKey(entryId) {
      return 'nav:state:v1:' + entryId;
    }

    function newEntryId() {
      return String(Date.now()) + ':' + String(Math.random()).slice(2, 10);
    }

    function getCurrentEntry() {
      var stack = readStack();
      if (stack.length > 0) {
        var last = stack[stack.length - 1];
        if (last && last.url === currentUrl) {
          return last;
        }
      }
      return null;
    }

    function upsertCurrentEntry() {
      var stack = readStack();
      var last = stack.length > 0 ? stack[stack.length - 1] : null;
      if (last && last.url === currentUrl) {
        currentEntryId = last.id;
        return last;
      }
      var entry = { id: newEntryId(), url: currentUrl, ts: Date.now() };
      currentEntryId = entry.id;
      stack.push(entry);
      writeStack(stack);
      return entry;
    }

    function removeRestoreParams(url) {
      try {
        var u = new URL(url, window.location.origin);
        u.searchParams.delete(restoreParam);
        u.searchParams.delete(restoreIdParam);
        return u.pathname + u.search + u.hash;
      } catch (e) {
        return url;
      }
    }

    function collectFormState() {
      var forms = [];
      document.querySelectorAll('form').forEach(function (form, index) {
        var fields = [];
        form.querySelectorAll('input,select,textarea').forEach(function (el, idx) {
          var tag = (el.tagName || '').toLowerCase();
          var type = (el.type || '').toLowerCase();
          if (type === 'password' || type === 'file') { return; }
          var field = {
            i: idx,
            name: el.name || '',
            tag: tag,
            type: type
          };
          if (type === 'checkbox' || type === 'radio') {
            field.checked = !!el.checked;
          } else {
            field.value = String(el.value || '');
          }
          fields.push(field);
        });
        forms.push({ i: index, fields: fields });
      });
      return forms;
    }

    function restoreFormState(forms) {
      if (!Array.isArray(forms)) { return; }
      var allForms = document.querySelectorAll('form');
      forms.forEach(function (savedForm) {
        var form = allForms[savedForm.i];
        if (!form || !Array.isArray(savedForm.fields)) { return; }
        var controls = form.querySelectorAll('input,select,textarea');
        savedForm.fields.forEach(function (savedField) {
          var el = controls[savedField.i];
          if (!el) { return; }
          var type = (el.type || '').toLowerCase();
          if (type === 'checkbox' || type === 'radio') {
            el.checked = !!savedField.checked;
          } else if (typeof savedField.value === 'string') {
            el.value = savedField.value;
          }
          try {
            el.dispatchEvent(new Event('change', { bubbles: true }));
          } catch (e) {}
        });
      });
    }

    function saveCurrentState() {
      var entry = currentEntryId ? { id: currentEntryId } : getCurrentEntry();
      if (!entry || !entry.id) { return; }
      var payload = {
        url: currentUrl,
        scrollY: window.scrollY || 0,
        hash: window.location.hash || '',
        forms: collectFormState(),
        savedAt: Date.now()
      };
      try {
        sessionStorage.setItem(stateKey(entry.id), JSON.stringify(payload));
      } catch (e) {}
    }

    function resolvePreviousEntry() {
      var stack = readStack();
      if (stack.length < 2) { return null; }
      var idx = stack.length - 1;
      while (idx >= 0 && stack[idx].url === currentUrl) { idx--; }
      if (idx < 0) { return null; }
      return stack[idx];
    }

    function goBack() {
      saveCurrentState();
      var prev = resolvePreviousEntry();
      if (prev && prev.url) {
        try {
          var u = new URL(prev.url, window.location.origin);
          u.searchParams.set(restoreParam, '1');
          u.searchParams.set(restoreIdParam, prev.id);
          window.location.href = u.pathname + u.search + u.hash;
          return;
        } catch (e) {}
      }
      if (window.history.length > 1) {
        window.history.back();
        return;
      }
      window.location.href = 'index.php?route=dashboard/index';
    }

    function restoreIfNeeded() {
      var params = null;
      try { params = new URLSearchParams(window.location.search); } catch (e) { return; }
      if (!params || params.get(restoreParam) !== '1') { return; }
      var id = params.get(restoreIdParam) || currentEntryId;
      if (!id) { return; }
      var raw = null;
      try { raw = sessionStorage.getItem(stateKey(id)); } catch (e) {}
      if (!raw) {
        var cleanUrl = removeRestoreParams(window.location.href);
        window.history.replaceState({}, '', cleanUrl);
        return;
      }
      var state = null;
      try { state = JSON.parse(raw); } catch (e) {}
      if (!state) {
        var cleanUrl2 = removeRestoreParams(window.location.href);
        window.history.replaceState({}, '', cleanUrl2);
        return;
      }
      restoreFormState(state.forms || []);
      var hash = typeof state.hash === 'string' ? state.hash : '';
      if (hash && hash !== window.location.hash) {
        window.location.hash = hash;
      }
      window.requestAnimationFrame(function () {
        window.scrollTo(0, Number(state.scrollY || 0));
      });
      var cleanUrl3 = removeRestoreParams(window.location.href);
      window.history.replaceState({}, '', cleanUrl3);
    }

    function isBackControl(el) {
      if (!el) { return false; }
      var href = (el.getAttribute && (el.getAttribute('href') || '') || '').trim().toLowerCase();
      if (href === 'javascript:history.back()') { return true; }
      var onClick = (el.getAttribute && (el.getAttribute('onclick') || '') || '').toLowerCase();
      if (onClick.indexOf('history.back(') !== -1) { return true; }
      var text = ((el.textContent || '') + '').trim().toLowerCase();
      if (text.indexOf('voltar') === 0) { return true; }
      return el.hasAttribute && el.hasAttribute('data-nav-back');
    }

    function installBackInterceptor() {
      document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) { return; }
        var control = target.closest('a,button');
        if (!control || !isBackControl(control)) { return; }
        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
          event.stopImmediatePropagation();
        }
        goBack();
      }, true);
    }

    function installStatePersistence() {
      var debouncedTimer = null;
      var debouncedSave = function () {
        if (debouncedTimer) { window.clearTimeout(debouncedTimer); }
        debouncedTimer = window.setTimeout(saveCurrentState, 120);
      };
      document.addEventListener('input', debouncedSave, true);
      document.addEventListener('change', debouncedSave, true);
      window.addEventListener('pagehide', saveCurrentState);
      window.addEventListener('beforeunload', saveCurrentState);
    }

    function init() {
      upsertCurrentEntry();
      installBackInterceptor();
      installStatePersistence();
      restoreIfNeeded();
    }

    return {
      init: init,
      goBack: goBack,
      saveCurrentState: saveCurrentState
    };
  })();

  nav.init();
  window.AppNav = nav;
});
