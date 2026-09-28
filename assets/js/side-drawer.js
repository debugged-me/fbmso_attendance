/*
 * SideDrawer — the shared right-side panel.
 *
 *   var panel = SideDrawer.create({
 *     label: 'Payment details',          // accessible name (and bar title when nav is off)
 *     title: 'Filter ledger',            // optional text shown in the bar instead of prev/next
 *     icon: 'mdi-filter-outline',        // optional icon before the title
 *     nav: true,                         // prev/next buttons + ↑/↓ keys
 *     rowSelector: '#t tbody tr[data-sd-key]', // rows that open the panel on click / Enter
 *     dataTable: dt,                     // optional DataTables instance: nav follows its filter/sort/pages
 *     keyOf: function (data, node) {},   // with dataTable: how to read a row's key (default data-sd-key)
 *     keys: function () { return [] },   // or: explicit ordered keys for nav
 *     width: 460,
 *     accent: '#38aeb7',
 *     render: function (key, panel) { panel.body(html | node); panel.footer(html | node | null); },
 *     onOpen: function (key) {}, onClose: function () {}
 *   });
 *   panel.open(key); panel.close();
 *
 * Only one panel is open at a time. Clicks on links, buttons, inputs and
 * dropdowns inside a row keep their own behaviour.
 */
