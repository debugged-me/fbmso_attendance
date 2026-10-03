<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * LogReader — turns CodeIgniter's application/logs/log-*.php files and the
 * PHP error_log into structured entries the Error Logs page can render.
 *
 * Two formats are understood:
 *
 *   CI:  ERROR - 2026-10-03 16:40:44 --> Severity: error --> Exception: ... File.php 31
 *        ERROR - 2026-10-03 16:41:00 --> 404 Page Not Found --> some/uri
 *        ERROR - 2026-10-03 16:42:00 --> Mail queue: send failed id=2903 ...
 *   PHP: [19-Jan-2022 13:23:43 UTC] PHP Warning:  mysqli_connect(): ... on line 7
 *
 * Lines that don't start a new entry are treated as continuations of the
 * previous one (stack traces, multi-line OpenSSL output).
 *
 * explain() attaches a plain-English title, cause and fix to each entry by
 * pattern-matching against a small knowledge base — the point of the page is
 * "see the error, know what to do", so keep the rules ordered specific-first.
 */
class LogReader
{
    /** Never read more than the tail of a file — prod logs can get big. */
    const MAX_READ = 1048576; // 1 MB

    /** Hard cap on parsed entries returned to the page. */
    const MAX_ENTRIES = 5000;

    /** Entries skipped by entries() because they belong to another system. */
    private $droppedForeign = 0;

    public function droppedForeign()
    {
        return $this->droppedForeign;
    }

    /**
     * Discover the log files this server is writing.
     *
     * @return array key => ['key','kind','label','path','size','mtime']
     *               kind is 'ci' (application/logs/log-*.php) or 'php' (error_log)
     */
    public function sources()
    {
        $out = array();

        $dir = (string)config_item('log_path');
        $dir = $dir !== '' ? rtrim($dir, '/\\') . '/' : APPPATH . 'logs/';
        $ext = ltrim((string)config_item('log_file_extension'), '.');
        $ext = $ext !== '' ? $ext : 'php';

        foreach ((array)glob($dir . 'log-*.' . $ext) as $path) {
            if (!preg_match('/^log-(\d{4}-\d{2}-\d{2})\.' . preg_quote($ext, '/') . '$/', basename($path), $m)) {
                continue;
            }
            $out['ci-' . $m[1]] = array(
                'key'   => 'ci-' . $m[1],
                'kind'  => 'ci',
                'label' => date('M j, Y', strtotime($m[1])),
                'path'  => $path,
                'size'  => (int)@filesize($path),
                'mtime' => (int)@filemtime($path),
            );
        }

        // PHP's own error_log: wherever php.ini sends it plus the
        // conventional <docroot>/error_log this app produces.
        $candidates = array(rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'error_log');
        $ini = trim((string)@ini_get('error_log'));
        if ($ini !== '') {
            $candidates[] = $ini;
            if ($ini[0] !== '/' && !preg_match('/^[A-Za-z]:[\\\\\/]/', $ini)) {
                $candidates[] = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . $ini;
            }
        }
        $i = 0;
        foreach (array_unique($candidates) as $path) {
            if (!is_file($path)) {
                continue;
            }
            $out['php-' . $i] = array(
                'key'   => 'php-' . $i,
                'kind'  => 'php',
                'label' => 'PHP error_log' . ($i > 0 ? ' (' . basename($path) . ')' : ''),
                'path'  => $path,
                'size'  => (int)@filesize($path),
                'mtime' => (int)@filemtime($path),
            );
            $i++;
        }

        uasort($out, function ($a, $b) { return $b['mtime'] - $a['mtime']; });
        return $out;
    }

    /** Whitelist lookup — the only way a page param becomes a file path. */
    public function source($key)
    {
        $all = $this->sources();
        return isset($all[$key]) ? $all[$key] : null;
    }

