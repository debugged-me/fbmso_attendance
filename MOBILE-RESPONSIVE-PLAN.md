# Mobile-First Responsive Shell — Implementation Spec

**Repo:** `/Applications/XAMPP/xamppfiles/htdocs/fbmso_attendance`
**Stack:** CodeIgniter 3 + Bootstrap 4 (Velonic/Hyper admin theme) + jQuery
**Local URL:** `http://localhost/fbmso_attendance/`
**Goal:** Every page renders correctly on any phone or screen size, and the phone
experience reads as a native app — not a desktop site scaled down.

> This document is the complete brief for an implementing agent. Read sections
> 0–2 in full before writing any code. Phases are ordered by dependency and are
> individually shippable.

---

## 0. Rules for the implementing agent

1. **Do not rewrite views.** There are 126 view files. The entire plan is
   delivered through two new shared asset files plus edits to three existing
   includes. Per-view edits happen only in Phase 6, for a named list of 7 files.
2. **Desktop must not change.** Every rule you add lives inside
   `@media (max-width: 767.98px)` unless the spec explicitly says otherwise.
   After each phase, load a page at 1440px wide and confirm it looks identical
   to before. If it does not, your selector is too broad.
3. **Never touch** `system/`, `vendor/`, `assets/css/app*.css`,
   `assets/css/bootstrap*.css`, or any other vendor theme file. Overrides go in
   the new files only, loaded last so specificity wins naturally.
4. **Avoid `!important`.** Load order gives you the win. Use it only where the
   theme itself uses `!important` on the same property (grep first, then note
   why in a comment).
5. **Namespace everything `ms-`** (mobile shell), the way `uk-` is namespaced in
   `assets/css/ui-kit.css`. No bare class names.
6. **Match the house style.** Look at `assets/css/ui-kit.css` and
   `assets/js/ui-kit.js` — commented section banners, IIFE-wrapped vanilla JS,
   no build step, no npm. Follow that exactly. Do not introduce a bundler,
   Sass, Tailwind, or any new dependency.
7. **Test at 320px width after every phase.** If anything causes a horizontal
   scrollbar on `<body>`, that is a bug and blocks the phase.
8. Stop and ask before deleting any existing file.

---

## 1. Verified codebase facts

These were confirmed by inspection. Trust them, but re-verify a file before
editing it.

### The shell is consistent

Almost every page is the same structure:

```
<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>
    <div class="content-page">
      <div class="content">
        <div class="container-fluid">
          ... page content ...
```

| Shared include | Views that use it |
|---|---|
| `application/views/includes/head.php` | 88 |
| `application/views/includes/sidebar.php` | 93 |
| `application/views/includes/top-nav-bar.php` | 93 |
| `application/views/includes/footer.php` | 90 |

**This is the leverage point.** One `<link>` in `head.php` and one `<script>` in
`footer.php` reach ~90% of the app.

### Existing precedent to follow

The repo already does exactly this pattern twice:

- `assets/css/ui-kit.css` (844 lines) + `assets/js/ui-kit.js` (1063 lines) —
  global modal/toast layer, loaded from `includes/ui_kit.php`, which is included
  by `head.php`. Exposes `window.UI`.
- `assets/css/masterlist-responsive.css` (91 lines) +
  `assets/js/masterlist-mobile.js` — table→card flip below 768px, gated on
  `<body class="masterlist-page">`, currently used by 18 views. The JS derives
  `data-label` from `<thead>` and re-applies it via `MutationObserver` when
  DataTables redraws.

**Phase 3 generalizes that second pair to the whole app.** Read both files
before starting; you are extending a working idea, not inventing one.

### What currently breaks on a phone

