(function () {
  'use strict';

  // Menu off-canvas mobile.
  var navToggle = document.querySelector('[data-nav-toggle]');
  var mobileNav = document.querySelector('[data-mobile-nav]');
  if (navToggle && mobileNav) {
    navToggle.addEventListener('click', function () {
      var open = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', String(!open));
      mobileNav.hidden = open;
    });
    mobileNav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        navToggle.setAttribute('aria-expanded', 'false');
        mobileNav.hidden = true;
      });
    });
  }

  // Modal de login: abrir/fechar, trap de foco, ESC, submit único.
  var modal = document.querySelector('[data-login-modal]');
  var panel = document.querySelector('[data-login-panel]');
  var backdrop = document.querySelector('[data-login-backdrop]');
  var openButtons = document.querySelectorAll('[data-login-open]');
  var closeButtons = document.querySelectorAll('[data-login-close]');
  var recoverToggle = document.querySelector('[data-recover-toggle]');
  var recoverTip = document.querySelector('[data-recover-tip]');
  var loginForm = document.querySelector('[data-login-form]');
  var firstField = document.getElementById('login_email');
  var lastFocusedElement = null;

  if (modal && panel) {
    var focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    var trapFocus = function (event) {
      if (event.key !== 'Tab') { return; }
      var focusable = Array.prototype.slice.call(panel.querySelectorAll(focusableSelector));
      if (!focusable.length) { return; }
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };

    var openModal = function () {
      lastFocusedElement = document.activeElement;
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      window.setTimeout(function () {
        if (firstField) { firstField.focus(); } else { panel.focus(); }
      }, 60);
    };

    var closeModal = function () {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
        lastFocusedElement.focus();
      }
    };

    openButtons.forEach(function (button) { button.addEventListener('click', openModal); });
    closeButtons.forEach(function (button) { button.addEventListener('click', closeModal); });
    if (backdrop) { backdrop.addEventListener('click', closeModal); }

    document.addEventListener('keydown', function (event) {
      if (!modal.classList.contains('is-open')) { return; }
      if (event.key === 'Escape') { closeModal(); return; }
      trapFocus(event);
    });

    if (recoverToggle && recoverTip) {
      recoverToggle.addEventListener('click', function () {
        recoverTip.hidden = !recoverTip.hidden;
      });
    }

    if (loginForm) {
      loginForm.addEventListener('submit', function () {
        var submit = loginForm.querySelector('button[type="submit"]');
        if (submit) {
          submit.disabled = true;
          submit.textContent = 'Entrando...';
        }
      });
    }

    if (modal.dataset.hasError === '1') {
      openModal();
    }
  }
})();