    /**
     * Parse entries for one source key, or 'all' for every source merged.
     * Returned newest-first, capped at MAX_ENTRIES.
     */
    public function entries($key = 'all')
    {
        $srcs = $this->sources();
        $files = array();
        if ($key === 'all') {
            $files = array_values($srcs); // sources() is already mtime-desc
        } elseif ($key === 'ci') {
            // This app's own logs only — application/logs/log-*.php. Skips
            // the PHP error_log, which may carry stale lines from other
            // sites when the file is shared or copied between projects.
            foreach ($srcs as $s) {
                if ($s['kind'] === 'ci') {
                    $files[] = $s;
                }
            }
        } elseif (isset($srcs[$key])) {
            $files = array($srcs[$key]);
        }

        $entries = array();
        foreach ($files as $f) {
            $list = $f['kind'] === 'php' ? $this->parsePhp($f['path']) : $this->parseCi($f['path']);
            foreach ($list as &$e) {
                $e['src'] = $f['key'];
            }
            unset($e);
            $entries = array_merge($entries, $list);
            if (count($entries) >= self::MAX_ENTRIES) {
                break;
            }
        }

        // This is a separate system: entries whose file:line lives outside
        // this install's root are someone else's errors (e.g. an error_log
        // copied over from another site) — drop them entirely, whichever
        // source they came through.
        $this->droppedForeign = 0;
        $root = rtrim(FCPATH, '/\\');
        $entries = array_values(array_filter($entries, function ($e) use ($root) {
            if ($e['file'] !== '' && strpos($e['file'], $root) !== 0) {
                $this->droppedForeign++;
                return false;
            }
            return true;
        }));

        usort($entries, function ($a, $b) { return $b['ts'] - $a['ts']; });
        return array_slice($entries, 0, self::MAX_ENTRIES);
    }

    // ------------------------------------------------------------------
    // Parsing
    // ------------------------------------------------------------------

    private function parseCi($path)
    {
        $entries = array();
        foreach ($this->tail($path) as $line) {
            if (preg_match('/^(?:ERROR|DEBUG|INFO|WARNING|ALL)\s+-\s+(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})\s+-->\s?(.*)$/s', $line, $m)) {
                $entries[] = $this->ciEntry($m[3], $m[1] . ' ' . $m[2]);
            } elseif ($entries && trim($line) !== '') {
                $i = count($entries) - 1;
                $entries[$i]['raw'] .= "\n" . $line;
                $entries[$i]['msg'] .= "\n" . $line;
            }
        }
        return $entries;
    }

    private function ciEntry($msg, $ts)
    {
        $e = array(
            'time' => $ts, 'ts' => (int)strtotime($ts), 'level' => 'error',
            'raw' => trim($msg), 'msg' => trim($msg),
            'file' => '', 'line' => 0, 'uri' => '', 'src' => '',
        );

        // Web 404s log as "404 Page Not Found: uri" (or "-->" on older CI);
        // CLI 404s log as "Not Found: Class/method".
        if (preg_match('/^(?:404 Page Not Found|Not Found)\s*(?::|-->)\s*(.*)$/s', $e['msg'], $m)) {
            $e['level'] = 'notfound';
            $e['uri']   = trim($m[1]);
            $e['msg']   = 'Page not found: ' . ($e['uri'] !== '' ? '/' . ltrim($e['uri'], '/') : '(unknown URL)');
            return $e;
        }
        if (preg_match('/^Severity:\s*(.+?)\s*-->\s*(.*)$/s', $e['msg'], $m)) {
            $e['level'] = $this->mapSeverity($m[1]);
            $e['msg']   = trim($m[2]);
        }
        // CI appends " /path/File.php <line>" with no separator. Greedy (.*)
        // for the message so the path group can only be the LAST such pair —
        // exception text like "0 passed in /x/Loader.php on line 1287 and
        // exactly 1 expected /y/File.php 31" keeps the throw site, not a span.
        if (preg_match('/^(.*)\s+((?:[A-Za-z]:)?[\/\\\\][^\n]*?\.(?:php|phtml|inc|html))\s+(\d+)\s*$/s', $e['msg'], $m)) {
            $e['msg']  = trim($m[1]);
            $e['file'] = $m[2];
            $e['line'] = (int)$m[3];
        }
        return $e;
    }