| Problem | Count | Impact |
|---|---|---|
| Views containing `<table>` | 71 | Only 18 get card treatment today |
| Views using `.table-responsive` (horizontal scroll) | 66 | **Biggest "shrunk desktop" tell** |
| Inline `width: NNNpx` (3+ digits) in views | 81 | Overflows a 360px viewport |
| Views with `.modal` | 32 | Render as centered desktop dialogs |
| `col-md-*` usages | 267 | Stack OK, but fixed-width children inside do not |
| Views that init DataTables | 14 | Desktop-tuned defaults |
| Sidebar `<li>` entries | ~195 across 8 roles | Unusable as a phone menu |

### Roles

`sidebar.php` branches on `$this->session->userdata('level')`:

`Super Admin`, `Admin`, `IT`, `Encoder`, `School Admin`, `Head Registrar`,
`Registrar`, `Accounting`, and `Student` / `Stude Applicant`
(the last two share a branch at `sidebar.php:1238`).

### Views NOT using the shared `head.php` (17)

Handled in Phase 6 only.

*Print / email templates — leave fixed-width, they are correct as-is:*
`accounting_receipt_email.php`, `activity_qr_poster.php`,
`profile_page_print.php`, `stude_profile_print.php`, `email_test.php`,
`Student_qr_model.php`, `bday_month.php`, `bday_today.php`

*User-facing, must be fixed individually in Phase 6:*
`home_page.php`, `lock-screen.php`, `profile_form.php`,
`profile_form_new.php`, `profile_form_update.php`, `profile_page_update.php`,
`registration_form.php`, `student_signup_update.php`,
`settings_department_Subject.php`

---

## 2. Architecture

Two new files carry the whole design:

```
assets/css/mobile-shell.css     <- all responsive rules + design tokens
assets/js/mobile-shell.js       <- drawer, tab bar wiring, table labeling, sheets
application/views/includes/mobile-tabbar.php   <- new, role-aware bottom nav
```

Three existing files get small edits:

```
application/views/includes/head.php      <- viewport meta + one <link>
application/views/includes/footer.php    <- one <script> + one include
application/views/includes/top-nav-bar.php <- add ids/classes only, no restructure
```

**Breakpoints** (match the theme, do not invent new ones):

| Name | Query | Meaning |
|---|---|---|
| phone | `max-width: 767.98px` | App shell active: drawer, tab bar, card tables, sheets |
| tablet | `768px – 991.98px` | Bootstrap defaults, sidebar collapsed |
| desktop | `min-width: 992px` | Unchanged from today |

Note: CSS custom properties **cannot** be used inside `@media` queries. Write
the pixel value literally; define a `--ms-bp` token for documentation only.

---

## Phase 1 — Foundation

*Invisible to the eye. Makes everything after it possible and stops the worst
overflow bugs on its own.*

### 1.1 Create `assets/css/mobile-shell.css`

Start the file with a banner comment in the style of `ui-kit.css`, then:

```css
:root {
  /* Layout metrics */
  --ms-appbar-h: 56px;
  --ms-tabbar-h: 56px;
  --ms-safe-top: env(safe-area-inset-top, 0px);
  --ms-safe-bottom: env(safe-area-inset-bottom, 0px);
  --ms-safe-left: env(safe-area-inset-left, 0px);
  --ms-safe-right: env(safe-area-inset-right, 0px);

  /* Touch */
  --ms-touch: 44px;          /* WCAG 2.5.5 minimum hit area */
  --ms-radius: 16px;
  --ms-radius-sm: 10px;

  /* Motion — reuse ui-kit's curve so the app feels like one system */
  --ms-ease: cubic-bezier(.22, 1, .36, 1);
  --ms-dur: .26s;

  /* Stacking. ui-kit.css owns 20800-21800; stay below it. */
  --ms-z-scrim: 1040;
  --ms-z-drawer: 1050;
  --ms-z-tabbar: 1030;
  --ms-z-appbar: 1035;

  --ms-bp: 767.98px;         /* documentation only — see note in §2 */
}
```

Then the universal safety rules (these apply at **all** widths and are safe):

