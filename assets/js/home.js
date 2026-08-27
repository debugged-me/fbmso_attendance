(function() {
  document.addEventListener('click', function(event) {
    var btn = event.target.closest('.toggle-pass[data-target]');
    if (!btn) return;

    var input = document.querySelector(btn.getAttribute('data-target'));
    if (!input) return;

    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';

    var icon = btn.querySelector('i');
    if (icon) {
      icon.className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
    }
  });
})();

(function() {
  var form = document.querySelector('form[action*="Login/auth"]');
  if (!form) return;

  form.addEventListener('submit', function(e) {
    var u = document.getElementById('username');
    var p = document.getElementById('password');

    if (u && typeof u.value === 'string') {
      u.value = u.value.replace(/\u00a0/g, ' ').replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '').replace(/\s+/g, ' ').trim();
    }
    if (p && typeof p.value === 'string') {
      p.value = p.value.replace(/\u00a0/g, ' ').replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '').trim();
    }

    // Loading state on the submit button
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

  var title = /temporary password/i.test(forgotInfo) ? 'Check your email' : 'Password Updated';

  UI.fire({
    icon: 'success',
    title: title,
    text: forgotInfo,
    confirmButtonColor: '#3b5fd4'
  });
})();

(function($) {
  var state = window.homeLoginState || {};
  var form = document.getElementById('resetPassword');
  if (!form) return;

  var emailInput = document.getElementById('reset-email');
  var manualSection = document.getElementById('manual-reset-section');
  var identifierInput = document.getElementById('reset-identifier');
  var resetModeInput = document.getElementById('reset-mode');
  var toggleManualLink = document.getElementById('toggleManualReset');
  var newPasswordInput = document.getElementById('reset-new-password');
  var confirmPasswordInput = document.getElementById('reset-confirm-password');
  var submitButton = document.getElementById('resetSubmit');
  var submitLabel = submitButton ? submitButton.querySelector('span') : null;
  var statusBox = document.getElementById('reset-status');
  var manualMode = !!state.forgotManualMode;
  var accountVerified = !!state.forgotAccountVerified && manualMode;
  var initialEmail = normalizeEmail(state.forgotEmail || (emailInput ? emailInput.value : ''));
  var initialIdentifier = normalizeIdentifier(state.forgotIdentifier || (identifierInput ? identifierInput.value : ''));
  var lastVerifiedKey = accountVerified ? buildIdentityKey(initialEmail, initialIdentifier) : '';
  var lastEmailChecked = accountVerified ? initialEmail : '';
  var lastEmailExists = accountVerified ? true : null;
  var lastEmailActive = accountVerified ? true : null;
  var lastEmailMessage = '';
  var checkTimer = null;
  var activeRequest = null;

  function normalizeEmail(value) {
    return String(value || '')
      .replace(/\u00a0/g, ' ')
      .replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '')
      .replace(/\s+/g, '')
      .trim()
      .toLowerCase();
  }

  function normalizePassword(value) {
    return String(value || '')
      .replace(/\u00a0/g, ' ')
      .replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '')
      .trim();
  }

  function normalizeIdentifier(value) {
    return String(value || '')
      .replace(/\u00a0/g, ' ')
      .replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function buildIdentityKey(email, identifier) {
    return normalizeEmail(email) + '|' + normalizeIdentifier(identifier).toLowerCase();
  }

  function getEmailReadyMessage() {
    return 'Email exists. You can send a temporary password to this email now.';
  }

  function getManualReadyMessage() {
    return 'Account verified. You can set a new password now.';
  }

  function isEmailReady(email) {
    return lastEmailChecked === email && lastEmailExists === true && lastEmailActive === true;
  }

  function setStatus(message, type) {
    if (!statusBox) return;

    statusBox.textContent = message || '';
    statusBox.classList.remove('is-error', 'is-success');

    if (message && type === 'success') {
      statusBox.classList.add('is-success');
    } else if (message && type === 'error') {
      statusBox.classList.add('is-error');
    }

    statusBox.hidden = !message;
  }

  function clearManualPasswords() {
    if (identifierInput && !manualMode) {
      identifierInput.value = '';
    }

    if (newPasswordInput) {
      newPasswordInput.value = '';
    }

    if (confirmPasswordInput) {
      confirmPasswordInput.value = '';
    }
  }

  function syncResetModeUi() {
    var currentEmail = normalizeEmail(emailInput ? emailInput.value : '');

    if (resetModeInput) {
      resetModeInput.value = manualMode ? 'manual' : 'email';
    }

    if (manualSection) {
      manualSection.hidden = !manualMode;
    }

    if (identifierInput) {
      identifierInput.required = !!manualMode;
    }

    if (newPasswordInput) {
      newPasswordInput.required = !!manualMode;
    }

    if (confirmPasswordInput) {
      confirmPasswordInput.required = !!manualMode;
    }

    if (submitLabel) {
      submitLabel.textContent = manualMode ? 'Update password' : 'Send temporary password';
    }

    if (toggleManualLink) {
      toggleManualLink.textContent = manualMode ? 'Send temporary password instead' : 'Set password manually instead';
    }

    if (submitButton) {
      submitButton.hidden = false;
      submitButton.disabled = manualMode ? !accountVerified : !isEmailReady(currentEmail);
    }
  }

  function setAccountVerified(show) {
    accountVerified = !!show;
    if (!accountVerified) {
      clearManualPasswords();
    }
    syncResetModeUi();
  }

  function normalizeInputs() {
    var email = normalizeEmail(emailInput ? emailInput.value : '');
    var identifier = normalizeIdentifier(identifierInput ? identifierInput.value : '');

    if (emailInput) {
      emailInput.value = email;
    }

    if (identifierInput) {
      identifierInput.value = identifier;
    }

    return {
      email: email,
      identifier: identifier
    };
  }

  function clearScheduledCheck() {
    if (checkTimer) {
      clearTimeout(checkTimer);
      checkTimer = null;
    }
  }

  function abortActiveCheck() {
    if (activeRequest && typeof activeRequest.abort === 'function') {
      activeRequest.abort();
    }
    activeRequest = null;
  }

  function clearEmailCacheIfChanged(email) {
    if (email !== lastEmailChecked) {
      lastEmailChecked = '';
      lastEmailExists = null;
      lastEmailActive = null;
      lastEmailMessage = '';
      lastVerifiedKey = '';
    }
  }

  function performEmailCheck(email, immediateFocusTarget) {
    clearScheduledCheck();

    if (!email) {
      abortActiveCheck();
      clearEmailCacheIfChanged('');
      setAccountVerified(false);
      setStatus('', '');
      return;
    }

    clearEmailCacheIfChanged(email);

    if (!isValidEmail(email)) {
      abortActiveCheck();
      setAccountVerified(false);
      setStatus('Please enter a valid email address.', 'error');
      if (immediateFocusTarget && emailInput) emailInput.focus();
      return;
    }

    if (isEmailReady(email)) {
      setAccountVerified(false);
      setStatus(manualMode ? 'Email exists. Enter your username or student ID.' : getEmailReadyMessage(), 'success');
      return;
    }

    if (lastEmailChecked === email && lastEmailExists === false) {
      setAccountVerified(false);
      setStatus(lastEmailMessage || 'Email does not exist.', 'error');
      return;
    }

    if (lastEmailChecked === email && lastEmailExists === true && lastEmailActive === false) {
      setAccountVerified(false);
      setStatus(lastEmailMessage || 'Email exists, but the account is not active. Please contact support.', 'error');
      return;
    }

    if (!$ || typeof $.ajax !== 'function') {
      setAccountVerified(false);
      setStatus('Email check is unavailable right now. Please try again.', 'error');
      return;
    }

    abortActiveCheck();
    setAccountVerified(false);
    setStatus('Checking email...', '');

    activeRequest = $.ajax({
      url: state.checkResetEmailUrl || form.getAttribute('data-check-url'),
      method: 'POST',
      dataType: 'json',
      data: {
        email: email,
        mode: manualMode ? 'manual' : 'email'
      }
    }).done(function(response) {
      lastEmailChecked = email;
      lastEmailExists = !!(response && response.email_exists);
      lastEmailActive = !!(response && response.account_active);
      lastEmailMessage = (response && response.message) || '';

      if (lastEmailExists && lastEmailActive) {
        setStatus(manualMode ? 'Email exists. Enter your username or student ID.' : getEmailReadyMessage(), 'success');
      } else {
        setStatus(lastEmailMessage || 'Email does not exist.', 'error');
      }
    }).fail(function(xhr, textStatus) {
      if (textStatus === 'abort') {
        return;
      }

      lastEmailChecked = '';
      lastEmailExists = null;
      lastEmailActive = null;
      lastEmailMessage = '';
      setStatus('Unable to check email right now. Please try again.', 'error');
    }).always(function() {
      activeRequest = null;
      syncResetModeUi();
    });
  }

  function performManualCheck(identity, immediateFocusTarget) {
    var email = identity.email;
    var identifier = identity.identifier;
    var identityKey = buildIdentityKey(email, identifier);

    clearScheduledCheck();

    if (!email) {
      abortActiveCheck();
      clearEmailCacheIfChanged('');
      setAccountVerified(false);
      setStatus('', '');
      return;
    }

    clearEmailCacheIfChanged(email);

    if (!isValidEmail(email)) {
      abortActiveCheck();
      setAccountVerified(false);
      setStatus('Please enter a valid email address.', 'error');
      if (immediateFocusTarget && emailInput) emailInput.focus();
      return;
    }

    if (!identifier) {
      if (isEmailReady(email)) {
        setAccountVerified(false);
        setStatus('Email exists. Enter your username or student ID.', 'success');
      } else {
        performEmailCheck(email, immediateFocusTarget);
      }
      return;
    }

    if (accountVerified && lastVerifiedKey === identityKey) {
      setStatus(getManualReadyMessage(), 'success');
      syncResetModeUi();
      return;
    }

    if (!$ || typeof $.ajax !== 'function') {
      setAccountVerified(false);
      setStatus('Account check is unavailable right now. Please try again.', 'error');
      return;
    }

    abortActiveCheck();
    setAccountVerified(false);
    setStatus('Checking account...', '');

    activeRequest = $.ajax({
      url: state.checkResetEmailUrl || form.getAttribute('data-check-url'),
      method: 'POST',
      dataType: 'json',
      data: {
        email: email,
        identifier: identifier,
        mode: 'manual'
      }
    }).done(function(response) {
      lastEmailChecked = email;
      lastEmailExists = !!(response && response.email_exists);
      lastEmailActive = !!(response && response.account_active);
      lastEmailMessage = (response && response.message) || '';

      if (response && response.success) {
        initialEmail = email;
        initialIdentifier = identifier;
        lastVerifiedKey = identityKey;
        setAccountVerified(true);
        setStatus(getManualReadyMessage(), 'success');
        if (immediateFocusTarget && newPasswordInput) {
          newPasswordInput.focus();
        }
        return;
      }

      lastVerifiedKey = '';
      setAccountVerified(false);
      setStatus((response && response.message) || 'Email exists, but it does not match that username or student ID.', 'error');
      if (immediateFocusTarget) {
        if (identifierInput && identifier) {
          identifierInput.focus();
        } else if (emailInput) {
          emailInput.focus();
        }
      }
    }).fail(function(xhr, textStatus) {
      if (textStatus === 'abort') {
        return;
      }

      lastVerifiedKey = '';
      setAccountVerified(false);
      setStatus('Unable to check account right now. Please try again.', 'error');
    }).always(function() {
      activeRequest = null;
      syncResetModeUi();
    });
  }

  function scheduleCheck() {
    var identity = normalizeInputs();

    if (!identity.email && !identity.identifier) {
      clearScheduledCheck();
      abortActiveCheck();
      clearEmailCacheIfChanged('');
      setAccountVerified(false);
      setStatus('', '');
      return;
    }

    clearEmailCacheIfChanged(identity.email);

    if (!identity.email) {
      clearScheduledCheck();
      abortActiveCheck();
      setAccountVerified(false);
      setStatus('', '');
      return;
    }

    if (!isValidEmail(identity.email)) {
      clearScheduledCheck();
      abortActiveCheck();
      setAccountVerified(false);
      setStatus('Please enter a valid email address.', 'error');
      return;
    }

    if (!manualMode) {
      clearScheduledCheck();
      checkTimer = setTimeout(function() {
        performEmailCheck(identity.email, false);
      }, 120);
      return;
    }

    if (buildIdentityKey(identity.email, identity.identifier) !== lastVerifiedKey) {
      setAccountVerified(false);
    }

    if (!identity.identifier) {
      clearScheduledCheck();
      abortActiveCheck();
      checkTimer = setTimeout(function() {
        performEmailCheck(identity.email, false);
      }, 160);
      return;
    }

    clearScheduledCheck();
    checkTimer = setTimeout(function() {
      performManualCheck(identity, false);
    }, 180);
  }

  if (emailInput) {
    emailInput.value = initialEmail;
  }

  if (identifierInput) {
    identifierInput.value = initialIdentifier;
  }

  syncResetModeUi();
  setAccountVerified(accountVerified);

  if (state.forgotError) {
    setStatus(state.forgotError, 'error');
  }

  if (state.forgotModalOpen && window.jQuery) {
    window.jQuery('#forgotModal').modal('show');
  }

  [emailInput, identifierInput].forEach(function(input) {
    if (!input) return;

    input.addEventListener('input', scheduleCheck);
    input.addEventListener('keyup', scheduleCheck);
    input.addEventListener('change', scheduleCheck);
    input.addEventListener('paste', function() {
      setTimeout(scheduleCheck, 0);
    });
    input.addEventListener('blur', function() {
      if (manualMode) {
        performManualCheck(normalizeInputs(), false);
      } else {
        performEmailCheck(normalizeEmail(emailInput ? emailInput.value : ''), false);
      }
    });
  });

  if (toggleManualLink) {
    toggleManualLink.addEventListener('click', function(event) {
      event.preventDefault();
      manualMode = !manualMode;

      if (!manualMode) {
        clearManualPasswords();
        lastVerifiedKey = '';
        accountVerified = false;
      }

      syncResetModeUi();

      if (manualMode) {
        if (accountVerified) {
          setStatus(getManualReadyMessage(), 'success');
          newPasswordInput.focus();
        } else if (isEmailReady(normalizeEmail(emailInput ? emailInput.value : ''))) {
          setStatus('Email exists. Enter your username or student ID.', 'success');
        } else {
          scheduleCheck();
        }
      } else if (isEmailReady(normalizeEmail(emailInput ? emailInput.value : ''))) {
        setStatus(getEmailReadyMessage(), 'success');
      } else {
        scheduleCheck();
      }
    });
  }

  if (window.jQuery) {
    window.jQuery('#forgotModal').on('shown.bs.modal', function() {
      setTimeout(scheduleCheck, 50);
      setTimeout(scheduleCheck, 250);
    });
  }

  setTimeout(scheduleCheck, 50);
  setTimeout(scheduleCheck, 250);

  form.addEventListener('submit', function(event) {
    var identity = normalizeInputs();

    if (!manualMode) {
      if (!isEmailReady(identity.email)) {
        event.preventDefault();
        performEmailCheck(identity.email, true);

        if (!identity.email) {
          setStatus('Please enter your registered email.', 'error');
          if (emailInput) emailInput.focus();
        } else if (!isValidEmail(identity.email)) {
          setStatus('Please enter a valid email address.', 'error');
          if (emailInput) emailInput.focus();
        }

        return;
      }
      return;
    }

    if (!accountVerified) {
      event.preventDefault();
      performManualCheck(identity, true);

      if (!identity.email) {
        setStatus('Please enter your registered email.', 'error');
        if (emailInput) emailInput.focus();
      } else if (!isValidEmail(identity.email)) {
        setStatus('Please enter a valid email address.', 'error');
        if (emailInput) emailInput.focus();
      } else if (!identity.identifier) {
        setStatus('Please enter your username or student ID.', 'error');
        if (identifierInput) identifierInput.focus();
      }

      return;
    }

    if (newPasswordInput) {
      newPasswordInput.value = normalizePassword(newPasswordInput.value);
    }

    if (confirmPasswordInput) {
      confirmPasswordInput.value = normalizePassword(confirmPasswordInput.value);
    }

    var newPassword = newPasswordInput ? newPasswordInput.value : '';
    var confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';

    if (newPassword.length < 8) {
      event.preventDefault();
      setStatus('Password must be at least 8 characters.', 'error');
      if (newPasswordInput) newPasswordInput.focus();
      return;
    }

    if (newPassword !== confirmPassword) {
      event.preventDefault();
      setStatus('Passwords do not match.', 'error');
      if (confirmPasswordInput) confirmPasswordInput.focus();
    }
  });
})(window.jQuery);

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

  if (window.UI) {
    UI.fire(opts);
    var fb = document.getElementById('login-error-message');
    if (fb) fb.style.display = 'none';
    var infoFb = document.getElementById('login-info-message');
    if (infoFb) infoFb.style.display = 'none';
  }
})();
