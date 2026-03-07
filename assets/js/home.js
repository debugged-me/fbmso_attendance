(function() {
  var btn = document.getElementById('togglePass');
  var ipt = document.getElementById('password');
  if (!btn || !ipt) return;

  btn.addEventListener('click', function() {
    var show = ipt.type === 'password';
    ipt.type = show ? 'text' : 'password';
    this.firstElementChild.className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
  });
})();

(function() {
  var form = document.querySelector('form[action*="Login/auth"]');
  if (!form) return;

  form.addEventListener('submit', function() {
    var u = document.getElementById('username');
    var p = document.getElementById('password');

    if (u && typeof u.value === 'string') {
      u.value = u.value.replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
    }
    if (p && typeof p.value === 'string') {
      p.value = p.value.replace(/\u00a0/g, ' ').trim();
    }
  });
})();

(function() {
  var state = window.homeLoginState || {};
  var loginError = state.loginError || '';
  var infoMsg = state.infoMessage || '';

  if (!loginError && !infoMsg) return;

  var isErr = /invalid|incorrect|not active|failed|unauthorized|email not found/i.test(loginError || '');
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

  if (window.Swal) {
    Swal.fire(opts);
    var fb = document.getElementById('login-error-message');
    if (fb) fb.style.display = 'none';
  }
})();
