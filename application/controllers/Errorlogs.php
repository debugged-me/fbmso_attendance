<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Errorlogs — a readable view over the raw log files.
 *
 * SSH'ing into production to grep application/logs/log-*.php and error_log
 * is slow and easy to misread. This page parses both formats, groups repeat
 * errors into one row, hides bot-scan 404 noise, and attaches a
 * plain-English "what it means / what to do" note (LogReader::explain).
 *
 * index()    — the viewer.
 * download() — fetch one raw log file (GET; read-only, like backup/file).
 * clear()    — POST only; truncates one file, audit-logged.
 *
 * Super Admin only — log lines can contain file paths, SQL fragments,
 * and IPs. The AuthGuard 'errorlogs/*' rule and this controller check are
 * deliberate duplication, same convention as Backup and Security.
 */
class Errorlogs extends CI_Controller
{
    const RAW_LIMIT = 500;   // rows shown in the raw/timeline view

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('LogReader');
        $this->load->model('SettingsModel'); // used by top-nav-bar
        $this->load->model('AuditLogModel');
    }

    /** Second layer behind AuthGuard's 'errorlogs/*' role rule. */
    private function requireSuperAdmin()
    {
        if (is_cli()) {
            return;
        }
        if ($this->session->userdata('logged_in') !== TRUE) {
            redirect('login');
        }
        if ((string)$this->session->userdata('level') !== 'Super Admin') {
            show_error('Access Denied — Super Admin only.', 403);
        }
    }

    /** Mutating endpoints must be POST so CI's CSRF check applies. */
    private function requirePost()
    {
        if (strtolower((string)$this->input->method()) !== 'post') {
            show_error('Method not allowed', 405);
        }
    }

    public function index()
    {
        $this->requireSuperAdmin();

        $sources = $this->logreader->sources();

        // Default to this app's own logs ('ci' = application/logs/*). 'all'
        // additionally merges the PHP error_log — an explicit choice, since
        // that file can carry stale lines from other sites on some hosts.
        $src = (string)$this->input->get('src', true);
        if (!in_array($src, array('ci', 'all'), true) && !isset($sources[$src])) {
            $src = 'ci';
        }
        $level = strtolower((string)$this->input->get('level', true));
        if (!in_array($level, array('all', 'fatal', 'error', 'warning', 'notice', 'notfound'), true)) {
            $level = 'all';
        }
        $q       = trim((string)$this->input->get('q', true));
        $view    = $this->input->get('view') === 'raw' ? 'raw' : 'grouped';
        $noiseOn = (int)$this->input->get('noise') === 1;

        $entries = $this->logreader->entries($src);
        foreach ($entries as &$e) {
            $e['explain'] = $this->logreader->explain($e);
        }
        unset($e);

        // Stats describe the whole selected source before list filters.
        $stats = array('total' => 0, 'fatal' => 0, 'error' => 0, 'warning' => 0, 'notice' => 0, 'notfound' => 0, 'last' => 0);
        $hiddenNoise = 0;
        foreach ($entries as $e) {
            $stats['total']++;
            if (isset($stats[$e['level']])) {
                $stats[$e['level']]++;
            }
            if ($e['ts'] > $stats['last']) {
                $stats['last'] = $e['ts'];
            }
            if (!empty($e['explain']['noise'])) {
                $hiddenNoise++;
            }
        }

        // List filters: level, search text, noise toggle.
        $filtered = array();
        foreach ($entries as $e) {
            if ($level !== 'all' && $e['level'] !== $level) {
                continue;
            }
            if ($q !== '' && stripos($e['raw'] . ' ' . $e['uri'], $q) === false) {
                continue;
            }
            if (!$noiseOn && !empty($e['explain']['noise'])) {
                continue;
            }
            $filtered[] = $e;
        }

        $data = array(
            'sources' => $sources,
            'src'     => $src,
            'level'   => $level,
            'q'       => $q,
            'view'    => $view,
            'noiseOn' => $noiseOn,
            'stats'   => $stats,
            'capped'  => false,
            'rows'    => array(),
            'shown'   => 0,
            'hiddenNoise' => $hiddenNoise,
            'dropped'   => $this->logreader->droppedForeign(),
            'rawLimit'  => self::RAW_LIMIT,
        );

        if ($view === 'grouped') {
            $data['rows']  = $this->logreader->group($filtered);
            $data['shown'] = count($data['rows']);
        } else {
            $data['capped'] = count($filtered) > self::RAW_LIMIT;
            $data['rows']   = array_slice($filtered, 0, self::RAW_LIMIT);
            $data['shown']  = count($data['rows']);
        }

        $this->load->view('errorlogs_index', $data);
    }

    /**
     * GET error-logs/download/{key} — the raw file, untouched.
     * Read-only, so GET is fine (same convention as backup/file/{id}).
     */
    public function download($key = '')
    {
        $this->requireSuperAdmin();

        $src = $this->logreader->source((string)$key);
        if (!$src || !is_file($src['path'])) {
            show_error('That log file no longer exists.', 404);
            return;
        }

        while (ob_get_level()) {
            @ob_end_clean();
        }
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', basename($src['path']));
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($src['path']));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        readfile($src['path']);
        exit;
    }

    /**
     * POST error-logs/clear/{key} — truncate one log file.
     * CI files get their PHP header line back so a direct browser hit on
     * application/logs/log-*.php still exits instead of listing entries.
     */
    public function clear($key = '')
    {
        $this->requireSuperAdmin();
        $this->requirePost();

        $src = $this->logreader->source((string)$key);
        if (!$src || !is_file($src['path'])) {
            show_error('That log file no longer exists.', 404);
            return;
        }

        $ok = false;
        if ($src['kind'] === 'ci') {
            $ok = @file_put_contents($src['path'], "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n") !== false;
        } else {
            $fp = @fopen($src['path'], 'wb');
            if ($fp) {
                $ok = true;
                fclose($fp);
            }
        }

        try {
            $this->AuditLogModel->write(
                'delete', 'Error Logs', null, null, null,
                array('file' => basename($src['path']), 'succeeded' => $ok),
                $ok ? 1 : 0,
                $ok ? 'Cleared log file' : 'Failed to clear log file',
                array('_actor_username' => (string)$this->session->userdata('username'))
            );
        } catch (Throwable $ignored) {
        }

        $this->session->set_flashdata(
            $ok ? 'success' : 'danger',
            $ok ? 'Log file cleared: ' . basename($src['path'])
                : 'Could not clear ' . basename($src['path']) . ' — check file permissions.'
        );
        redirect('error-logs?src=' . urlencode($src['key']));
    }
}
