<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * AuthGuard
 *
 * One place that answers "is this request allowed?".
 *
 * The hook in application/hooks/AuthGuard.php calls enforce() on every
 * request, so protection does not depend on each controller remembering to
 * check the session. Controllers can also call the helpers here directly
 * (require_level(), is_logged_in(), user(), ...) instead of re-reading
 * session keys by hand.
 */
class Authguard
{
    /** @var CI_Controller */
    protected $CI;

    protected $public_routes  = array();
    protected $role_rules     = array();
    protected $student_levels = array();
    protected $student_routes = array();
    protected $login_route   = 'login';
    protected $idle_timeout  = 0;

    protected $key_flag = 'logged_in';
    protected $key_user = 'username';
    protected $key_role = 'level';

    public function __construct()
    {
        $this->CI = &get_instance();

        // The session may not be loaded yet on early requests.
        if (!isset($this->CI->session)) {
            $this->CI->load->library('session');
        }
        $this->CI->load->helper('url');

        $this->CI->config->load('authguard', true, true);
        $cfg = $this->CI->config->item('authguard');

        // config->load() with a section returns the array; fall back to
        // top-level items if the file was merged instead of sectioned.
        $get = function ($key, $default) use ($cfg) {
            if (is_array($cfg) && array_key_exists($key, $cfg)) {
                return $cfg[$key];
            }
            $item = get_instance()->config->item($key);
            return ($item === null || $item === false) ? $default : $item;
        };

        $this->public_routes  = (array)$get('authguard_public', array());
        $this->role_rules     = (array)$get('authguard_roles', array());
        $this->student_levels = (array)$get('authguard_student_levels', array());
        $this->student_routes = (array)$get('authguard_student_routes', array());
        $this->login_route   = (string)$get('authguard_login_route', 'login');
        $this->idle_timeout  = (int)$get('authguard_idle_timeout', 0);
        $this->key_flag      = (string)$get('authguard_session_flag', 'logged_in');
        $this->key_user      = (string)$get('authguard_session_user', 'username');
        $this->key_role      = (string)$get('authguard_session_role', 'level');
    }

    // ------------------------------------------------------------------
    // Entry point (called by the hook)
    // ------------------------------------------------------------------

    /**
     * Gate the current request. Sends a redirect or an error response and
     * halts when the request is not allowed.
     */
    public function enforce()
    {
        $route = $this->current_route();

        // CLI runs (migrations, cron) are never gated.
        if (is_cli()) {
            return;
        }

        if ($this->is_public($route)) {
            return;
        }

        if (!$this->is_logged_in()) {
            $this->reject_unauthenticated();
            return;
        }

        // A revoked session must stop working immediately, not whenever it
        // happens to expire. Sessions live in files, so this is the only
        // place the check can happen.
        $this->CI->load->library('sessionregistry');
        if ($this->CI->sessionregistry->isCurrentRevoked()) {
            $this->CI->session->sess_destroy();
            $this->reject_unauthenticated('Your session was ended. Please sign in again.');
            return;
        }

        // Only touch the session when the timeout is actually in use — an
        // unconditional write here would cost a session file lock + write on
        // every single request for no benefit.
        if ($this->idle_timeout > 0) {
            if ($this->is_idle_expired()) {
                $this->CI->session->sess_destroy();
                $this->reject_unauthenticated('Your session expired after a period of inactivity.');
                return;
            }
            $this->touch_activity();
        }

        // Student accounts are deny-by-default: only the routes on the student
        // list are theirs. This is checked before the role map, because a
        // student must never fall through to "no rule, therefore allowed".
        if ($this->is_student()) {
            if (!$this->student_may($route)) {
                $this->reject_forbidden(array('a staff account'));
            }
            return;
        }

        $allowed = $this->rule_for($route, $this->role_rules);
        if ($allowed !== null && !$this->has_level($allowed)) {
            $this->reject_forbidden($allowed);
        }
    }

    /** Is the signed-in account a student rather than staff? */
    public function is_student()
    {
        return $this->student_levels && $this->has_level($this->student_levels);
    }

    /** May a student account open this route? */
    public function student_may($route = null)
    {
        $route = ($route === null) ? $this->current_route() : strtolower($route);

        foreach ($this->student_routes as $pattern) {
            if ($this->route_matches($route, $pattern)) {
                return true;
            }
        }
        return false;
    }

    // ------------------------------------------------------------------
    // Public helpers for controllers
    // ------------------------------------------------------------------

    public function is_logged_in()
    {
        $flag = $this->CI->session->userdata($this->key_flag);
        $user = (string)$this->CI->session->userdata($this->key_user);

        return ($flag === true || $flag === 1 || $flag === '1' || $flag === 'TRUE')
            && $user !== '';
    }

    /** Current user's level/position, '' when signed out. */
    public function level()
    {
        return trim((string)$this->CI->session->userdata($this->key_role));
    }

    /** The whole session payload, handy for views. */
    public function user()
    {
        return array(
            'username' => (string)$this->CI->session->userdata($this->key_user),
            'level'    => $this->level(),
            'fname'    => (string)$this->CI->session->userdata('fname'),
            'lname'    => (string)$this->CI->session->userdata('lname'),
            'avatar'   => (string)$this->CI->session->userdata('avatar'),
            'IDNumber' => (string)$this->CI->session->userdata('IDNumber'),
            'sy'       => (string)$this->CI->session->userdata('sy'),
            'semester' => (string)$this->CI->session->userdata('semester'),
        );
    }

