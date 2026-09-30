/*
 * Theme form-plugin setup, loaded on every page by includes/footer_plugins.php.
 *
 * Each plugin is set up only if its library is loaded. The theme's original
 * version called TouchSpin unconditionally, and since no page loads that
 * library it threw "TouchSpin is not a function" on every page and stopped
 * here, before the time picker and color picker (also not loaded).
 */
!function (e) {
    "use strict";

    var i = function () {};

    i.prototype.initSwitchery = function () {
        if (typeof window.Switchery !== "function") return;
        e('[data-plugin="switchery"]').each(function () {
            new Switchery(e(this)[0], e(this).data());
        });
    };

    i.prototype.initSelect2 = function () {
        if (!e.fn.select2) return;
        e('[data-toggle="select2"]').select2();
    };

    i.prototype.initInputmask = function () {
        if (!e.fn.mask) return;
        e('[data-toggle="input-mask"]').each(function (i, t) {
            var n = e(t).data("maskFormat"), o = e(t).data("reverse");
            null != o ? e(t).mask(n, { reverse: o }) : e(t).mask(n);
        });
    };

    i.prototype.initTouchspin = function () {
        if (!e.fn.TouchSpin) return;
        e('[data-toggle="touchspin"]').each(function (i, t) {
            e(t).TouchSpin(e.extend({}, e(t).data()));
        });
    };

    i.prototype.initTimepicker = function () {
        if (!e.fn.timepicker) return;
        var icons = { up: "mdi mdi-chevron-up", down: "mdi mdi-chevron-down" };
        e("#timepicker").timepicker({ defaultTIme: !1, icons: icons });
        e("#timepicker2").timepicker({ showMeridian: !1, icons: icons });
        e("#timepicker3").timepicker({ minuteStep: 15, icons: icons });
    };

    i.prototype.initColorpicker = function () {
        if (!e.fn.colorpicker) return;
        e("#default-colorpicker").colorpicker({ format: "hex" });
        e("#rgba-colorpicker").colorpicker();
        e("#component-colorpicker").colorpicker({ format: null });
    };

    i.prototype.init = function () {
        this.initSwitchery();
        this.initSelect2();
        this.initInputmask();
        this.initTouchspin();
        this.initTimepicker();
        this.initColorpicker();
    };

    e.Components = new i();
    e.Components.Constructor = i;
}(window.jQuery), function () {
    "use strict";
    window.jQuery.Components.init();
}();
