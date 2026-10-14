/* UMS EduGate — client helpers (Phase 3 UI shell). Progressive enhancement only. */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss success/info alerts after 6s (danger/warning stay until dismissed).
    document.querySelectorAll('.alert-dismissible').forEach(function (el) {
      if (el.classList.contains('alert-success') || el.classList.contains('alert-info')) {
        setTimeout(function () {
          if (window.bootstrap && el.parentNode) {
            bootstrap.Alert.getOrCreateInstance(el).close();
          }
        }, 6000);
      }
    });

    // Disable double-submission of forms/buttons (data attribute opt-in).
    document.querySelectorAll('form[data-disable-on-submit]').forEach(function (form) {
      form.addEventListener('submit', function () {
        form.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = true; });
        // Re-enable if the page is restored from bfcache.
        window.addEventListener('pageshow', function () {
          form.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = false; });
        }, { once: true });
      });
    });

    // Confirm destructive actions.
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (ev) {
        if (!window.confirm(el.getAttribute('data-confirm'))) { ev.preventDefault(); }
      });
    });
  });
})();
