// Espaço para scripts da UI; mantido mínimo nesta entrega
console.log('Instituto Dona - UI carregada');
document.addEventListener('DOMContentLoaded', function () {
  if (window.feather && typeof window.feather.replace === 'function') {
    window.feather.replace();
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
      isOpen = !panel.classList.contains('hidden');
      sync(!isOpen);
      try { localStorage.setItem('submenu:' + key, (!isOpen) ? 'true' : 'false'); } catch (e) {}
      if (window.feather && typeof window.feather.replace === 'function') {
        window.feather.replace();
      }
    });
  });
});