```css
html { -webkit-text-size-adjust: 100%; }

img, video, canvas, svg, iframe, embed, object {
  max-width: 100%;
  height: auto;
}

body { overflow-wrap: anywhere; }

/* Long unbroken strings (IDs, emails, URLs) are the #1 overflow source */
td, th, .card-body, .dropdown-item { overflow-wrap: anywhere; }

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: .01ms !important;
  }
}
```

Then the phone block foundation:

```css
@media (max-width: 767.98px) {
  html, body { overscroll-behavior-y: contain; }

  body {
    padding-left: var(--ms-safe-left);
    padding-right: var(--ms-safe-right);
  }

  /* Fluid type ramp — one scale from 320px to 4K */
  h1, .h1 { font-size: clamp(1.35rem, 5.5vw, 1.75rem); }
  h2, .h2 { font-size: clamp(1.2rem, 4.8vw, 1.5rem); }
  h3, .h3 { font-size: clamp(1.1rem, 4.2vw, 1.35rem); }
  h4, .h4 { font-size: clamp(1rem, 3.8vw, 1.2rem); }
  h5, .h5 { font-size: clamp(.95rem, 3.4vw, 1.05rem); }

  /* Gutters: tighter than desktop, never zero */
  .content-page > .content > .container-fluid { padding-left: 12px; padding-right: 12px; }
  .card { border-radius: 14px; }
  .card-body { padding: 1rem; }

  /* Neutralize fixed inline widths that would overflow.
     There are 81 of these across the views; this is the blanket fix. */
  .content-page [style*="width"] { max-width: 100% !important; }
  .content-page [style*="min-width"] { min-width: 0 !important; }
}
```

> The last two rules use `!important` deliberately — they must beat inline
> styles. This is the one sanctioned exception. Add a comment saying so.

### 1.2 Full-height units

Anywhere the codebase uses `100vh`, add a `100dvh` line directly after it (older
browsers ignore the one they do not understand). Find them with:

```bash
grep -rn "100vh" application/views assets/css --include="*.php" --include="*.css"
```

Do **not** change vendor theme files — instead override in `mobile-shell.css`.

### 1.3 Edit `application/views/includes/head.php`

Two changes:

**a.** Replace the viewport meta (currently line 4):

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
```

**b.** Add, as the **last** stylesheet before the `ui_kit.php` include:

```html
<link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=1'); ?>">
<meta name="theme-color" content="#1a2942">
```

`#1a2942` matches `.sidebar-logout` in `sidebar.php`. Keep the `?v=` cache
buster and bump it on every change — the repo already does this for
`sora.css?v=20260827` and `uniform-page.css?v=20260827`.

### 1.4 Create `assets/js/mobile-shell.js`

Skeleton only in this phase. IIFE, no jQuery dependency, matching
`masterlist-mobile.js` style:

```js
(function () {
  'use strict';

  var BP = 767.98;
  var isPhone = function () { return window.matchMedia('(max-width: ' + BP + 'px)').matches; };

  var MS = window.MS = {
    isPhone: isPhone,
    modules: {}
  };

  function syncBodyState() {
    document.body.classList.toggle('ms-phone', isPhone());
  }

  function boot() {
    syncBodyState();
    window.addEventListener('resize', syncBodyState, { passive: true });
    Object.keys(MS.modules).forEach(function (k) {
      try { MS.modules[k](); } catch (e) { console.error('[mobile-shell] ' + k, e); }
    });
  }

  document.readyState !== 'loading'
    ? boot()
    : document.addEventListener('DOMContentLoaded', boot);
})();
```

Every later phase registers into `MS.modules`. **A throwing module must never
take the page down** — that is what the try/catch is for.

### 1.5 Edit `application/views/includes/footer.php`

Add next to the existing `masterlist-mobile.js` line:

```html
<script src="<?= base_url('assets/js/mobile-shell.js?v=1'); ?>"></script>
```

### Phase 1 acceptance

- [ ] No horizontal scroll on `<body>` at 320px on: `Page/student`,
      `Page/admin`, `Page/profileList`, `AttendanceLogs`, `activities`
