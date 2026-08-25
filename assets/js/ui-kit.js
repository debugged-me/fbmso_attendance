/* ==========================================================================
   UI Kit — shared modal + notification layer
   Loaded on every page via application/views/includes/head.php.

   Zero dependencies: no jQuery, no Bootstrap JS, no SweetAlert. It works on
   the login screen, the QR poster and the dashboard alike, and it cannot be
   broken by script load order.

   Why it stays cheap:
   - Nothing is built until something is shown. No container, no listeners,
     no timers exist on a page that never opens a modal or a toast.
   - The only global listeners are one keydown and one click, and the keydown
     is attached only while a modal is actually open.
   - Toast countdowns are CSS animations, not setInterval loops, so N visible
     toasts still cost zero JS ticks per frame.
   - Enter/leave animate `transform` + `opacity` only, and `will-change` is
     removed as soon as the transition ends.

   ---------------------------------------------------------------------------
   NOTIFICATIONS
     UI.success('Saved.')
     UI.error('Could not save.', 'Server error')
     UI.warning('Session expiring soon.')
     UI.info('3 new requests.')
     UI.toast({ type, title, message, duration, position, action:{label,onClick} })

   MODALS
     UI.alert({ title, message })                    -> Promise
     UI.confirm({ title, message, variant:'danger' }) -> Promise<boolean>
     UI.prompt({ title, label, value })               -> Promise<string|null>
     UI.modal({ title, body, buttons:[...] })         -> handle { close(), el }
     UI.loading('Generating report...')               -> handle { close() }

   MARKUP (no JS needed)
     <a href="..."          data-ui-confirm="Delete this record?">Delete</a>
     <form ... data-ui-confirm="Submit this form?">
     <button data-ui-toast="Copied." data-ui-toast-type="success">
   ========================================================================== */

