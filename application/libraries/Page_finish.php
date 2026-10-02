<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Last pass over every HTML page before it is sent.
 *
 * Cashiers on phones saw each page arrive as a draft that JavaScript then
 * rearranged a second later — the app bar switched from the school name to
 * the page title, the bottom tab bar popped in, the list jumped — and read
 * it as the system glitching. This pass makes the first paint already look
 * finished, and covers what is still being set up with a loading state:
 *
 *  - Local CSS/JS URLs get ?v=<mtime><size>, so browsers keep them for a
 *    year (see assets/js/.htaccess) instead of re-checking ~24 files on every
 *    page view, yet still fetch a changed file the moment it is deployed.
 *  - The app bar shows the page's own title from the start, read the same
 *    way mobile-shell.js reads it.
 *  - The bottom tab bar is moved to the top of <body>, so it paints with the
 *    first frame instead of after the whole page has been parsed (it is
 *    position: fixed, and mobile-shell.js re-parents it to <body> anyway).
 *  - A loading state: a progress bar, and the content area fading in once
 *    the page's scripts have set it up. It also shows while leaving a page,
 *    so a tap gets an immediate response.
 */
class Page_finish
{
    public function apply($html)
    {
        if (!is_string($html) || $html === '' || stripos($html, '</head>') === false) {
            return $html;
        }

        foreach (['versionAssets', 'appBarTitle', 'hoistTabbar', 'addLoadingState'] as $step) {
            $next = $this->$step($html);
            // A regex that hits a PCRE limit returns null. Skip that step
            // rather than send a blank page.
            if (is_string($next) && $next !== '') {
                $html = $next;
            }
        }

        return $html;
    }

    // ------------------------------------------------------------------
    // Asset versions
    // ------------------------------------------------------------------