- [ ] 1440px screenshots identical to pre-change
- [ ] `window.MS` exists in the console; no errors
- [ ] Text does not reflow when rotating an iPhone to landscape

---

## Phase 2 — Navigation (app bar, drawer, bottom tab bar)

*This is the phase that makes it feel like an app.*

### 2.1 Compact app bar

`top-nav-bar.php` currently renders `.navbar-custom` with a right-floated
`.topnav-menu` (birthday dropdown, request bell, profile, settings) and a
left `.topnav-menu-left` (hamburger `.button-menu-mobile`, desktop search).

Below 768px:

- Height → `var(--ms-appbar-h)`, `padding-top: var(--ms-safe-top)`
- Keep visible: hamburger (left), page title (center), profile avatar (right)
- Collapse the birthday dropdown, request bell (`req_bell.php`), and the
  right-bar settings toggle into a single `⋮` overflow menu
- The desktop `.app-search` is already `d-none d-lg-block` — leave it
- `position: fixed`; give `.content-page` matching `padding-top`

**Page title:** no view provides one. Derive it in `mobile-shell.js` from, in
order: `document.querySelector('.page-title')`, then `.page-title-box h4`, then
`document.title`. Inject into a `<span class="ms-appbar-title">` that you add to
`top-nav-bar.php` (adding markup there is fine; restructuring is not).

### 2.2 Drawer

`sidebar.php` / `.left-side-menu` already slides in on mobile via the theme's
`.button-menu-mobile`. It is missing everything that makes a drawer feel native.
Add, as `MS.modules.drawer`:

| Feature | Implementation |
|---|---|
| Scrim | `.ms-scrim` div appended to `<body>`, fades in, tap closes |
| Scroll lock | Reuse `html.uk-locked` / `body.uk-locked` from `ui-kit.css` |
| Swipe to close | `touchstart`/`touchmove` on the drawer, translateX follow, close past 40% or on fast flick |
| Edge swipe to open | Optional; only from the left 20px, and only when no `<canvas>`/camera is on screen |
| Escape key | Closes |
| Back button | `history.pushState` on open, `popstate` closes — so Android back dismisses the drawer instead of leaving the page |
| Focus trap | Trap Tab inside the drawer while open; restore focus to the hamburger on close |
| `aria-*` | `aria-expanded` on the hamburger, `role="dialog"` + `aria-modal` on the drawer |

Animate **only** `transform` and `opacity`, per the performance note at the top
of `ui-kit.css`. Never animate `left`/`width`.

Also collapse the sidebar's `metismenu` accordions to closed on phones — 195
`<li>` entries expanded is the current failure. Only the section matching the
current URL opens.

### 2.3 Bottom tab bar — NEW

Create `application/views/includes/mobile-tabbar.php`, and include it from
`footer.php` (which 90 views already load) so it appears everywhere for free:

```php
<?php include(APPPATH . 'views/includes/mobile-tabbar.php'); ?>
```

**Markup:** `<nav class="ms-tabbar" role="navigation">` containing 4–5
`<a class="ms-tab">` items, each an `mdi` icon (already loaded) + a short label.
Fixed to the bottom, `height: calc(var(--ms-tabbar-h) + var(--ms-safe-bottom))`,
with matching bottom padding. Hidden at `min-width: 768px`.

**In `mobile-shell.js`, move the tab bar to `<body>` on load** — the same trick
`footer.php` already uses for `#fbmsoVisionMissionModal` — so no ancestor
transform or `overflow:hidden` can trap it.

**Active state:** compare `window.location.pathname` against each tab's href;
mark the longest prefix match `.is-active`.

**Content clearance:** add
`padding-bottom: calc(var(--ms-tabbar-h) + var(--ms-safe-bottom) + 8px)` to
`.content-page` below 768px, and hide the desktop `<footer class="footer">`
there (its copyright modal trigger moves into the drawer).

**Role map.** Branch on `$this->session->userdata('level')`, mirroring the
structure in `sidebar.php`. Routes below are verified from `sidebar.php`:

