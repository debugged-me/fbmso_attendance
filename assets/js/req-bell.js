(function ensureJQ(){
  function start(){
    if (!window.jQuery) return setTimeout(start, 50);
    (function($){

      (function () {
        if (window.__REQ_BELL_INIT) return;
        window.__REQ_BELL_INIT = true;

        var POLL_MS = 30000;
        var INCLUDE_PROCESSING = false;
        var pollDisabled = false;   // set true if endpoints return 404

        function fmtDate(s) {
          if (!s) return "";
          try {
            return new Date(s.replace(" ", "T")).toLocaleString();
          } catch (e) {
            return s;
          }
        }

        function escapeHtml(value) {
          return String(value == null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
        }

        function renderItem(baseHref, row) {
          var title = escapeHtml(row.document_type || "Document request");
          var meta = escapeHtml("By " + (row.student || "—") + " · " + fmtDate(row.request_date || ""));
          var href = escapeHtml(row.url || baseHref || "#");

          return [
            '<a class="req-item" href="', href, '">',
              '<div class="req-title">', title, '</div>',
              '<div class="req-meta"><span>', meta, '</span></div>',
            '</a>'
          ].join("");
        }

        function refreshCount($b) {
          if (pollDisabled) return;
          var url = $b.data("count-url");
          if (!url) return;
          $.getJSON(url, { include_processing: INCLUDE_PROCESSING ? 1 : 0 }).done(function (resp) {
            var n = Number((resp && resp.count) || 0);
            var $badge = $b.find(".req-badge");
            if (n > 0) { $badge.text(n).show(); } else { $badge.hide().text("0"); }
          }).fail(function (jqXHR) {
            if (jqXHR.status === 404) { pollDisabled = true; }
          });
        }

        function refreshList($b) {
          if (pollDisabled) return;
          var url = $b.data("list-url");
          if (!url) return;

          $.getJSON(url, { include_processing: INCLUDE_PROCESSING ? 1 : 0, limit: 8 }).done(function (resp) {
            var rows = (resp && resp.data) || [];
            var $list = $b.find(".req-list");
            var $empty = $b.find(".req-empty");
            if (!rows.length) { $list.empty(); $empty.show(); return; }
            $empty.hide();
            var baseHref = $b.data("index-url") || "#";
            $list.html(rows.map(function (r) { return renderItem(baseHref, r); }).join(""));
          }).fail(function (jqXHR) {
            if (jqXHR.status === 404) { pollDisabled = true; }
          });
        }

        function tick() {
          $(".req-bell").each(function () {
            var $b = $(this);
            refreshCount($b);
            refreshList($b);
          });
        }

        $(function () {
          tick();
          setInterval(tick, POLL_MS);

          $(document).on("shown.bs.dropdown", ".req-bell", function () {
            var $b = $(this);
            $b.find(".req-badge").text("0").hide();
            var mark = $b.data("markseen-url");
            if (mark) $.post(mark);
          });

          $(document).on("click", ".req-bell .req-list a", function () {
            var $b = $(this).closest(".req-bell");
            $b.find(".req-badge").text("0").hide();
            var mark = $b.data("markseen-url");
            if (mark) $.post(mark);
          });
        });
      })();
      /* ====== END: original req-bell.js logic ====== */

    })(jQuery);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
