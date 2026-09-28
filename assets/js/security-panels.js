/*
 * IP summary panel for the security screens. Any element with
 * data-ip-summary and data-sd-key="<ip>" opens it; Ctrl/Cmd-click still
 * follows the element's href (the full investigate page).
 *
 *   SecurityPanels.ipPanel({ url: '.../Securityadmin/ip_summary', investigateUrl: '.../Securityadmin/investigate' });
 *
 * Needs side-drawer.js.
 */
(function (window) {
    'use strict';

    var cache = {};

    function SD() { return window.SideDrawer; }

    function statusTone(status) {
        return status === 'success' ? 'success' : status === 'failed' ? 'danger' : 'neutral';
    }

    var SecurityPanels = {
        load: function (url, ip) {
            if (cache[ip]) return Promise.resolve(cache[ip]);
            return SD().fetchJSON(url + '?ip=' + encodeURIComponent(ip)).then(function (data) {
                cache[ip] = data;
                return data;
            });
        },

        statusPill: function (status) {
            return SD().pill(status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown', statusTone(status));
        },

        summaryHtml: function (d) {
            var S = SD();
            var html = '';
            if (d.blocked) {
                html += '<p class="sd-note sd-error" style="margin-top:0"><i class="mdi mdi-block-helper"></i> <strong>Blocked</strong>' +
                    (d.blocked.reason ? ' \u00b7 ' + S.esc(d.blocked.reason) : '') +
                    '<br><small>' + S.esc([d.blocked.by ? 'by ' + d.blocked.by : '', d.blocked.at,
                        d.blocked.permanent ? 'permanent' : (d.blocked.expires ? 'until ' + d.blocked.expires : '')].filter(Boolean).join(' \u00b7 ')) + '</small></p>';
            }
            html += '<div class="sd-stats">' +
                '<div class="sd-stat"><div class="sd-stat-label">Successful sign-ins</div><div class="sd-stat-value is-good">' + d.success + '</div></div>' +
                '<div class="sd-stat"><div class="sd-stat-label">Failed attempts</div><div class="sd-stat-value' + (d.failed ? ' is-bad' : '') + '">' + d.failed + '</div></div>' +
                '<div class="sd-stat"><div class="sd-stat-label">Accounts tried</div><div class="sd-stat-value">' + d.accounts + '</div></div>' +
                '<div class="sd-stat"><div class="sd-stat-label">Security events</div><div class="sd-stat-value">' + d.events + '</div></div>' +
                '</div>';
            html += '<div style="margin-top:.6rem">' + S.kv([['First seen', d.firstSeen], ['Last seen', d.lastSeen]]) + '</div>';

            if (d.topAccounts.length) {
                html += S.section('Accounts from this IP', '<ul class="sd-list">' + d.topAccounts.map(function (a) {
                    return '<li><div class="sd-list-main"><div class="sd-list-title sd-mono">' + S.esc(a.username || '(blank)') + '</div>' +
                        '<div class="sd-list-sub">Last ' + S.esc(a.last) + '</div></div>' +
                        '<div class="sd-list-end">' + a.attempts + ' attempt' + (a.attempts === 1 ? '' : 's') +
                        (a.failed ? '<div class="sd-list-sub" style="color:#c42f41">' + a.failed + ' failed</div>' : '') + '</div></li>';
                }).join('') + '</ul>', d.accounts > d.topAccounts.length ? d.topAccounts.length + ' of ' + d.accounts : null);
            }
            if (d.recent.length) {
                html += S.section('Latest attempts', '<ul class="sd-list">' + d.recent.map(function (r) {
                    return '<li><div class="sd-list-main"><div class="sd-list-title sd-mono">' + S.esc(r.username || '(blank)') + '</div>' +
                        '<div class="sd-list-sub">' + S.esc(r.time) + '</div></div><div class="sd-list-end">' + SecurityPanels.statusPill(r.status) + '</div></li>';
                }).join('') + '</ul>');
            }
            if (d.devices.length) {
                html += S.section('Devices seen', S.kv(d.devices.map(function (dev) {
                    return [dev.label, dev.count + '\u00d7'];
                })));
            }
            return html;
        },

        investigateButton: function (investigateUrl, ip) {
            return '<a class="sd-btn" href="' + SD().esc(investigateUrl + '?ip=' + encodeURIComponent(ip)) + '"><i class="mdi mdi-magnify"></i> Full investigation</a>';
        },

        ipPanel: function (options) {
            var S = SD();
            if (!S) return null;
            function hero(ip, data) {
                return '<div class="sd-hero"><div class="sd-hero-media"><i class="mdi mdi-ip-network-outline"></i></div><div class="sd-hero-text">' +
                    '<div class="sd-tags" style="margin:0 0 .3rem">' + S.pill('IP address', 'neutral') +
                    (data && data.blocked ? S.pill('Blocked', 'danger') : '') + '</div>' +
                    '<h5 class="sd-title sd-mono">' + S.esc(ip) + '</h5>' +
                    (data ? '<div class="sd-desc">' + data.total + ' sign-in record' + (data.total === 1 ? '' : 's') + '</div>' : '') +
                    '</div></div>';
            }
            return S.create({
                label: 'IP address summary',
                width: 440,
                trigger: options.trigger || '[data-ip-summary]',
                render: function (ip, panel) {
                    panel.footer(SecurityPanels.investigateButton(options.investigateUrl, ip));
                    panel.loading(hero(ip, null));
                    SecurityPanels.load(options.url, ip).then(function (data) {
                        if (panel.current === ip) panel.body(hero(ip, data) + S.section('Activity', SecurityPanels.summaryHtml(data)));
                    }).catch(function (err) {
                        if (panel.current === ip) panel.error(err.message, hero(ip, null));
                    });
                }
            });
        }
    };

    window.SecurityPanels = SecurityPanels;
})(window);