| Level | Tabs (href → label, icon) |
|---|---|
| `Student`, `Stude Applicant` | `Page/student` Home `mdi-home-variant-outline` · `student/my_qr` My QR `mdi-qrcode` · `Page/studentAccountingRecords` Payments `mdi-cash-multiple` · `Page/studentProfile` Profile `mdi-account-circle-outline` · drawer trigger More `mdi-menu` |
| `Admin` | `Page/admin` Home · `AttendanceLogs` Attendance · `activities` Activities · `Page/announcement` News · More |
| `Registrar`, `Head Registrar` | `Page/registrar` Home · `Page/profileList` Students · `Page/requestSummary` Requests · More |
| `Encoder` | `Page/encoder` Home · `Page/profileListEncoder` Students · More |
| `School Admin` | `Page/school_admin` Home · Masterlist · Reports · More |
| `IT` | `Page/IT` Home · `Page/userAccounts` Users · More |
| `Super Admin` | `Page/superAdmin` Home · `AttendanceLogs` Attendance · More |
| `Accounting` | Read the block at `sidebar.php:978` and use that role's dashboard route + its two most-used links |

**Rules:** never more than 5 tabs. The last tab is always **More**, which opens
the drawer. Any destination not in the tab bar remains reachable from the
drawer — the tab bar is a shortcut layer, never the only path.

### Phase 2 acceptance

- [ ] Tab bar visible on every page below 768px, hidden at 768px+
- [ ] Correct tabs for each of the 9 role values (test by changing the session
      `level`, or read the value and stub locally — do not modify auth code)
- [ ] Drawer: scrim, swipe-close, Escape, Android back all work
- [ ] Background does not scroll while the drawer is open
- [ ] Nothing is hidden behind the tab bar at the bottom of a long page
- [ ] Tab bar clears the iPhone home indicator (test in a device emulator with a
      notch profile)

---

## Phase 3 — Tables (highest value; 71 views)

### 3.1 Generalize the card flip

Read `assets/css/masterlist-responsive.css` first — the technique is already
correct, it is just scoped to one body class.

In `mobile-shell.css`, port those rules to a `.ms-rt` class:

```css
@media (max-width: 767.98px) {
  .ms-rt thead { display: none; }
  .ms-rt tbody { display: block; }
  .ms-rt tbody tr {
    display: block;
    margin-bottom: 1rem;
    border: 1px solid rgba(226,232,240,.9);
    border-radius: 12px;
    padding: .85rem 1rem;
    background: #fff;
    box-shadow: 0 10px 30px rgba(15,23,42,.08);
  }
  .ms-rt tbody td {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: .35rem 0;
    border: 0 !important;
    font-size: .92rem;
  }
  .ms-rt tbody td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #6c757d;
    flex: 0 0 45%;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-size: .78rem;
  }
  .ms-rt tbody td[data-label='']::before { content: ''; }
  .ms-rt tbody td.control { display: none !important; }

  /* A card table must not also be a horizontal scroller */
  .table-responsive:has(.ms-rt) { overflow-x: visible; }
}
```

If `:has()` support is a concern, have the JS add `.ms-rt-host` to the parent
`.table-responsive` instead and style that. **Prefer the JS class** — it is the
safer choice for older Android WebViews.

### 3.2 Auto-apply, app-wide

Extend `masterlist-mobile.js`'s logic into `MS.modules.tables`:

1. Select every `table` inside `.content-page`.
2. **Skip** a table if it has `.ms-rt-keep`, `data-ms-cards="off"`, or is
   nested inside another table, or has fewer than 2 `<thead> th`.
3. Skip if the table has more than **10** columns — those are matrix reports
   (grade sheets, wide masterlists) that read better as a scroller. Give those
   a "Swipe to see more →" affordance instead: a small hint line above and a
   right-edge fade, both auto-hiding after the first scroll.
4. Otherwise add `.ms-rt`, copy `<thead> th` text into each cell's `data-label`
   (exact logic already in `masterlist-mobile.js`), and attach the same
   `MutationObserver` so DataTables redraws stay labeled.