    /**
     * Does the signed-in user hold one of these levels?
     * Comparison is case-insensitive and whitespace-tolerant, because the
     * levels stored on accounts are free text.
     */
    public function has_level($levels)
    {
        $levels = is_array($levels) ? $levels : array($levels);
        $mine   = strtolower($this->level());

        foreach ($levels as $level) {
            if ($mine === strtolower(trim((string)$level))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Guard a single action from inside a controller:
     *   $this->authguard->require_level(['Admin', 'School Admin']);
     * Halts the request when the level does not match.
     */
    public function require_level($levels)
    {
        if (!$this->is_logged_in()) {
            $this->reject_unauthenticated();
            return false;
        }
        if (!$this->has_level($levels)) {
            $this->reject_forbidden(is_array($levels) ? $levels : array($levels));
            return false;
        }
        return true;
    }

    // ------------------------------------------------------------------
    // Route matching
    // ------------------------------------------------------------------

    /** 'controller/method' for the request being handled. */
    public function current_route()
    {
        $dir    = strtolower(trim((string)$this->CI->router->fetch_directory(), '/'));
        $class  = strtolower((string)$this->CI->router->fetch_class());
        $method = strtolower((string)$this->CI->router->fetch_method());

        $route = ($dir !== '' ? $dir . '/' : '') . $class . '/' . ($method === '' ? 'index' : $method);

        return $route;
    }

    public function is_public($route = null)
    {
        $route = ($route === null) ? $this->current_route() : strtolower($route);

        foreach ($this->public_routes as $pattern) {
            if ($this->route_matches($route, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Most specific rule wins: 'page/admin' beats 'page/*'.
     * Returns null when no rule covers the route.
     */
    protected function rule_for($route, array $rules)
    {
        $exact = null;
        $wild  = null;

        foreach ($rules as $pattern => $value) {
            if (!$this->route_matches($route, $pattern)) {
                continue;
            }
            if (strpos($pattern, '*') === false) {
                $exact = $value;
            } elseif ($wild === null) {
                $wild = $value;
            }
        }
        return ($exact !== null) ? $exact : $wild;
    }

    protected function route_matches($route, $pattern)
    {
        $pattern = strtolower(trim((string)$pattern));
        if ($pattern === '') {
            return false;
        }

        // 'controller' on its own means the whole controller.
        if (strpos($pattern, '/') === false) {
            $pattern .= '/*';
        }

        if (substr($pattern, -2) === '/*') {
            $prefix = substr($pattern, 0, -1); // keep the trailing slash
            return strpos($route, $prefix) === 0;
        }

        return $route === $pattern;
    }

    // ------------------------------------------------------------------
    // Idle timeout
    // ------------------------------------------------------------------

    protected function is_idle_expired()
    {
        $last = (int)$this->CI->session->userdata('authguard_last_seen');
        return $last > 0 && (time() - $last) > $this->idle_timeout;
    }

    protected function touch_activity()
    {
        $this->CI->session->set_userdata('authguard_last_seen', time());
    }

    // ------------------------------------------------------------------
    // Rejections
    // ------------------------------------------------------------------

    /** XHR/fetch callers get JSON, not a login page they cannot render. */
    protected function wants_json()
    {
        if ($this->CI->input->is_ajax_request()) {
            return true;
        }
        $accept = strtolower((string)$this->CI->input->server('HTTP_ACCEPT'));

        return strpos($accept, 'application/json') !== false;
    }

    protected function reject_unauthenticated($message = 'Please sign in to continue.')
    {
        if ($this->wants_json()) {
            $this->json(401, array(
                'ok'      => false,
                'error'   => 'unauthenticated',
                'message' => $message,
                'login'   => site_url($this->login_route),
            ));
            return;
        }

        $next = $this->current_url_path();
        $this->CI->session->set_flashdata('ui_notice', array(
            'type'    => 'warning',
            'title'   => 'Sign in required',
            'message' => $message,
        ));

        redirect(site_url($this->login_route) . '?next=' . urlencode($next));
    }

    protected function reject_forbidden(array $allowed = array())
    {
        $mine = $this->level() ?: 'Unknown';

        if ($this->wants_json()) {
            $this->json(403, array(
                'ok'      => false,
                'error'   => 'forbidden',
                'message' => 'Your account level (' . $mine . ') cannot access this page.',
            ));
            return;
        }

        $this->CI->output->set_status_header(403);
        $this->CI->load->view('errors/html/error_forbidden', array(
            'user_level' => $mine,
            'allowed'    => $allowed,
            'route'      => $this->current_route(),
        ));
        $this->CI->output->_display();
        exit;
    }

    protected function json($status, array $payload)
    {
        $this->CI->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload))
            ->_display();
        exit;
    }

    /** Path + query of the request, for the ?next= bounce-back. */
    protected function current_url_path()
    {
        $uri   = (string)$this->CI->uri->uri_string();
        $query = (string)$this->CI->input->server('QUERY_STRING');

        return $uri . ($query !== '' ? '?' . $query : '');
    }
}
