<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Adds the CSRF token to every POST form in the rendered HTML.
 *
 * This codebase has 31 hand-written <form method="post"> tags across 27 view
 * files and does not use form_open(), so CodeIgniter inserts nothing for us.
 * Editing all 31 would work once and then rot: the 32nd form somebody adds
 * next month would silently be unprotected, and a form that posts without a
 * token does not fail quietly -- it 403s in the user's face.
 *
 * Injecting at the output layer covers every form that exists now and every
 * one added later, with no discipline required from whoever writes it.
 *
 * Runs on display_override so it sees the finished page. HTML only: JSON and
 * file downloads are passed through untouched.
 */
class CsrfInjectHook
{
    public function inject()
    {
        $CI = &get_instance();
        $output = $CI->output->get_output();

        if ($this->shouldRewrite($CI, $output)) {
            $output = $this->addTokens($CI, $output);
            $output = $this->addHeadTags($CI, $output);
        }

        $CI->output->_display($output);
    }

    /** Only touch HTML responses that actually contain a POST form. */
    protected function shouldRewrite($CI, $output)
    {
        if (!config_item('csrf_protection')) {
            return false;
        }

        // Needs to run on any HTML page, not just ones with a form: pages
        // with only AJAX POSTs still need the meta tag and the script.
        if ($output === '' || stripos($output, '</head>') === false) {
            return false;
        }

        // Skip non-HTML responses (JSON endpoints, downloads). CI_Output has
        // get_content_type(), not get_headers() -- calling the latter throws,
        // and because a failed display_override hook makes CodeIgniter fall
        // back to its own _display(), the page still renders and the failure
        // is completely silent. Worth knowing when debugging this hook.
        $type = (string)$CI->output->get_content_type();
        if ($type !== '' && stripos($type, 'html') === false) {
            return false;
        }

        return true;
    }

    protected function addTokens($CI, $output)
    {
        $name  = (string)$CI->security->get_csrf_token_name();
        $value = (string)$CI->security->get_csrf_hash();

        if ($name === '' || $value === '') {
            return $output;
        }

        $field = '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
               . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">';

        // Match opening <form> tags whose method is post. GET forms are left
        // alone: CodeIgniter does not check them, and a stray token in a GET
        // form would end up in the URL bar.
        return preg_replace_callback(
            '#<form\b[^>]*>#i',
            function ($m) use ($field, $name) {
                $tag = $m[0];

                if (!preg_match('#\bmethod\s*=\s*["\']?post["\']?#i', $tag)) {
                    return $tag;
                }

                // A form that already carries the token (hand-written, or a
                // page rendered through this hook twice) must not get a second.
                if (stripos($tag, $name) !== false) {
                    return $tag;
                }

                return $tag . $field;
            },
            $output
        );
    }

    /**
     * Publish the token to JavaScript and load the helper that attaches it to
     * AJAX POSTs. Injected here because the app has no shared header view --
     * 27 view files would otherwise each need editing, and any new one would
     * be missed.
     */
    protected function addHeadTags($CI, $output)
    {
        if (stripos($output, 'name="csrf-token"') !== false) {
            return $output;
        }

        $name  = (string)$CI->security->get_csrf_token_name();
        $value = (string)$CI->security->get_csrf_hash();

        if ($name === '' || $value === '') {
            return $output;
        }

        $tags = '<meta name="csrf-token-name" content="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">'
              . '<meta name="csrf-token" content="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">'
              . '<script src="' . htmlspecialchars(base_url('assets/js/csrf.js?v=2'), ENT_QUOTES, 'UTF-8') . '"></script>';

        // Before </head> so the prefilter is registered before page scripts
        // start firing requests.
        $pos = stripos($output, '</head>');

        return $pos === false
            ? $output
            : substr($output, 0, $pos) . $tags . substr($output, $pos);
    }
}