5. Keep `masterlist-page` working — that body class should now simply be a
   no-op alias, with the rules coming from `.ms-rt`.

Row-level actions (the button clusters in the last column) should collapse into
a single `⋮` menu per card so a row never wraps into a wall of buttons.

### 3.3 DataTables mobile defaults

The 14 views that call `.DataTable(` each pass their own options. Do **not**
edit them. Instead set global defaults in `mobile-shell.js`, guarded on
`window.jQuery && $.fn.dataTable`:

```js
if (isPhone() && window.jQuery && $.fn.dataTable) {
  $.extend(true, $.fn.dataTable.defaults, {
    pageLength: 10,
    lengthChange: false,
    // 'f' (filter) is moved into the app bar; 'l' and 'i' hidden per masterlist-responsive.css
    dom: '<"ms-dt-top"f>rt<"ms-dt-bottom"p>'
  });
}
```

This must run **before** any view's inline `.DataTable()` call. Note that
`mobile-shell.js` currently loads from `footer.php`, which is late. **Load a
small `assets/js/mobile-shell-early.js` from `head.php`** containing only the
DataTables defaults block, or move the whole script to `head.php` without
`defer` — matching the reasoning already documented in `includes/ui_kit.php`
about inline view scripts running during parse.

### 3.4 Search into the app bar

The DataTables filter input is the primary interaction on list pages. On phones,
render it as a full-width pill directly under the app bar, sticky, rather than
above the table. `.ms-dt-top` styles that.

### Phase 3 acceptance

- [ ] `Page/profileList`, `AttendanceLogs`, and 5 randomly picked table views
      render as cards at 390px with correct labels
- [ ] DataTables paging/search still work; labels survive a page change
- [ ] Wide report tables scroll horizontally *inside their container* with a
      visible swipe hint, and never widen `<body>`
- [ ] No table view scrolls the page horizontally at 320px

---

## Phase 4 — Forms, inputs, touch

### 4.1 Stop iOS zoom-on-focus

**This single rule removes most of the "shrunk website" feeling.** iOS Safari
zooms the viewport whenever a focused input's font-size is under 16px, and the
theme's `.form-control` is smaller than that.

```css
@media (max-width: 767.98px) {
  input, select, textarea,
  .form-control, .form-control-sm, .custom-select {
    font-size: 16px;      /* must be >= 16px. Do not use rem here. */
    min-height: var(--ms-touch);
    border-radius: var(--ms-radius-sm);
  }
}
```

### 4.2 Touch targets

```css
@media (max-width: 767.98px) {
  .btn, .dropdown-item, .nav-link, .page-link,
  .left-side-menu a, table td a.btn {
    min-height: var(--ms-touch);
    display: inline-flex;
    align-items: center;
  }
  a, button, .btn { -webkit-tap-highlight-color: transparent; }
  .btn:active { transform: scale(.97); }
  .btn-block + .btn-block { margin-top: .5rem; }
}
```

### 4.3 Stacked layout and sticky actions

- All `.btn` inside a `.card-footer`, `.modal-footer`, or form action row →
  full width, stacked, primary action first (visually top on mobile).
- Long forms: make the submit row `position: sticky; bottom: calc(var(--ms-tabbar-h) + var(--ms-safe-bottom))`
  so Save is always reachable without scrolling to the end.
- Kill fixed widths on selects — e.g. `min-width:240px` on the camera picker at
  `application/views/scan_page.php:58`. The blanket rule from Phase 1.1 covers
  it, but fix that one inline too since Phase 6 touches the file anyway.

### 4.4 Keyboards

Sweep the form views and set correct input attributes. Grep for `type="text"`
on fields whose name/id suggests a number, phone, or email:

| Field kind | Attributes |
|---|---|
| ID number / student number | `inputmode="numeric" pattern="[0-9]*"` |
| Phone / contact | `type="tel" inputmode="tel" autocomplete="tel"` |
| Email | `type="email" inputmode="email" autocomplete="email"` |
| Amounts | `inputmode="decimal"` |
| Password | `autocomplete="current-password"` / `"new-password"` |

