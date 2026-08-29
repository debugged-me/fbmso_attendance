(function() {
  document.addEventListener('click', function(event) {
    var btn = event.target.closest('.toggle-pass[data-target]');
    if (!btn) return;

    var input = document.querySelector(btn.getAttribute('data-target'));
    if (!input) return;

    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';

    var icon = btn.querySelector('i');
    if (icon) icon.className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
  });
})();

(function() {
  var form = document.querySelector('form[action*="Login/auth"]');
  if (!form) return;

  form.addEventListener('submit', function() {
    var u = document.getElementById('username');
    var p = document.getElementById('password');

    if (u && typeof u.value === 'string') {
      u.value = u.value.replace(/\u00a0/g, ' ').replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '').replace(/\s+/g, ' ').trim();
    }
    if (p && typeof p.value === 'string') {
      p.value = p.value.replace(/\u00a0/g, ' ').replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '').trim();
    }

    var btn = document.getElementById('loginBtn');
    if (btn && u && p && u.value.trim() && p.value.trim()) {
      btn.classList.add('is-loading');
    }
  });
})();

(function() {
  var state = window.homeLoginState || {};
  var forgotInfo = state.forgotInfo || '';

  if (!forgotInfo || !window.UI) return;

  UI.fire({
    icon: 'success',
    title: 'Check your email',
    text: forgotInfo,
    confirmButtonColor: '#3b5fd4'
  });
})();

(function() {
  var state = window.homeLoginState || {};
  var form = document.getElementById('resetPassword');
  if (!form) return;

  var emailInput = document.getElementById('reset-email');
  var statusBox = document.getElementById('reset-status');

  function normalizeEmail(value) {
    return String(value || '')
      .replace(/\u00a0/g, ' ')
      .replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '')
      .replace(/\s+/g, '')
      .trim()
      .toLowerCase();
  }

  function setStatus(message, isError) {
    if (!statusBox) return;
    statusBox.textContent = message || '';
    statusBox.classList.remove('is-error', 'is-success');
    if (message) statusBox.classList.add(isError ? 'is-error' : 'is-success');
    statusBox.hidden = !message;
  }

  if (emailInput) emailInput.value = normalizeEmail(state.forgotEmail || emailInput.value);
  if (state.forgotError) setStatus(state.forgotError, true);
  if (state.forgotModalOpen && window.jQuery) window.jQuery('#forgotModal').modal('show');

  form.addEventListener('submit', function(event) {
    var email = normalizeEmail(emailInput ? emailInput.value : '');
    if (emailInput) emailInput.value = email;

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      event.preventDefault();
      setStatus('Please enter a valid email address.', true);
      if (emailInput) emailInput.focus();
    }
  });
})();

(function() {
  var state = window.homeLoginState || {};
  var loginError = state.loginError || '';
  var infoMsg = state.infoMessage || '';

  if (!loginError && !infoMsg) return;

  var isErr = /invalid|incorrect|not active|failed|unauthorized|email not found|verify your email/i.test(loginError || '');
  var opts = isErr ? {
    icon: 'error',
    title: 'Sign-in failed',
    text: loginError,
    confirmButtonColor: '#e74c3c'
  } : {
    icon: 'success',
    title: 'Done',
    text: infoMsg,
    confirmButtonColor: '#3b5fd4'
  };

  if (window.UI) {
    UI.fire(opts);
    var fb = document.getElementById('login-error-message');
    if (fb) fb.style.display = 'none';
    var infoFb = document.getElementById('login-info-message');
    if (infoFb) infoFb.style.display = 'none';
  }
})();