    private function parsePhp($path)
    {
        $entries = array();
        foreach ($this->tail($path) as $line) {
            if (preg_match('/^\[(\d{2}-[A-Za-z]{3}-\d{4})\s+(\d{2}:\d{2}:\d{2})[^\]]*\]\s*(.*)$/', $line, $m)) {
                $ts = (int)strtotime($m[1] . ' ' . $m[2]);
                $entries[] = $this->phpEntry($m[3], $ts, date('Y-m-d H:i:s', $ts));
            } elseif ($entries && trim($line) !== '') {
                $i = count($entries) - 1;
                $entries[$i]['raw'] .= "\n" . $line;
                $entries[$i]['msg'] .= "\n" . $line;
            }
        }
        return $entries;
    }

    private function phpEntry($msg, $ts, $time)
    {
        $e = array(
            'time' => $time, 'ts' => $ts, 'level' => 'error',
            'raw' => trim($msg), 'msg' => trim($msg),
            'file' => '', 'line' => 0, 'uri' => '', 'src' => '',
        );

        if (preg_match('/^PHP\s+(Fatal error|Parse error|Catchable fatal error|Core error|Warning|Core Warning|Notice|Deprecated|Strict standards|User error|User warning|User notice|User deprecated)\s*:\s*(.*)$/is', $e['msg'], $m)) {
            $e['level'] = $this->mapSeverity($m[1]);
            $e['msg']   = trim($m[2]);
        }
        if (preg_match('/^(.*?)\s+in\s+(\/[^\n]+?|[A-Za-z]:[\\\\\/][^\n]+?)\s+on line\s+(\d+)\s*$/s', $e['msg'], $m)) {
            $e['msg']  = trim($m[1]);
            $e['file'] = $m[2];
            $e['line'] = (int)$m[3];
        }
        return $e;
    }

    private function mapSeverity($sev)
    {
        $s = strtolower(trim($sev));
        if (preg_match('/fatal|parse|core error|uncaught/', $s)) return 'fatal';
        if (strpos($s, 'warn') !== false) return 'warning';
        if (preg_match('/notice|deprecat|strict/', $s)) return 'notice';
        return 'error';
    }

    /** Last MAX_READ bytes of $path as an array of lines. */
    private function tail($path)
    {
        $size = (int)@filesize($path);
        if ($size <= 0) {
            return array();
        }
        $fp = @fopen($path, 'rb');
        if (!$fp) {
            return array();
        }
        if ($size > self::MAX_READ) {
            fseek($fp, -self::MAX_READ, SEEK_END);
            fgets($fp); // discard the partial first line
        }
        $data = (string)stream_get_contents($fp);
        fclose($fp);
        return preg_split('/\r\n|\r|\n/', $data);
    }

    // ------------------------------------------------------------------
    // Grouping
    // ------------------------------------------------------------------

    /**
     * Collapse repeat entries into one row. The signature normalises the
     * parts that vary between occurrences — digits, long hex, absolute
     * paths — so "id=2903" and "id=3100" land in the same group.
     */
    public function group(array $entries)
    {
        $groups = array();
        foreach ($entries as $e) {
            $sig = $this->signature($e);
            if (!isset($groups[$sig])) {
                $groups[$sig] = array(
                    'sig' => $sig, 'level' => $e['level'], 'count' => 0,
                    'first' => $e, 'last' => $e, 'msg' => $e['msg'], 'raw' => $e['raw'],
                    'locs' => array(), 'uris' => array(),
                );
            }
            $g = &$groups[$sig];
            $g['count']++;
            if ($e['ts'] <= $g['first']['ts']) $g['first'] = $e;
            if ($e['ts'] >= $g['last']['ts'])  $g['last']  = $e;
            if ($e['file'] !== '') $g['locs'][$this->shortPath($e['file']) . ':' . $e['line']] = true;
            if ($e['uri'] !== '')  $g['uris'][$e['uri']] = true;
        }
        unset($g);

        foreach ($groups as &$g) {
            $g['locs'] = array_slice(array_keys($g['locs']), 0, 4);
            $g['uris'] = array_slice(array_keys($g['uris']), 0, 5);
            $g['explain'] = isset($g['last']['explain']) ? $g['last']['explain'] : $this->explain($g['last']);
        }
        unset($g);

        usort($groups, function ($a, $b) { return $b['last']['ts'] - $a['last']['ts']; });
        return array_values($groups);
    }