Keep `type="text"` where a leading zero matters — `type="number"` strips it.

### Phase 4 acceptance

- [ ] Tapping any input on a real iPhone (or iOS simulator) does **not** zoom
- [ ] Every tappable element is ≥44×44px at 390px
- [ ] Numeric keypad appears for ID-number fields
- [ ] Save button reachable without scrolling on the longest form
      (`profile_form_new.php` after Phase 6)

---

## Phase 5 — Modals become bottom sheets

32 views contain modals, plus `ui-kit.js`'s own modal and the two in
`top-nav-bar.php` (`#changeProfilePicModal`, `#changePasswordModal`). One global
restyle covers all of them — **no markup changes**.

```css
@media (max-width: 767.98px) {
  .modal-dialog {
    margin: 0;
    position: fixed;
    left: 0; right: 0; bottom: 0;
    max-width: 100%;
    transform: translateY(100%);
    transition: transform var(--ms-dur) var(--ms-ease);
  }
  .modal.show .modal-dialog { transform: translateY(0); }
  .modal-content {
    border-radius: 20px 20px 0 0;
    max-height: 90dvh;
    max-height: 90vh;      /* fallback first-declared order matters — put vh above dvh */
  }
  .modal-body { overflow-y: auto; -webkit-overflow-scrolling: touch; }
  .modal-dialog-centered { align-items: flex-end; }
}
```

Add a grab handle (`.ms-sheet-handle`, a 36×4px rounded bar) via CSS
`::before` on `.modal-content`, plus drag-down-to-dismiss in
`MS.modules.sheets`: `touchmove` translates the sheet, release past 25% height
or a downward flick calls Bootstrap's `$(modal).modal('hide')`.

Apply the same treatment to `ui-kit.css`'s `.uk-backdrop` dialog. Check
`ui-kit.css` for its existing mobile rules first and extend rather than fight
them.

`z-index` note: `#fbmsoVisionMissionModal` is pinned to `20000` and ui-kit uses
`21000+`. The tab bar at `1030` sits below all of them, which is correct — a
sheet must cover the tab bar.

### Phase 5 acceptance

- [ ] Every modal slides up from the bottom at 390px
- [ ] Long modal bodies scroll internally; the page behind does not
- [ ] Drag-down dismisses; Bootstrap's `hidden.bs.modal` still fires
- [ ] `UI.confirm()` / `UI.alert()` from `ui-kit.js` still work

---

## Phase 6 — Scan page, PWA, and the sweep

### 6.1 Scan page (`application/views/scan_page.php`)

This is the app's most phone-native flow and deserves hand-tuning:

- Camera viewport full-bleed: edge-to-edge width, `aspect-ratio: 1`, rounded
  corners, with a scanning reticle overlay
- Big primary action zone at the bottom, above the tab bar
- `navigator.vibrate(60)` plus the existing audio cue on a successful scan
- Add `playsinline` and `muted` to the `<video>` the html5-qrcode lib injects
  (it usually does, but verify — without `playsinline`, iOS goes fullscreen)
- Hide the tab bar while the scanner is running, to maximise viewport
- Fix the fixed-width `#cameraSelect` (line 58)

**Blocker to flag:** the page already warns at line 485 that *"iOS camera
requires HTTPS or localhost."* Confirm production serves HTTPS. If it does not,
this flow simply cannot work on iPhone and the responsive work will not save it.
Report this to the user rather than working around it.

### 6.2 Installable web app

Add so it launches chrome-free from the home screen:

- `manifest.webmanifest` at repo root: `name`, `short_name` ("Attendance"),
  `start_url` (respecting the `base_url` logic in
  `application/config/config.php:38` — the app is served from a subdirectory
  `/fbmso_attendance/` in dev), `display: "standalone"`,
  `background_color`, `theme_color: "#1a2942"`, icons at 192/512
  (source: `assets/images/Attendance.png`)
