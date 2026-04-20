// Espaço para scripts da UI; mantido mínimo nesta entrega
console.log('Instituto Dona - UI carregada');
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
});
