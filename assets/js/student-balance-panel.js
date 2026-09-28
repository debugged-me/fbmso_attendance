/*
 * Student balance panel for the accounting screens. Any element with
 * data-student-balance and data-sd-key="<student number>" opens it.
 *
 *   StudentBalancePanel({ url: '<?= site_url('Accounting/studentSummary') ?>' });
 *
 * Needs side-drawer.js.
 */
(function (window) {
    'use strict';

    window.StudentBalancePanel = function (options) {
        var SD = window.SideDrawer;
        if (!SD) return null;
        var cache = {};

        function hero(studno, info) {
            var place = info ? [info.course, info.yearLevel].filter(Boolean).join(' \u00b7 ') : '';
            return '<div class="sd-hero"><div class="sd-hero-media"><i class="mdi mdi-account-cash-outline"></i></div><div class="sd-hero-text">' +
                '<h5 class="sd-title">' + SD.esc(info ? info.name : studno) + '</h5>' +
                '<div class="sd-sub">' + SD.esc(studno) + '</div>' +
                (place ? '<div class="sd-desc">' + SD.esc(place) + '</div>' : '') +
                '</div></div>';
        }

        function feeTone(status) {
            return status === 'paid' ? ['Fully paid', 'success'] : status === 'partial' ? ['Partial', 'warning'] : ['No set price', 'neutral'];
        }

        function body(info) {
            var owed = info.termOutstanding > 0.004;
            var totalDue = info.termPaid + info.termOutstanding;
            var pct = totalDue > 0 ? Math.min(100, Math.round(info.termPaid / totalDue * 100)) : 100;

            var html = SD.section('This term' + (info.term ? ' \u00b7 ' + info.term : ''),
                '<div class="sd-stats">' +
                    '<div class="sd-stat"><div class="sd-stat-label">Paid</div><div class="sd-stat-value is-good">' + SD.money(info.termPaid) + '</div></div>' +
                    '<div class="sd-stat"><div class="sd-stat-label">Outstanding</div><div class="sd-stat-value' + (owed ? ' is-bad' : '') + '">' + SD.money(info.termOutstanding) + '</div></div>' +
                '</div>' +
                (totalDue > 0 ? '<div class="sd-meter" title="' + pct + '% paid"><span style="width:' + pct + '%"></span></div>' : ''));

            if (info.fees.length) {
                html += SD.section('Fees this term', '<ul class="sd-list">' + info.fees.map(function (fee) {
                    var tone = feeTone(fee.status);
                    return '<li><div class="sd-list-main"><div class="sd-list-title">' + SD.esc(fee.description) + '</div>' +
                        '<div class="sd-list-sub">' + SD.money(fee.paid) + (fee.full > 0 ? ' of ' + SD.money(fee.full) : '') +
                        (fee.last ? ' \u00b7 last paid ' + SD.esc(fee.last) : '') + '</div></div>' +
                        '<div class="sd-list-end">' + (fee.outstanding > 0.004 ? '<div style="color:#c42f41">' + SD.money(fee.outstanding) + ' due</div>' : '') +
                        SD.pill(tone[0], tone[1]) + '</div></li>';
                }).join('') + '</ul>', info.fees.length);
            } else {
                html += SD.section('Fees this term', '<p class="sd-empty">No payments recorded this term.</p>');
            }

            if (info.payments.length) {
                html += SD.section('Recent payments', '<ul class="sd-list">' + info.payments.map(function (p) {
                    return '<li><div class="sd-list-main"><div class="sd-list-title">' + SD.esc(p.description || 'Payment') + '</div>' +
                        '<div class="sd-list-sub">' + SD.esc([p.date, p.or ? 'O.R. ' + p.or : '', p.type].filter(Boolean).join(' \u00b7 ')) + '</div></div>' +
                        '<div class="sd-list-end">' + SD.money(p.amount) + '</div></li>';
                }).join('') + '</ul>', info.paymentCount > info.payments.length ? info.payments.length + ' of ' + info.paymentCount : info.payments.length);
            }

            html += SD.section('All terms', SD.kv([
                ['Payments recorded', String(info.paymentCount)],
                ['Total collected', SD.money(info.allTimePaid)],
                ['Email', info.email]
            ]));
            return html;
        }

        return SD.create({
            label: 'Student balance',
            width: 440,
            trigger: options.trigger || '[data-student-balance]',
            render: function (studno, panel) {
                panel.footer(null);
                if (cache[studno]) { panel.body(hero(studno, cache[studno]) + body(cache[studno])); return; }
                panel.loading(hero(studno, null));
                SD.fetchJSON(options.url + '?id=' + encodeURIComponent(studno)).then(function (info) {
                    cache[studno] = info;
                    if (panel.current === studno) panel.body(hero(studno, info) + body(info));
                }).catch(function (err) {
                    if (panel.current === studno) panel.error(err.message, hero(studno, null));
                });
            }
        });
    };
})(window);