(function (window, document) {
    'use strict';

    var openPanel = null;
    var INTERACTIVE = 'a, button, input, select, textarea, label, form, .dropdown, .dropdown-menu, [data-sd-ignore]';

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function blank(value) {
        return value === null || value === undefined || String(value).trim() === '';
    }

    function el(tag, className, html) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (html !== undefined) node.innerHTML = html;
        return node;
    }

    function setContent(target, content) {
        while (target.firstChild) target.removeChild(target.firstChild);
        if (content === null || content === undefined || content === '') return false;
        if (typeof content === 'string') target.innerHTML = content;
        else target.appendChild(content);
        return true;
    }

    function focusable(root) {
        return Array.prototype.filter.call(
            root.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), summary, [tabindex]:not([tabindex="-1"])'),
            function (node) { return node.offsetParent !== null || node === document.activeElement; }
        );
    }

    function Panel(options) {
        var self = this;
        self.options = options || {};
        self.current = null;
        self.lastFocus = null;

        var o = self.options;
        var navOn = !!o.nav;
        var titleHtml = o.title
            ? '<span class="sd-bar-title">' + (o.icon ? '<i class="mdi ' + esc(o.icon) + '"></i> ' : '') + esc(o.title) + '</span>'
            : '';

        self.backdrop = el('div', 'sd-backdrop');
        self.el = el('aside', 'sd-drawer' + (o.className ? ' ' + o.className : ''));
        self.el.setAttribute('role', 'dialog');
        self.el.setAttribute('aria-modal', 'true');
        self.el.setAttribute('aria-hidden', 'true');
        self.el.setAttribute('aria-label', o.label || o.title || 'Details');
        if (o.width) self.el.style.width = o.width + 'px';
        if (o.accent) self.el.style.setProperty('--sd-accent', o.accent);

        self.el.innerHTML =
            '<div class="sd-bar">' +
                '<div class="sd-bar-start">' +
                    (navOn
                        ? '<button type="button" class="sd-icon-btn" data-sd-step="-1" aria-label="Previous" title="Previous (↑)"><i class="mdi mdi-chevron-up"></i></button>' +
                          '<button type="button" class="sd-icon-btn" data-sd-step="1" aria-label="Next" title="Next (↓)"><i class="mdi mdi-chevron-down"></i></button>' +
                          '<span class="sd-counter"></span>'
                        : '') +
                    titleHtml +
                '</div>' +
                '<button type="button" class="sd-icon-btn" data-sd-close aria-label="Close" title="Close (Esc)"><i class="mdi mdi-close"></i></button>' +
            '</div>' +
            '<div class="sd-body"></div>' +
            '<div class="sd-foot" hidden></div>';

        self.bodyEl = self.el.querySelector('.sd-body');
        self.footEl = self.el.querySelector('.sd-foot');
        self.counterEl = self.el.querySelector('.sd-counter');
        self.prevBtn = self.el.querySelector('[data-sd-step="-1"]');
        self.nextBtn = self.el.querySelector('[data-sd-step="1"]');
        self.closeBtn = self.el.querySelector('[data-sd-close]');

        var mount = function () {
            document.body.appendChild(self.backdrop);
            document.body.appendChild(self.el);
        };
        if (document.body) mount(); else document.addEventListener('DOMContentLoaded', mount);

        self.backdrop.addEventListener('click', function () { self.close(); });
        self.closeBtn.addEventListener('click', function () { self.close(); });
        if (self.prevBtn) self.prevBtn.addEventListener('click', function () { self.step(-1); });
        if (self.nextBtn) self.nextBtn.addEventListener('click', function () { self.step(1); });

        if (o.dataTable) {
            var dtHelper = SideDrawer.dataTable(o.dataTable, o.keyOf);
            if (!o.keys) o.keys = dtHelper.keys;
            if (!o.reveal) o.reveal = dtHelper.reveal;
            if (window.jQuery) {
                window.jQuery(o.dataTable.table().node()).on('draw.dt', function () {
                    if (self.isOpen()) { self.refresh(); self.highlight(); }
                });
            }
        }

        if (o.trigger) {
            document.addEventListener('click', function (e) {
                var trigger = e.target.closest && e.target.closest(o.trigger);
                if (!trigger || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                e.preventDefault();
                e.stopPropagation();
                self.open(trigger.hasAttribute('data-sd-key') ? trigger.getAttribute('data-sd-key') : undefined);
            }, true);
        }

        if (o.rowSelector) {
            document.addEventListener('click', function (e) {
                var row = e.target.closest && e.target.closest(o.rowSelector);
                if (!row) return;
                var interactive = e.target.closest(INTERACTIVE);
                var opener = interactive && interactive.hasAttribute('data-sd-open');
                if (interactive && row.contains(interactive) && !opener) return;
                if (opener && (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey)) return;
                if (opener) e.preventDefault();
                self.open(row.getAttribute('data-sd-key'));
            });
            document.addEventListener('keydown', function (e) {
                if ((e.key !== 'Enter' && e.key !== ' ') || !e.target.matches || !e.target.matches(o.rowSelector)) return;
                e.preventDefault();
                self.open(e.target.getAttribute('data-sd-key'));
            });
        }
    }

    Panel.prototype.isOpen = function () {
        return this.el.classList.contains('is-open');
    };

    Panel.prototype.body = function (content) {
        setContent(this.bodyEl, content);
        this.bodyEl.scrollTop = 0;
        return this;
    };

    Panel.prototype.footer = function (content) {
        this.footEl.hidden = !setContent(this.footEl, content);
        return this;
    };

    Panel.prototype.loading = function (heroHtml) {
        return this.body((heroHtml || '') + SideDrawer.skeleton());
    };

    Panel.prototype.error = function (message, heroHtml) {
        this.footer(null);
        return this.body((heroHtml || '') + '<p class="sd-note sd-error"><i class="mdi mdi-alert-circle-outline"></i> ' + esc(message) + '</p>');
    };

    Panel.prototype.keys = function () {
        var o = this.options;
        if (o.keys) return o.keys() || [];
        if (!o.rowSelector) return [];
        return Array.prototype.filter.call(document.querySelectorAll(o.rowSelector), function (row) {
            return row.offsetParent !== null;
        }).map(function (row) { return row.getAttribute('data-sd-key'); });
    };

    Panel.prototype.refresh = function () {
        if (!this.counterEl) return;
        var list = this.keys();
        var index = list.indexOf(this.current);
        this.counterEl.textContent = index >= 0 ? (index + 1) + ' of ' + list.length : '';
        this.prevBtn.disabled = index <= 0;
        this.nextBtn.disabled = index < 0 || index >= list.length - 1;
    };

    Panel.prototype.rows = function () {
        return this.options.rowSelector ? document.querySelectorAll(this.options.rowSelector) : [];
    };

    Panel.prototype.highlight = function () {
        var self = this;
        var open = self.isOpen();
        Array.prototype.forEach.call(self.rows(), function (row) {
            row.classList.toggle('sd-row-active', open && row.getAttribute('data-sd-key') === self.current);
        });
    };

    Panel.prototype.activeRow = function () {
        var self = this;
        return Array.prototype.filter.call(self.rows(), function (row) {
            return row.getAttribute('data-sd-key') === self.current;
        })[0] || null;
    };

    Panel.prototype.open = function (key) {
        var self = this;
        var o = self.options;
        if (openPanel && openPanel !== self) openPanel.close(true);
        self.current = key === undefined ? null : String(key);
        if (o.render) o.render(self.current, self);
        if (!self.isOpen()) {
            self.lastFocus = document.activeElement;
            self.el.classList.add('is-open');
            self.backdrop.classList.add('is-open');
            self.el.setAttribute('aria-hidden', 'false');
            document.body.classList.add('sd-lock');
            openPanel = self;
            setTimeout(function () {
                var first = o.focus ? self.el.querySelector(o.focus) : null;
                (first || self.closeBtn).focus();
            }, 60);
            if (o.onOpen) o.onOpen(self.current, self);
        }
        self.refresh();
        self.highlight();
        return self;
    };

    Panel.prototype.close = function (switching) {
        var self = this;
        if (!self.isOpen()) return self;
        self.el.classList.remove('is-open');
        self.backdrop.classList.remove('is-open');
        self.el.setAttribute('aria-hidden', 'true');
        if (!switching) document.body.classList.remove('sd-lock');
        if (openPanel === self) openPanel = null;
        self.highlight();
        self.current = null;
        if (self.options.onClose) self.options.onClose(self);
        if (!switching && self.lastFocus && self.lastFocus.focus && document.contains(self.lastFocus)) self.lastFocus.focus();
        return self;
    };

    Panel.prototype.step = function (delta) {
        var list = this.keys();
        var index = list.indexOf(this.current);
        var next = list[index + delta];
        if (index < 0 || next === undefined) return;
        if (this.options.reveal) this.options.reveal(next);
        this.open(next);
        var row = this.activeRow();
        if (row && row.scrollIntoView) row.scrollIntoView({ block: 'nearest' });
    };

    document.addEventListener('keydown', function (e) {
        if (!openPanel) return;
        if (e.key === 'Escape') { e.preventDefault(); openPanel.close(); return; }
        if (e.key === 'Tab') {
            var nodes = focusable(openPanel.el);
            if (!nodes.length) return;
            var first = nodes[0], last = nodes[nodes.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            else if (!openPanel.el.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
            return;
        }
        if (!openPanel.options.nav || /INPUT|SELECT|TEXTAREA/.test(e.target.tagName)) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); openPanel.step(1); }
        if (e.key === 'ArrowUp') { e.preventDefault(); openPanel.step(-1); }
    });

    var SideDrawer = {
        create: function (options) { return new Panel(options); },

        /* Turn an element on the page (usually a filter <form>) into a panel. */
        fromElement: function (element, options) {
            if (!element) return null;
            options = options || {};
            var panel = new Panel(options);
            panel.body(element);
            element.classList.add('sd-form');
            element.hidden = false;
            return panel;
        },

        dataTable: function (dt, keyOf) {
            keyOf = keyOf || function (data, node) { return node ? node.getAttribute('data-sd-key') : null; };
            function list() {
                var out = [];
                dt.rows({ search: 'applied', order: 'applied' }).every(function () {
                    out.push(keyOf(this.data(), this.node()));
                });
                return out;
            }
            return {
                keys: list,
                reveal: function (key) {
                    var index = list().indexOf(key);
                    var info = dt.page.info();
                    if (index < 0 || info.length <= 0) return;
                    var page = Math.floor(index / info.length);
                    if (page !== info.page) dt.page(page).draw('page');
                }
            };
        },

        fetchJSON: function (url) {
            return fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (res) {
                return res.json().catch(function () {
                    throw new Error('Your session may have expired. Reload the page and try again.');
                });
            }).then(function (data) {
                if (!data || data.ok === false) throw new Error((data && data.message) || 'Could not load the details.');
                return data;
            });
        },

        esc: esc,
        blank: blank,

        field: function (label, value, wide, raw) {
            if (blank(value)) return '';
            return '<div' + (wide ? ' class="sd-wide"' : '') + '><dt>' + esc(label) + '</dt><dd>' + (raw ? value : esc(value)) + '</dd></div>';
        },

        grid: function (fields) {
            var html = fields.join('');
            return html ? '<dl class="sd-grid">' + html + '</dl>' : '';
        },

        kv: function (pairs, className) {
            var html = pairs.filter(function (p) { return !blank(p[1]); }).map(function (p) {
                return '<div><dt>' + esc(p[0]) + '</dt><dd>' + (p[2] ? p[1] : esc(p[1])) + '</dd></div>';
            }).join('');
            return html ? '<dl class="sd-kv' + (className ? ' ' + className : '') + '">' + html + '</dl>' : '';
        },

        section: function (title, inner, count) {
            if (!inner) return '';
            return '<section class="sd-section"><h6>' + esc(title) +
                (count !== undefined && count !== null ? ' <span class="sd-count">' + esc(count) + '</span>' : '') +
                '</h6>' + inner + '</section>';
        },

        pill: function (text, tone) {
            return '<span class="sd-pill sd-tone-' + esc(tone || 'neutral') + '">' + esc(text) + '</span>';
        },

        status: function (ok, text) {
            return '<span class="sd-status ' + (ok ? 'sd-status-ok' : 'sd-status-failed') + '"><i></i>' + esc(text) + '</span>';
        },

        skeleton: function () {
            return '<section class="sd-section"><div class="sd-skel" style="width:40%"></div><div class="sd-skel"></div><div class="sd-skel" style="width:75%"></div></section>' +
                '<section class="sd-section"><div class="sd-skel" style="width:35%"></div><div class="sd-skel" style="width:85%"></div><div class="sd-skel" style="width:60%"></div></section>';
        },

        money: function (value) {
            var n = Number(value) || 0;
            return '\u20b1 ' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };

    window.SideDrawer = SideDrawer;
})(window, document);