- In `head.php`: `<link rel="manifest">`, `apple-mobile-web-app-capable`,
  `apple-mobile-web-app-status-bar-style="black-translucent"`,
  `apple-touch-icon`
- Confirm `.htaccess` serves `.webmanifest` with
  `application/manifest+json`; add the MIME type if missing

Do **not** add a service worker in this phase. Offline caching for a
session-authenticated CI app is a separate project with real logout/staleness
risks.

### 6.3 The 9 orphan views

Bring these under the shared layer. For each, add
`<?php include('includes/head.php'); ?>` **if** its `<head>` is generic; if it
has genuinely page-specific `<head>` content, instead add just the viewport meta
and the `mobile-shell.css` link:

`home_page.php`, `lock-screen.php`, `profile_form.php`, `profile_form_new.php`,
`profile_form_update.php`, `profile_page_update.php`, `registration_form.php`,
`student_signup_update.php`, `settings_department_Subject.php`

`registration_form.php` and `profile_form_new.php` are the long public-facing
forms — they get the most attention. `home_page.php` is the public landing page
and uses `includes/header.php` (the marketing header with `.nav-menu
d-none d-lg-block`, which has **no mobile menu at all** — add one).

Leave the 8 print/email templates alone.

### 6.4 Final sweep

```bash
# Remaining fixed widths worth fixing at source
grep -rn 'width: *[0-9]\{3,\}px' application/views/*.php

# Tables that opted out — confirm each is deliberate
grep -rn 'ms-rt-keep' application/views/

# Any leftover 100vh
grep -rn '100vh' application/views assets/css
```

---

## Verification

### Widths to test every phase

`320` (iPhone SE) · `360` (common Android) · `390` (iPhone 14) · `430` (Pro Max)
· `768` (iPad portrait) · `1024` · `1440`

Plus: **phone landscape**, **200% browser zoom**, and **one pass with the
on-screen keyboard open** on a long form.

### Pages to smoke-test

| Page | Route | Why |
|---|---|---|
| Student dashboard | `Page/student` | Most-used student screen; card grid |
| Admin dashboard | `Page/admin` | Widget density |
| Student list | `Page/profileList` | DataTables + card flip |
| Attendance logs | `AttendanceLogs` | Largest table |
| Scanner | scan page route | Camera, the flagship mobile flow |
| Activities | `activities` | Modals |
| A masterlist | any `masterlist_*` | Regression check on existing mobile CSS |
| Public landing | `home_page.php` route | Orphan view, no shared head |

### Definition of done

1. No page scrolls horizontally at 320px.
2. No input triggers iOS zoom on focus.
3. Every page has a bottom tab bar with correct role-appropriate tabs.
4. Every list is readable without pinch-zoom or horizontal scroll (or has an
   explicit, deliberate swipe affordance).
5. Every modal is a bottom sheet on phones.
6. Desktop at 1440px is visually identical to the pre-change build.
7. Nothing sits under the notch or the home indicator.
8. No JS console errors on any smoke-test page.

---

## Out of scope

- Service worker / offline mode
- Redesigning page *content* or information architecture — this is a shell and
  layout project
- Touching `srms-college` (explicitly excluded by the user)
- Any build tooling, framework, or new runtime dependency
- The 8 print/email templates
- Dark mode (the theme ships `app-dark.css`; wiring it up is a separate task)

---

## Progress checklist

- [ ] **Phase 1** — Foundation: tokens, safety rules, viewport, two new files wired
- [ ] **Phase 2** — Navigation: compact app bar, real drawer, role-aware tab bar
- [ ] **Phase 3** — Tables: `.ms-rt` app-wide, auto-labels, DataTables defaults
- [ ] **Phase 4** — Forms: 16px inputs, 44px targets, sticky actions, keyboards
- [ ] **Phase 5** — Modals become bottom sheets
- [ ] **Phase 6** — Scan page, PWA manifest, 9 orphan views, final sweep