(function (window, document) {
    'use strict';

    if (window.UI && window.UI.__uikit) {
        return; // already loaded (a view double-including the head)
    }

    // ----------------------------------------------------------------------
    // Small helpers
    // ----------------------------------------------------------------------

    var TOAST_MAX = 4;          // visible at once; the rest queue
    var TOAST_DEFAULT_MS = 4500;
    var reduceMotion = window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : { matches: false };

    function esc(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function el(tag, className, html) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (html !== undefined) node.innerHTML = html;
        return node;
    }

    function assign(target) {
        for (var i = 1; i < arguments.length; i++) {
            var src = arguments[i];
            if (!src) continue;
            for (var k in src) {
                if (Object.prototype.hasOwnProperty.call(src, k)) target[k] = src[k];
            }
        }
        return target;
    }

    /**
     * Run `fn` when the element's transition finishes, with a timeout fallback
     * so a dropped transitionend (background tab, display:none) can never leak
     * a node or strand a promise.
     */
    function afterTransition(node, fallbackMs, fn) {
        var done = false;
        function finish() {
            if (done) return;
            done = true;
            node.removeEventListener('transitionend', onEnd);
            node.classList.remove('uk-animating');
            fn();
        }
        function onEnd(e) {
            if (e.target === node) finish();
        }
        if (reduceMotion.matches) {
            finish();
            return;
        }
        node.addEventListener('transitionend', onEnd);
        window.setTimeout(finish, fallbackMs);
    }

    var ICONS = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        question: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
    };

    // ----------------------------------------------------------------------
    // Scroll lock — shared by every open modal, restored exactly once
    // ----------------------------------------------------------------------

    var lockDepth = 0;
    var savedPadding = '';

    function lockScroll() {
        if (lockDepth++ > 0) return;
        // Compensate for the scrollbar so the page does not jump sideways.
        var gap = window.innerWidth - document.documentElement.clientWidth;
        savedPadding = document.body.style.paddingRight;
        if (gap > 0) document.body.style.paddingRight = gap + 'px';
        document.body.classList.add('uk-locked');
    }

    function unlockScroll() {
        if (--lockDepth > 0) return;
        lockDepth = 0;
        document.body.classList.remove('uk-locked');
        document.body.style.paddingRight = savedPadding;
    }

    // ----------------------------------------------------------------------
    // Modal
    // ----------------------------------------------------------------------

    var openModals = [];
    var keyListenerAttached = false;

    var FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

    function onDocumentKey(e) {
        if (!openModals.length) return;
        var top = openModals[openModals.length - 1];

        if (e.key === 'Escape' && top.opts.closeOnEsc !== false) {
            e.preventDefault();
            top.close(top.opts.escValue !== undefined ? top.opts.escValue : null);
            return;
        }

        // Keep focus inside the topmost dialog.
        if (e.key === 'Tab') {
            var items = top.modal.querySelectorAll(FOCUSABLE);
            if (!items.length) return;
            var first = items[0];
            var last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    function attachKeyListener() {
        if (keyListenerAttached) return;
        document.addEventListener('keydown', onDocumentKey);
        keyListenerAttached = true;
    }

    function detachKeyListener() {
        if (!keyListenerAttached || openModals.length) return;
        document.removeEventListener('keydown', onDocumentKey);
        keyListenerAttached = false;
    }

    /**
     * Core modal. Everything else (alert/confirm/prompt/loading) is built on it.
     *
     * @param {Object} opts
     *   title, subtitle, body (string), html (bool, default false for body),
     *   icon: 'success'|'error'|'warning'|'info'|'question'|false,
     *   size: 'sm'|'lg'|'xl'|'full',
     *   buttons: [{ label, variant, value, close, onClick }],
     *   closeButton, closeOnEsc, closeOnBackdrop, onClose(value)
     * @returns {{close: Function, el: HTMLElement, body: HTMLElement}}
     */
    function modal(opts) {
        opts = opts || {};

        var backdrop = el('div', 'uk-backdrop');
        var box = el('div', 'uk-modal' + (opts.size ? ' uk-modal-' + opts.size : ''));
        box.setAttribute('role', opts.role || 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('tabindex', '-1');

        var parts = '';

        // --- header ---
        var hasIcon = !!opts.icon && ICONS[opts.icon];
        if (opts.title || opts.subtitle || opts.closeButton !== false) {
            parts += '<div class="uk-modal-head' + (hasIcon ? '' : ' uk-no-icon') + '">';
            if (hasIcon) {
                parts += '<div class="uk-modal-icon uk-' + esc(opts.icon) + '">' + ICONS[opts.icon] + '</div>';
            }
            parts += '<div class="uk-modal-titles">';
            if (opts.title) {
                parts += '<h2 class="uk-modal-title">' + esc(opts.title) + '</h2>';
            }
            if (opts.subtitle) {
                parts += '<p class="uk-modal-sub">' + esc(opts.subtitle) + '</p>';
            }
            parts += '</div>';
            if (opts.closeButton !== false) {
                parts += '<button type="button" class="uk-modal-x" data-uk-dismiss aria-label="Close">' + ICONS.close + '</button>';
            }
            parts += '</div>';
        }

        // --- body ---
        if (opts.body !== undefined && opts.body !== null && opts.body !== '') {
            parts += '<div class="uk-modal-body">' + (opts.html ? opts.body : esc(opts.body)) + '</div>';
        }

        // --- footer ---
        var buttons = opts.buttons || [];
        if (buttons.length) {
            parts += '<div class="uk-modal-foot">';
            for (var i = 0; i < buttons.length; i++) {
                var b = buttons[i];
                parts += '<button type="button" class="uk-btn uk-btn-' + esc(b.variant || 'ghost') +
                    '" data-uk-index="' + i + '">' + esc(b.label) + '</button>';
            }
            parts += '</div>';
        }

        box.innerHTML = parts;
        backdrop.appendChild(box);

        var previousFocus = document.activeElement;
        var closed = false;

        var handle = {
            el: backdrop,
            modal: box,
            body: box.querySelector('.uk-modal-body'),
            opts: opts,
            close: closeModal
        };

        function closeModal(value) {
            if (closed) return;
            closed = true;

            var index = openModals.indexOf(handle);
            if (index > -1) openModals.splice(index, 1);

            backdrop.classList.remove('uk-in');
            box.classList.add('uk-animating');

            afterTransition(box, 400, function () {
                if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                unlockScroll();
                detachKeyListener();

                // Give focus back to whatever opened the dialog.
                if (previousFocus && previousFocus.focus) {
                    try { previousFocus.focus(); } catch (e) { /* node gone */ }
                }
                if (typeof opts.onClose === 'function') opts.onClose(value);
            });
        }

        // One delegated click handler covers dismiss, backdrop and every button.
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) {
                if (opts.closeOnBackdrop !== false) closeModal(null);
                return;
            }
            var dismiss = e.target.closest ? e.target.closest('[data-uk-dismiss]') : null;
            if (dismiss) {
                closeModal(null);
                return;
            }
            var btn = e.target.closest ? e.target.closest('[data-uk-index]') : null;
            if (btn) {
                var conf = buttons[parseInt(btn.getAttribute('data-uk-index'), 10)];
                if (!conf) return;
                var result = (typeof conf.onClick === 'function') ? conf.onClick(handle, btn) : undefined;
                if (result === false) return;        // handler asked to stay open
                if (conf.close === false) return;
                closeModal(conf.value !== undefined ? conf.value : true);
            }
        });

        document.body.appendChild(backdrop);
        lockScroll();
        openModals.push(handle);
        attachKeyListener();

        box.classList.add('uk-animating');

        // Two frames: one for the browser to lay the node out, one to animate.
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                backdrop.classList.add('uk-in');
                afterTransition(box, 400, function () { });

                var focusTarget = box.querySelector('[data-uk-autofocus]')
                    || box.querySelector('.uk-input')
                    || box.querySelector('.uk-btn-primary, .uk-btn-danger, .uk-btn-success')
                    || box;
                if (focusTarget && focusTarget.focus) focusTarget.focus();
            });
        });

        return handle;
    }

    // ----------------------------------------------------------------------
    // Modal presets
    // ----------------------------------------------------------------------

    function normalise(input, defaults) {
        if (typeof input === 'string') input = { message: input };
        return assign({}, defaults, input || {});
    }

    function alertBox(options) {
        var o = normalise(options, { icon: 'info', okText: 'OK', title: 'Notice' });
        return new Promise(function (resolve) {
            modal({
                title: o.title,
                subtitle: o.subtitle,
                icon: o.icon,
                size: o.size,
                body: o.message,
                html: o.html,
                buttons: [{ label: o.okText, variant: o.variant || 'primary', value: true }],
                onClose: function () { resolve(); }
            });
        });
    }

    function confirmBox(options) {
        var o = normalise(options, {
            icon: 'question',
            title: 'Are you sure?',
            confirmText: 'Confirm',
            cancelText: 'Cancel',
            variant: 'primary'
        });
        return new Promise(function (resolve) {
            modal({
                title: o.title,
                subtitle: o.subtitle,
                icon: o.icon,
                size: o.size,
                body: o.message,
                html: o.html,
                buttons: [
                    { label: o.cancelText, variant: 'ghost', value: false },
                    { label: o.confirmText, variant: o.variant, value: true }
                ],
                escValue: false,
                onClose: function (value) { resolve(value === true); }
            });
        });
    }

    function promptBox(options) {
        var o = normalise(options, {
            icon: 'question',
            title: 'Enter a value',
            confirmText: 'OK',
            cancelText: 'Cancel',
            value: '',
            inputType: 'text',
            required: true
        });

        return new Promise(function (resolve) {
            var fieldId = 'uk-prompt-' + Math.random().toString(36).slice(2, 9);
            var body = (o.message ? '<p style="margin:0 0 12px">' + esc(o.message) + '</p>' : '')
                + (o.label ? '<label for="' + fieldId + '">' + esc(o.label) + '</label>' : '')
                + '<input id="' + fieldId + '" class="uk-input" type="' + esc(o.inputType) + '"'
                + ' value="' + esc(o.value) + '"'
                + (o.placeholder ? ' placeholder="' + esc(o.placeholder) + '"' : '')
                + ' data-uk-autofocus>';

            var handle = modal({
                title: o.title,
                subtitle: o.subtitle,
                icon: o.icon,
                body: body,
                html: true,
                buttons: [
                    { label: o.cancelText, variant: 'ghost', value: null },
                    {
                        label: o.confirmText,
                        variant: o.variant || 'primary',
                        onClick: function (h) {
                            var input = h.modal.querySelector('.uk-input');
                            var val = input ? input.value.trim() : '';
                            if (o.required && val === '') {
                                input.focus();
                                input.style.borderColor = 'var(--uk-error)';
                                return false;   // keep the dialog open
                            }
                            h.close(val);
                            return false;       // close() already handled it
                        }
                    }
                ],
                escValue: null,
                onClose: function (value) { resolve(value === true ? null : value); }
            });

            // Enter submits.
            var input = handle.modal.querySelector('.uk-input');
            if (input) {
                input.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    var val = input.value.trim();
                    if (o.required && val === '') {
                        input.style.borderColor = 'var(--uk-error)';
                        return;
                    }
                    handle.close(val);
                });
            }
        });
    }

    function loading(message) {
        return modal({
            body: '<div class="uk-modal-busy"><span class="uk-spinner"></span>'
                + '<div style="font-weight:600;color:var(--uk-text)">'
                + esc(message || 'Working...') + '</div></div>',
            html: true,
            size: 'sm',
            closeButton: false,
            closeOnEsc: false,
            closeOnBackdrop: false
        });
    }

    // ----------------------------------------------------------------------
    // Toasts
    // ----------------------------------------------------------------------

    var containers = {};   // one per position, created on first use
    var queue = [];
    var visible = 0;

    function containerFor(position) {
        position = position || 'top-right';
        if (containers[position]) return containers[position];

        var node = el('div', 'uk-toasts');
        node.setAttribute('data-pos', position);
        node.setAttribute('role', 'region');
        node.setAttribute('aria-live', 'polite');
        node.setAttribute('aria-label', 'Notifications');
        document.body.appendChild(node);
        containers[position] = node;
        return node;
    }

    function toast(options) {
        var o = normalise(options, {
            type: 'info',
            duration: TOAST_DEFAULT_MS,
            position: 'top-right',
            dismissible: true
        });

        if (!ICONS[o.type]) o.type = 'info';

        if (visible >= TOAST_MAX) {
            queue.push(o);
            return null;
        }
        return renderToast(o);
    }

    function renderToast(o) {
        visible++;

        var node = el('div', 'uk-toast uk-t-' + o.type);
        node.setAttribute('role', o.type === 'error' ? 'alert' : 'status');

        var html = '<div class="uk-toast-icon">' + ICONS[o.type] + '</div><div class="uk-toast-body">';
        if (o.title) html += '<p class="uk-toast-title">' + esc(o.title) + '</p>';
        if (o.message) {
            html += '<p class="uk-toast-msg">' + (o.html ? o.message : esc(o.message)) + '</p>';
        }
        if (o.action && o.action.label) {
            html += '<button type="button" class="uk-toast-action">' + esc(o.action.label) + '</button>';
        }
        html += '</div>';
        if (o.dismissible !== false) {
            html += '<button type="button" class="uk-toast-x" aria-label="Dismiss">' + ICONS.close + '</button>';
        }
        if (o.duration > 0) {
            // The bar's CSS animation IS the timer; hovering pauses it via CSS,
            // and `animationend` is what actually removes the toast. No polling.
            html += '<span class="uk-toast-timer" style="animation-duration:' + (o.duration | 0) + 'ms"></span>';
        }
        node.innerHTML = html;

        var removed = false;
        function remove() {
            if (removed) return;
            removed = true;
            visible--;
            node.classList.add('uk-animating', 'uk-out');
            node.classList.remove('uk-in');
            afterTransition(node, 350, function () {
                if (node.parentNode) node.parentNode.removeChild(node);
                if (queue.length && visible < TOAST_MAX) renderToast(queue.shift());
            });
        }

        node.addEventListener('click', function (e) {
            if (e.target.closest('.uk-toast-x')) {
                remove();
                return;
            }
            if (e.target.closest('.uk-toast-action')) {
                if (o.action && typeof o.action.onClick === 'function') o.action.onClick();
                remove();
                return;
            }
            if (o.onClick) o.onClick();
        });

        var timer = node.querySelector('.uk-toast-timer');
        if (timer) {
            timer.addEventListener('animationend', remove);
        }

        containerFor(o.position).appendChild(node);
        node.classList.add('uk-animating');
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                node.classList.add('uk-in');
                afterTransition(node, 350, function () { });
            });
        });

        return { close: remove, el: node };
    }

    function shorthand(type, defaultTitle) {
        return function (message, title, extra) {
            return toast(assign({
                type: type,
                title: title === undefined ? defaultTitle : title,
                message: message
            }, extra || {}));
        };
    }

    // ----------------------------------------------------------------------
    // Theme — follow the app, not the OS
    // ----------------------------------------------------------------------
    //
    // The Hyper theme's dark mode is an opt-in stylesheet swap (the customizer
    // repoints #app-stylesheet at app-dark.min.css). Keying the kit to the OS
    // preference instead would put a dark modal on a light page, so mirror the
    // app's real state onto <html data-uk-theme>.

    var themeMode = 'auto';

    function appIsDark() {
        var sheet = document.getElementById('app-stylesheet');
        return !!(sheet && /dark/i.test(sheet.getAttribute('href') || ''));
    }

    function applyTheme() {
        var dark = (themeMode === 'dark') || (themeMode === 'auto' && appIsDark());
        var root = document.documentElement;
        if (dark) {
            root.setAttribute('data-uk-theme', 'dark');
        } else {
            root.removeAttribute('data-uk-theme');
        }
    }

    function watchTheme() {
        applyTheme();

        // One observer on one node, attributes only — negligible cost, and it
        // keeps the kit in step when the customizer flips the theme.
        var sheet = document.getElementById('app-stylesheet');
        if (!sheet || typeof MutationObserver !== 'function') return;

        new MutationObserver(function () {
            if (themeMode === 'auto') applyTheme();
        }).observe(sheet, { attributes: true, attributeFilter: ['href'] });
    }

    // ----------------------------------------------------------------------
    // Declarative hooks — one delegated listener for the whole document
    // ----------------------------------------------------------------------

    function bindDeclarative() {
        document.addEventListener('click', function (e) {
            if (!e.target.closest) return;

            // data-ui-toast="Copied to clipboard"
            var toaster = e.target.closest('[data-ui-toast]');
            if (toaster) {
                toast({
                    type: toaster.getAttribute('data-ui-toast-type') || 'info',
                    title: toaster.getAttribute('data-ui-toast-title') || undefined,
                    message: toaster.getAttribute('data-ui-toast')
                });
            }

            // data-ui-confirm="Delete this record?" on a link or button
            var trigger = e.target.closest('[data-ui-confirm]');
            if (!trigger || trigger.hasAttribute('data-uk-confirmed')) return;
            if (trigger.tagName === 'FORM') return;   // handled on submit below

            e.preventDefault();
            e.stopPropagation();

            confirmBox({
                title: trigger.getAttribute('data-ui-confirm-title') || 'Please confirm',
                message: trigger.getAttribute('data-ui-confirm'),
                confirmText: trigger.getAttribute('data-ui-confirm-ok') || 'Yes, continue',
                cancelText: trigger.getAttribute('data-ui-confirm-cancel') || 'Cancel',
                variant: trigger.getAttribute('data-ui-confirm-variant') || 'danger',
                icon: trigger.getAttribute('data-ui-confirm-icon') || 'warning'
            }).then(function (ok) {
                if (!ok) return;
                trigger.setAttribute('data-uk-confirmed', '1');
                if (trigger.tagName === 'A' && trigger.getAttribute('href')) {
                    window.location.href = trigger.href;
                } else {
                    trigger.click();
                }
                trigger.removeAttribute('data-uk-confirmed');
            });
        }, true);

        // <form data-ui-confirm="Submit?">
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.hasAttribute || !form.hasAttribute('data-ui-confirm')) return;
            if (form.hasAttribute('data-uk-confirmed')) return;

            e.preventDefault();
            confirmBox({
                title: form.getAttribute('data-ui-confirm-title') || 'Please confirm',
                message: form.getAttribute('data-ui-confirm'),
                confirmText: form.getAttribute('data-ui-confirm-ok') || 'Yes, continue',
                variant: form.getAttribute('data-ui-confirm-variant') || 'primary',
                icon: form.getAttribute('data-ui-confirm-icon') || 'question'
            }).then(function (ok) {
                if (!ok) return;
                form.setAttribute('data-uk-confirmed', '1');
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        }, true);
    }

    // ----------------------------------------------------------------------
    // Public surface
    // ----------------------------------------------------------------------

    var UI = {
        __uikit: true,

        // notifications
        toast: toast,
        success: shorthand('success', 'Success'),
        error: shorthand('error', 'Something went wrong'),
        warning: shorthand('warning', 'Heads up'),
        info: shorthand('info', null),

        /** Render server-side flashdata. Called by the head include. */
        flash: function (items) {
            if (!items) return;
            if (!Array.isArray(items)) items = [items];
            for (var i = 0; i < items.length; i++) {
                if (items[i] && items[i].message) toast(items[i]);
            }
        },

        // modals
        modal: modal,
        alert: alertBox,
        confirm: confirmBox,
        prompt: promptBox,
        loading: loading,

        /** Close every open dialog (e.g. before navigating away). */
        closeAll: function () {
            while (openModals.length) openModals[openModals.length - 1].close(null);
        },

        /**
         * 'auto' (default) follows the app's own theme, 'dark'/'light' force it.
         * Useful when embedding the kit outside this app.
         */
        theme: function (mode) {
            themeMode = (mode === 'dark' || mode === 'light') ? mode : 'auto';
            applyTheme();
            return themeMode;
        },

        icons: ICONS
    };

    window.UI = UI;

    function boot() {
        bindDeclarative();
        watchTheme();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

})(window, document);