    protected function versionAssets($html)
    {
        $base = rtrim((string)base_url(), '/') . '/';
        $basePath = rtrim((string)parse_url($base, PHP_URL_PATH), '/') . '/';
        $versions = [];

        return preg_replace_callback(
            '#\b(src|href)=(["\'])([^"\'?\#]*?assets/(?:js|css)/[^"\'?\#]+\.(?:js|css))(?:\?[^"\'\#]*)?\2#i',
            function ($m) use ($base, $basePath, &$versions) {
                $url = $m[3];

                if (strpos($url, $base) === 0) {
                    $rel = substr($url, strlen($base));
                } elseif (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $url)) {
                    return $m[0]; // another host
                } elseif ($basePath !== '/' && strpos($url, $basePath) === 0) {
                    $rel = substr($url, strlen($basePath));
                } else {
                    $rel = ltrim($url, './');
                }

                if (!isset($versions[$rel])) {
                    $file = FCPATH . $rel;
                    $versions[$rel] = is_file($file)
                        ? dechex((int)filemtime($file)) . dechex((int)filesize($file))
                        : '';
                }
                if ($versions[$rel] === '') {
                    return $m[0];
                }

                return $m[1] . '=' . $m[2] . $url . '?v=' . $versions[$rel] . $m[2];
            },
            $html
        );
    }

    // ------------------------------------------------------------------
    // App bar title
    // ------------------------------------------------------------------

    protected function appBarTitle($html)
    {
        if (strpos($html, 'ms-appbar-title') === false) {
            return $html;
        }

        $title = $this->pageTitle($html);
        if ($title === '') {
            return $html;
        }

        return preg_replace_callback(
            '#(<span\b[^>]*\bclass="(?:[^"]*\s)?ms-appbar-title(?:\s[^"]*)?"[^>]*>)(.*?)(</span>)#s',
            function ($m) use ($title) {
                return $m[1] . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . $m[3];
            },
            $html,
            1
        );
    }

    /** Same order as mobile-shell.js: .page-title, .page-title-box h4, .up-page-title, <title>. */
    protected function pageTitle($html)
    {
        $inner = $this->firstElementWithClass($html, 'page-title');

        if ($inner === null) {
            $box = $this->firstElementWithClass($html, 'page-title-box', 'div');
            if ($box !== null && preg_match('#<h4\b[^>]*>(.*?)</h4>#is', $box, $h4)) {
                $inner = $h4[1];
            }
        }

        if ($inner === null) {
            $inner = $this->firstElementWithClass($html, 'up-page-title');
        }

        // No title element: the script falls back to document.title.
        if ($inner === null && preg_match('#<title\b[^>]*>(.*?)</title>#is', $html, $docTitle)) {
            $inner = $docTitle[1];
        }

        if ($inner === null) {
            return '';
        }

        $text = html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string)preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Inner HTML of the first element carrying $class as a whole class token.
     * Nested elements of the same tag are balanced, so a <div> box keeps its
     * inner <div>s.
     */
    protected function firstElementWithClass($html, $class, $tag = '[a-z][a-z0-9]*')
    {
        $pattern = '#<(' . $tag . ')\b[^>]*\bclass="(?:[^"]*\s)?' . preg_quote($class, '#') . '(?:\s[^"]*)?"[^>]*>#i';
        if (!preg_match($pattern, $html, $open, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $name = strtolower($open[1][0]);
        $start = $open[0][1] + strlen($open[0][0]);
        $depth = 1;
        $pos = $start;

        while ($depth > 0 && preg_match('#<(/?)' . $name . '\b[^>]*>#i', $html, $tagMatch, PREG_OFFSET_CAPTURE, $pos)) {
            $depth += $tagMatch[1][0] === '/' ? -1 : 1;
            $pos = $tagMatch[0][1] + strlen($tagMatch[0][0]);
            if ($depth === 0) {
                return substr($html, $start, $tagMatch[0][1] - $start);
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Bottom tab bar
    // ------------------------------------------------------------------

    protected function hoistTabbar($html)
    {
        if (!preg_match('#<nav\b[^>]*\bclass="ms-tabbar"[^>]*>.*?</nav>#s', $html, $nav, PREG_OFFSET_CAPTURE)) {
            return $html;
        }
        if (!preg_match('#<body\b[^>]*>#i', $html, $body, PREG_OFFSET_CAPTURE) || $body[0][1] > $nav[0][1]) {
            return $html;
        }

        $html = substr_replace($html, '', $nav[0][1], strlen($nav[0][0]));
        $at = $body[0][1] + strlen($body[0][0]);

        return substr_replace($html, $nav[0][0], $at, 0);
    }

    // ------------------------------------------------------------------
    // Loading state
    // ------------------------------------------------------------------

    protected function addLoadingState($html)
    {
        if (strpos($html, 'id="pl-style"') !== false) {
            return $html;
        }

        $head = '<style id="pl-style">' . $this->loadingCss() . '</style>'
              . '<script>' . $this->loadingJs() . '</script>';
        $pos = stripos($html, '</head>');
        $html = substr_replace($html, $head, $pos, 0);

        if (!preg_match('#<body\b[^>]*>#i', $html, $body, PREG_OFFSET_CAPTURE)) {
            return $html;
        }
        // The spinner stands in for the content area, so only pages that have one get it.
        $marks = '<div class="pl-bar" aria-hidden="true"></div>';
        if (strpos($html, 'class="content-page"') !== false) {
            $marks .= '<div class="pl-spin" role="status" aria-live="polite"><span class="pl-spin-ring"></span><span class="pl-spin-text">Loading…</span></div>';
        }

        return substr_replace($html, $marks, $body[0][1] + strlen($body[0][0]), 0);
    }

    /*
     * The content area is hidden only by opacity, so the page lays out at its
     * real size while scripts build tables and widgets; it fades in once they
     * are done. The spinner waits 350 ms before showing, so a fast page never
     * flashes it.
     */
    protected function loadingCss()
    {
        return '.pl-bar{position:fixed;top:0;left:0;right:0;height:3px;z-index:2147483000;pointer-events:none;overflow:hidden;opacity:0;transition:opacity .25s ease}'
            . '.pl-bar::before{content:"";position:absolute;top:0;bottom:0;left:-45%;width:45%;border-radius:3px;background:linear-gradient(90deg,rgba(42,64,144,0),#2a4090 40%,#5b7cf0)}'
            . 'html.pl-loading .pl-bar,html.pl-leaving .pl-bar{opacity:1}'
            . 'html.pl-loading .pl-bar::before,html.pl-leaving .pl-bar::before{animation:pl-slide 1.1s cubic-bezier(.4,0,.2,1) infinite}'
            . '@keyframes pl-slide{to{left:100%}}'
            . '.content-page>.content{transition:opacity .22s ease}'
            . 'html.pl-loading .content-page>.content{opacity:0;pointer-events:none}'
            . 'html.pl-leaving .content-page>.content{opacity:.5;transition-duration:.15s}'
            . '.pl-spin{position:fixed;top:50%;left:50%;z-index:2147482999;display:flex;flex-direction:column;align-items:center;gap:10px;transform:translate(-50%,-50%);pointer-events:none;opacity:0;visibility:hidden;transition:opacity .2s ease,visibility 0s linear .2s}'
            . 'html.pl-loading .pl-spin{opacity:1;visibility:visible;transition:opacity .25s ease .35s,visibility 0s linear .35s}'
            . '.pl-spin-ring{width:34px;height:34px;border-radius:50%;border:3px solid rgba(42,64,144,.16);border-top-color:#2a4090;animation:pl-rot .8s linear infinite}'
            . '.pl-spin-text{font:600 12px/1 system-ui,-apple-system,"Segoe UI",sans-serif;letter-spacing:.02em;color:#6b7a99}'
            . '@keyframes pl-rot{to{transform:rotate(360deg)}}'
            . '@media (min-width:992px){body:not(.enlarged) .pl-spin{margin-left:120px}}'
            . '@media (prefers-reduced-motion:reduce){.pl-bar::before{left:0;width:100%;animation:none!important}.pl-spin-ring{animation-duration:2.4s}.content-page>.content{transition:none}}'
            . '@media print{.pl-bar,.pl-spin{display:none!important}html.pl-loading .content-page>.content{opacity:1}}';
    }

    /*
     * Reveal after DOMContentLoaded and after every jQuery ready handler the
     * page registered (DataTables, select2, the mobile shell), then two frames
     * so the finished layout is painted before it fades in. Never later than
     * 5 s, whatever happens.
     */
    protected function loadingJs()
    {
        return '(function(d,w){var h=d.documentElement,t;h.classList.add("pl-loading");'
            . 'function show(){h.classList.remove("pl-loading")}'
            . 'function settle(){var go=function(){w.requestAnimationFrame(function(){w.requestAnimationFrame(show)})};'
            . 'var $=w.jQuery;if($&&$.fn&&$.fn.jquery){$(function(){w.setTimeout(go,0)})}else{go()}}'
            . 'if(d.readyState==="loading"){d.addEventListener("DOMContentLoaded",settle)}else{settle()}'
            . 'w.setTimeout(show,5000);'
            . 'function stay(){w.clearTimeout(t);h.classList.remove("pl-leaving")}'
            . 'w.addEventListener("beforeunload",function(){h.classList.add("pl-leaving");w.clearTimeout(t);t=w.setTimeout(stay,8000)});'
            . 'w.addEventListener("pageshow",function(e){stay();if(e.persisted){show()}});'
            . 'd.addEventListener("pointerdown",stay,true);'
            . '})(document,window);';
    }
}