    private function signature(array $e)
    {
        $s = $e['level'] . '|' . $e['msg'];
        $s = str_replace(array(FCPATH, APPPATH, BASEPATH), '~/', $s);
        $s = preg_replace('/[?&][^\s]*/', ' ', $s);            // query strings
        $s = preg_replace('/\b[0-9a-f]{12,}\b/i', '<hex>', $s);
        $s = preg_replace('/\d+/', 'N', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return strtolower(trim($s));
    }

    public function shortPath($path)
    {
        return str_replace(array(FCPATH, APPPATH, BASEPATH), array('~/', '~/app/', '~/sys/'), $path);
    }

    // ------------------------------------------------------------------
    // Knowledge base — "what is this and what do I do"
    // ------------------------------------------------------------------

    /**
     * @return array ['cat','title','what','fix','noise']
     */
    public function explain(array $e)
    {
        if ($e['level'] === 'notfound') {
            return $this->explain404($e['uri']);
        }

        foreach ($this->rules() as $r) {
            if (preg_match($r['re'], $e['msg'])) {
                return array(
                    'cat'   => $r['cat'],
                    'title' => $r['title'],
                    'what'  => $r['what'],
                    'fix'   => $r['fix'],
                    'noise' => !empty($r['noise']),
                );
            }
        }

        return array(
            'cat'   => 'other',
            'title' => 'Error (unrecognised pattern)',
            'what'  => 'This does not match a known pattern yet — read the raw line. The file:line at the end points to where PHP raised it.',
            'fix'   => 'Open the file:line shown, reproduce the request that caused it, and fix from the message. If it recurs, add a rule for it in LogReader::rules().',
            'noise' => false,
        );
    }

    private function explain404($uri)
    {
        static $bots = array(
            'robots.txt', 'sitemap', 'favicon', 'apple-touch-icon', 'assetlinks',
            '.well-known', 'wp-', 'wordpress', 'xmlrpc', 'wlwmanifest', '.env',
            '.git', '.svn', '.htpasswd', 'phpmyadmin', 'pma/', 'administrator',
            'autodiscover', 'ecp/', 'owa/', 'telescope', '_ignition', 'boaform',
            'hnap', 'eval-stdin', 'magento', 'prestashop', 'drupal', 'joomla',
            'server-status', 'solr/', 'actuator', 'vendor/phpunit', '.ds_store',
            'dup-installer', 'installer.php', 'ws.php', 'alfanew', 'makefile',
            'backup.', 'old/', 'test.php', 'info.php', 'phpinfo',
        );
        $u = strtolower((string)$uri);
        foreach ($bots as $b) {
            if (strpos($u, $b) !== false) {
                return array(
                    'cat'   => '404-noise',
                    'title' => 'Bot scan — safe to ignore',
                    'what'  => 'Automated scanners probe every public site for WordPress, .env files, and admin panels. This is not your users and nothing is broken.',
                    'fix'   => 'Nothing. CI logs 404s at ERROR level so they always appear here — use the noise toggle to hide them. If one path is hammered repeatedly, block the IP from the Security dashboard.',
                    'noise' => true,
                );
            }
        }
        return array(
            'cat'   => '404',
            'title' => 'Broken link or missing route',
            'what'  => 'Someone (or a crawler) requested a URL the app does not serve. If a real user hit it, a link, redirect, or base_url() somewhere points to a route that does not exist.',
            'fix'   => 'Check the URI shown: search views and controllers for it, confirm the route exists in config/routes.php, or add a redirect. Repeated hits on an old URL mean a stale link still points there.',
            'noise' => false,
        );
    }

    /**
     * Ordered specific-first; first match wins.
     */
    private function rules()
    {
        return array(

            // ---- this app's own subsystems ------------------------------

            array('re' => '/illegal mix of collations/i',
                'cat' => 'database', 'title' => 'Database collation conflict',
                'what' => 'A query compared a latin1 column (o_users on production is latin1) against utf8mb4/Unicode input, so MySQL refused to run it.',
                'fix' => 'Convert the offending table to utf8mb4: ALTER TABLE o_users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; — the message usually names the operation that tripped it.'),

            array('re' => '/mail queue: send failed/i',
                'cat' => 'email', 'title' => 'Queued email failed to send',
                'what' => 'An email in the queue could not be delivered. The reason= part names which transport failed (system_email, brevo_relay_fallback, ...) and why.',
                'fix' => 'Read the reason in the raw line: SMTP auth/port problems mean checking the email settings; a bad recipient means the address bounced. The queue retries, so a single failure is often transient.'),

            array('re' => '/unable to send email using php smtp|the following smtp error/i',
                'cat' => 'email', 'title' => 'SMTP send failed',
                'what' => 'PHP could not deliver mail through the configured SMTP server — authentication, host, port, or crypto is wrong, or the server refused the connection.',
                'fix' => 'Check the email settings (host, port 465 = ssl://, 587 = STARTTLS, username/password or Brevo relay key). Send a test from the Email Test page to confirm.'),

            array('re' => '/fsockopen.*(ssl|crypto)|failed to enable crypto|ssl3_get_record|wrong version number/i',
                'cat' => 'email', 'title' => 'SMTP TLS handshake failed',
                'what' => 'The mail connection opened but the TLS negotiation failed — almost always the port/crypto pair is wrong (plain socket on an SSL port) or the server demands a newer TLS than this PHP offers.',
                'fix' => 'Use ssl://host:465 for implicit SSL, or host:587 with STARTTLS. On shared hosting also confirm outbound SMTP ports are not firewalled.'),

            array('re' => '/oauth2|googleapis\.com|google drive|drive upload|invalid_grant|unauthorized_client|refresh[_ ]token|access token/i',
                'cat' => 'drive', 'title' => 'Google Drive / OAuth problem',
                'what' => 'A Drive upload or token refresh failed. On this server the usual cause is outbound access to oauth2.googleapis.com being blocked, or a refresh token that was revoked/expired.',
                'fix' => 'The code already falls back to www.googleapis.com/oauth2/v4/token — if it persists, confirm the server can reach *.googleapis.com (nettest), then reconnect from Backup → Connect Google Drive. Verify the redirect URI is exactly https://<host>/backup/google-callback.'),

            array('re' => '/backup|dbbackup|dump/i',
                'cat' => 'backup', 'title' => 'Database backup problem',
                'what' => 'A backup run failed — disk space, a locked table, or the DB user missing SELECT/LOCK TABLES rights are the usual causes. Delivery (email/Drive) failures are tagged in the message.',
                'fix' => 'Check free disk space and the Backup page history for which stage failed. For email delivery, see the email rules above; for Drive, the OAuth rule.'),

            array('re' => '/session.*(save path|writable|write)|session_start|sess_save/i',
                'cat' => 'session', 'title' => 'Session storage problem',
                'what' => 'PHP could not start or write a session — the save path is not writable or the driver config is wrong.',
                'fix' => 'Check sess_save_path / sess_driver in config.php and that the directory is writable by the web user.'),

            array('re' => '/action you have requested is not allowed|csrf/i',
                'cat' => 'csrf', 'title' => 'CSRF token check failed',
                'what' => 'A POST arrived without a valid CSRF token — a page left open past the token lifetime, a hand-built form missing the token, or a forged request.',
                'fix' => 'If it hit a real user, that view is missing the token (CsrfInjectHook normally adds it to every POST form). One-offs are usually stale tabs — nothing to do.'),

            // ---- PHP / code-level ----------------------------------------

            array('re' => '/too few arguments|too many arguments|argument \#?\d+.*(must be|required)|missing required/i',
                'cat' => 'code', 'title' => 'Wrong number of function arguments',
                'what' => 'Code called a function or constructor without its required arguments — commonly a library loaded via load->library() that now needs params, or a signature changed without updating the caller.',
                'fix' => 'Open the file:line shown. For libraries, pass params as the second argument: $this->load->library(\'name\', $params) — or give the constructor defaults.'),

            array('re' => '/call to (a member function|undefined method)|call to undefined function/i',
                'cat' => 'code', 'title' => 'Call to a method/function that does not exist',
                'what' => 'The code called a method on an object that does not have it (or on null/false), or a function that is not defined — a typo, an unloaded model/library, or a changed API.',
                'fix' => 'Check the file:line: verify the model/library is loaded and the method name matches. "on null" means an earlier lookup returned nothing — fix that lookup instead.'),

            array('re' => '/non-numeric|non well formed numeric|uninitialized string offset|trying to access array offset on value of type|automatic conversion of false/i',
                'cat' => 'code', 'title' => 'Notice-level PHP bug',
                'what' => 'PHP warned about sloppy data handling — arithmetic on non-numeric strings, missing offsets, false used as an array. Usually not fatal, but it hides real bugs and fills the log.',
                'fix' => 'Fix at the file:line — validate or cast the value before using it. If this only appears on PHP 8+, the code was written with PHP 5/7 habits.'),

            array('re' => '/undefined (variable|index|array key|property|constant)|attempt to read property|trying to access array offset|trying to get property/i',
                'cat' => 'code', 'title' => 'Reading a variable/property that is not set',
                'what' => 'PHP notice-level bug: the code reads a variable, array key, or property that does not exist in this path. Usually harmless output-wise, but it hides real bugs.',
                'fix' => 'Fix at the file:line — initialise the variable, check isset(), or guard the property access. If it is "property on null", the DB query returned no row.'),

            array('re' => '/class .{0,60} not found|failed to open stream|failed opening|include\(.*no such file|require.*no such file/i',
                'cat' => 'deploy', 'title' => 'Missing file or class',
                'what' => 'PHP tried to include a file that is not there — an incomplete deploy, a renamed class, or a wrong path.',
                'fix' => 'Check the file:line and confirm the file was deployed. If it is views/errors/cli/error_general.php, that is a harmless CI quirk when a CLI request errors — create that view file to silence it.'),

            array('re' => '/headers already sent|cannot modify header/i',
                'cat' => 'code', 'title' => 'Output sent before headers',
                'what' => 'Something printed output before the app sent a header/redirect — whitespace or a BOM in an edited PHP file, a stray echo/var_dump, or a closing ?> with a trailing newline.',
                'fix' => 'The message names the file:line that produced output. Remove the whitespace/echo there; prefer omitting ?> at the end of pure-PHP files.'),

            array('re' => '/maximum execution time|max_execution_time/i',
                'cat' => 'perf', 'title' => 'Request ran out of time',
                'what' => 'The request exceeded PHP\'s time limit — a long query, a big export, or a loop that does not exit.',
                'fix' => 'If it is a known heavy task (exports, imports), raise the limit for that path with set_time_limit(). Otherwise the file:line points at the loop or query to optimise.'),

            array('re' => '/allowed memory size|out of memory|memory exhausted/i',
                'cat' => 'perf', 'title' => 'PHP ran out of memory',
                'what' => 'The request used more than memory_limit — usually loading a big result set or file entirely into memory.',
                'fix' => 'Stream the data instead (the backup exporter already does), or raise memory_limit for that task. The file:line shows what ate the memory.'),

            array('re' => '/permission denied|not writable|failed to open stream: permission|mkdir\(\)/i',
                'cat' => 'fs', 'title' => 'File permission problem',
                'what' => 'The web user cannot write or read the path in the message — uploads, logs, or a temp directory.',
                'fix' => 'chmod/chown the directory so the web user can write it (upload/, application/logs/, sys_get_temp_dir()). On cPanel, 755/775 dirs owned by the account user usually suffice.'),

            array('re' => '/no space left|disk (full|quota)|quota exceeded/i',
                'cat' => 'fs', 'title' => 'Disk full',
                'what' => 'The server ran out of disk space — writes (sessions, logs, uploads, backups, MySQL) start failing everywhere.',
                'fix' => 'Free space urgently: check upload/backups growth, old log files, and MySQL binlogs. Then clear the space hogs and re-test.'),

            // ---- database -------------------------------------------------

            array('re' => '/duplicate entry .* for key/i',
                'cat' => 'database', 'title' => 'Duplicate value in a unique column',
                'what' => 'An INSERT/UPDATE hit a unique index — a double-submit/retry, an import with repeated values, or code assuming a value is free when it is not.',
                'fix' => 'The key name in the message tells which column must stay unique. If it came from a form submit, add duplicate protection (unique check or INSERT ... ON DUPLICATE KEY UPDATE).'),

            array('re' => '/table .* doesn\'?t exist|base table or view not found/i',
                'cat' => 'database', 'title' => 'Missing database table',
                'what' => 'The code queried a table that is not in this database — a migration that never ran here, or a deploy that missed its SQL.',
                'fix' => 'Run the pending migration (the schema migrator runs on admin requests) or apply the CREATE TABLE the feature shipped with. Check the table name in the message.'),

            array('re' => '/unknown column|column .* cannot be null|incorrect .* value|data truncated/i',
                'cat' => 'database', 'title' => 'Query does not match the schema',
                'what' => 'The code expects a column/type the table does not have — code ahead of the schema, a NOT NULL column given no value, or data too long for the column.',
                'fix' => 'Run the ALTER/migration that adds the column, or fix the insert to supply valid values. The message names the column.'),

            array('re' => '/access denied for user/i',
                'cat' => 'database', 'title' => 'MySQL login rejected',
                'what' => 'The DB server refused the credentials in application/config/database.php — wrong user, password, or the user lacks rights from this host.',
                'fix' => 'Verify the username/password/database in config/database.php match the hosting panel, and that the user is allowed from the web host (e.g. \'user\'@\'localhost\' vs \'%\').'),

            array('re' => '/error in your sql syntax|query error|sqlstate|gone away|lost connection|deadlock/i',
                'cat' => 'database', 'title' => 'Database query failed',
                'what' => 'A SQL statement failed — a syntax bug, a server restart mid-query, a deadlock, or a timeout on a big query.',
                'fix' => 'Enable $this->db->save_queries and db_debug locally to see the failing statement. "Gone away"/deadlocks that repeat point at locking or a query that runs too long.'),

            // ---- network --------------------------------------------------

            array('re' => '/curl error|could not resolve host|connection timed out|connection refused|network is unreachable|temporary failure in name resolution|operation timed out/i',
                'cat' => 'network', 'title' => 'Outbound connection failed',
                'what' => 'The server could not reach an external host — DNS, outbound firewall, or the remote service being down. The host in the message says which integration broke.',
                'fix' => 'From the server, test DNS and connectivity to that host. On shared hosting ask whether outbound HTTPS to it is allowed. For Google specifically, see the Drive rule above.'),

            // ---- catch-alls ------------------------------------------------

            array('re' => '/exception|throwable|stack trace/i',
                'cat' => 'code', 'title' => 'Uncaught exception',
                'what' => 'Code threw an exception nothing caught — the message is the exception text and the file:line is where it was thrown.',
                'fix' => 'Read the exception message for the cause, fix at the file:line, or wrap the call in try/catch if failure is expected.'),

            array('re' => '/./s',
                'cat' => 'other', 'title' => 'PHP error',
                'what' => 'A PHP-level error with the details in the raw line — the file:line at the end is where it was raised.',
                'fix' => 'Open the file:line shown and work from the message text.'),
        );
    }
}
