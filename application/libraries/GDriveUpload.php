<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Minimal Google Drive uploader for service-account credentials.
 *
 * Why no google/apiclient: the project has no composer autoloader, and the
 * whole upload flow is two HTTPS calls -- mint a JWT, swap it for an access
 * token, then one multipart upload. cURL + openssl cover it.
 *
 * Setup the user does once (documented on the backup settings page):
 *   1. Google Cloud Console -> create a service account.
 *   2. Enable "Google Drive API" for that project.
 *   3. Download the service-account JSON key.
 *   4. Share the target Drive folder with the service account's email
 *      (service accounts have no Drive storage of their own -- the file
 *      lands in the shared folder of a real account).
 */
class GDriveUpload
{
    const TOKEN_URL  = 'https://oauth2.googleapis.com/token';
    // Some hosts can reach www.googleapis.com but not oauth2.googleapis.com —
    // the v4 endpoint is the same token service on a different edge.
    const TOKEN_URL_ALT = 'https://www.googleapis.com/oauth2/v4/token';
    const FILES_URL  = 'https://www.googleapis.com/drive/v3/files';
    const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true';
    // Resumable-upload protocol — used automatically for files >= 64 MiB so
    // multi-GB dumps stream in chunks instead of loading into memory.
    const RESUMABLE_URL = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true&fields=id,webViewLink';
    const RESUMABLE_MIN = 67108864; // 64 MiB
    const CHUNK_SIZE    = 8388608;  // 8 MiB — Drive requires multiples of 256 KiB
    const SCOPE      = 'https://www.googleapis.com/auth/drive.file';
    const FOLDER_MIME = 'application/vnd.google-apps.folder';
    const FOLDER_NAME = 'FBMSO Backups';

    private $sa;      // service-account creds (mode = 'sa')
    private $oauth;   // client_id/client_secret/refresh_token (mode = 'oauth')
    private $mode = 'sa';
    private $error = '';
    private $resolvedFolder = '';

    /**
     * @param array|string $credentials SA JSON string/array, or an array with
     *        client_id + client_secret + refresh_token for OAuth user mode.
     */
    public function __construct($credentials)
    {
        $creds = is_array($credentials) ? $credentials : json_decode((string)$credentials, true);

        if (!is_array($creds)) {
            $this->error = 'credentials are not valid JSON';
            return;
        }

        if (!empty($creds['refresh_token'])) {
            foreach (array('client_id', 'client_secret', 'refresh_token') as $key) {
                if (empty($creds[$key])) {
                    $this->error = 'OAuth credentials are missing "' . $key . '"';
                    return;
                }
            }
            $this->mode  = 'oauth';
            $this->oauth = $creds;
            return;
        }

        foreach (array('client_email', 'private_key') as $key) {
            if (empty($creds[$key])) {
                $this->error = 'service account JSON is missing "' . $key . '"';
                return;
            }
        }

        if (!function_exists('openssl_pkey_get_private') || !function_exists('openssl_sign')) {
            $this->error = 'the openssl PHP extension is required to sign the token request';
            return;
        }

        $this->sa = $creds;
    }

    public function error()
    {
        return $this->error;
    }

    /** Folder the file actually landed in (auto-created folder fallback). */
    public function resolvedFolderId()
    {
        return $this->resolvedFolder;
    }

    /**
     * Exchange an OAuth authorization code for a refresh token.
     *
     * @return string|false refresh token on success, false on failure (see error()).
     */
    public function exchangeCode($clientId, $clientSecret, $code, $redirectUri)
    {
        $resp = $this->tokenPost(array(
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ));

        if ($resp === null) {
            return false; // error() already carries the curl failure detail
        }

        list($status, $raw) = $resp;
        $data    = json_decode($raw, true);
        $refresh = isset($data['refresh_token']) ? (string)$data['refresh_token'] : '';

        if ($status >= 200 && $status < 300 && $refresh !== '') {
            return $refresh;
        }

        $this->error = isset($data['error_description']) ? (string)$data['error_description']
                     : (isset($data['error']) ? (string)$data['error']
                     : 'Google token exchange failed (HTTP ' . $status . ')');
        return false;
    }

    /**
     * Upload $filePath to Drive.
     *
     * @return array|false ['id' => drive file id, 'link' => webViewLink] or
     *                     false on failure (see error()).
     */
    public function upload($filePath, $name = null, $folderId = '')
    {
        if (($this->sa === null && $this->oauth === null) || $this->error !== '') {
            return false;
        }
        if (!is_file($filePath) || !is_readable($filePath)) {
            $this->error = 'file not found: ' . basename((string)$filePath);
            return false;
        }
        if (!function_exists('curl_init')) {
            $this->error = 'cURL is not available on this PHP server';
            return false;
        }

        $token = $this->accessToken();
        if ($token === null) {
            return false;
        }

        $name = $name !== null ? $name : basename($filePath);
        $folderId = trim((string)$folderId);

        // In OAuth mode the app can only write into folders it can see
        // (drive.file scope): try the configured folder first, and on a
        // permission miss fall back to our own "FBMSO Backups" folder.
        if ($folderId !== '') {
            $result = $this->doUpload($token, $filePath, $name, $folderId);
            if ($result !== false) {
                $this->resolvedFolder = $folderId;
                return $result;
            }
            if ($this->mode !== 'oauth' || !$this->isPermError) {
                return false; // SA mode: surface the real error (e.g. quota)
            }
        }

        if ($this->mode === 'oauth') {
            $auto = $this->ensureFolder($token);
            if ($auto === null) {
                return false;
            }
            $this->resolvedFolder = $auto;
            return $this->doUpload($token, $filePath, $name, $auto);
        }

        $result = $this->doUpload($token, $filePath, $name, '');
        if ($result !== false) {
            $this->resolvedFolder = '';
        }
        return $result;
    }

    private $isPermError = false;

    /**
     * One upload attempt. Picks multipart (small files, one request) or
     * resumable chunked (large files, flat memory) by size. Keeps the last
     * error in $this->error and flags permission-style failures in
     * $this->isPermError so upload() can decide whether the auto-folder
     * fallback is worth trying.
     */
    private function doUpload($token, $filePath, $name, $folderId)
    {
        $this->isPermError = false;

        $size = filesize($filePath);
        if ($size !== false && $size >= self::RESUMABLE_MIN) {
            return $this->doUploadResumable($token, $filePath, $name, $folderId);
        }
        return $this->doUploadMultipart($token, $filePath, $name, $folderId);
    }

    private function doUploadMultipart($token, $filePath, $name, $folderId)
    {
        $meta = array('name' => $name);
        if ($folderId !== '') {
            $meta['parents'] = array($folderId);
        }

        $boundary = 'fbmso_backup_' . bin2hex(random_bytes(8));
        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($meta) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/gzip\r\n\r\n";
        $body .= file_get_contents($filePath) . "\r\n";
        $body .= "--{$boundary}--";

        $resp = $this->httpPost(self::UPLOAD_URL, $body, array(
            'Content-Type: multipart/related; boundary=' . $boundary,
            'Authorization: Bearer ' . $token,
        ));

        if ($resp === null) {
            return false;
        }

        list($status, $raw) = $resp;
        $data = json_decode($raw, true);

        if ($status >= 200 && $status < 300 && !empty($data['id'])) {
            return array(
                'id'   => (string)$data['id'],
                'link' => isset($data['webViewLink']) ? (string)$data['webViewLink']
                        : 'https://drive.google.com/file/d/' . $data['id'] . '/view',
            );
        }

        if ($status === 404 || $status === 403) {
            $reason = isset($data['error']['errors'][0]['reason']) ? (string)$data['error']['errors'][0]['reason'] : '';
            $this->isPermError = in_array($reason, array('notFound', 'insufficientFilePermissions', 'insufficientPermissions'), true);
        }

        $msg = isset($data['error']['message']) ? (string)$data['error']['message'] : trim((string)$raw);
        $this->error = 'Drive upload failed (HTTP ' . $status . '): ' . substr($msg, 0, 300);
        return false;
    }

    /**
     * Resumable-upload path for large dumps: opens a session, streams the
     * file in 8 MiB chunks (flat ~8MB memory regardless of file size), and
     * on a dropped connection asks the session how much Google already has
     * and continues from there instead of restarting.
     */
    private function doUploadResumable($token, $filePath, $name, $folderId)
    {
        $size = filesize($filePath);
        if ($size === false || $size <= 0) {
            $this->error = 'cannot stat upload file';
            return false;
        }

        $meta = array('name' => $name);
        if ($folderId !== '') {
            $meta['parents'] = array($folderId);
        }

        // --- Open the resumable session --------------------------------
        $hdrs = array();
        $resp = $this->httpRaw('POST', self::RESUMABLE_URL, json_encode($meta), array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=UTF-8',
            'X-Upload-Content-Type: application/gzip',
            'X-Upload-Content-Length: ' . $size,
        ), 60, $hdrs);

        if ($resp === null) {
            return false;
        }

        list($status, $raw) = $resp;
        $session = isset($hdrs['location']) ? $hdrs['location'] : '';
        if ($session === '' || !($status >= 200 && $status < 300)) {
            $data = json_decode($raw, true);
            if ($status === 403 || $status === 404) {
                $reason = isset($data['error']['errors'][0]['reason']) ? (string)$data['error']['errors'][0]['reason'] : '';
                $this->isPermError = in_array($reason, array('notFound', 'insufficientFilePermissions', 'insufficientPermissions'), true);
            }
            $msg = isset($data['error']['message']) ? (string)$data['error']['message'] : trim((string)$raw);
            $this->error = 'Drive upload failed (HTTP ' . $status . '): ' . substr($msg, 0, 300);
            return false;
        }

        $fh = fopen($filePath, 'rb');
        if (!$fh) {
            $this->error = 'cannot open upload file';
            return false;
        }

        // --- Stream chunks ----------------------------------------------
        $offset   = 0;
        $failures = 0;
        $final    = null;

        while ($offset < $size) {
            $chunk = fread($fh, min(self::CHUNK_SIZE, $size - $offset));
            if ($chunk === false || $chunk === '') {
                fclose($fh);
                $this->error = 'could not read backup file for upload';
                return false;
            }
            $end = $offset + strlen($chunk) - 1;

            $resp = $this->httpRaw('PUT', $session, $chunk, array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/gzip',
                'Content-Range: bytes ' . $offset . '-' . $end . '/' . $size,
            ), 300, $hdrs);

            $status = ($resp !== null) ? $resp[0] : 0;
            $raw    = ($resp !== null) ? $resp[1] : '';

            if ($status === 308) {
                // Chunk accepted — the Range header is authoritative for how
                // many bytes actually arrived (a cut mid-send counts less).
                if (isset($hdrs['range']) && preg_match('/bytes=(\d+)-(\d+)/', $hdrs['range'], $m)) {
                    $offset = (int)$m[2] + 1;
                    fseek($fh, $offset);
                } else {
                    $offset = $end + 1;
                }
                $failures = 0;
                continue;
            }

            if ($status >= 200 && $status < 300) {
                $final = json_decode($raw, true);
                break;
            }

            if (++$failures > 8) {
                fclose($fh);
                $detail = trim((string)$raw) !== '' ? trim((string)$raw) : $this->error;
                $this->error = 'Drive upload failed after retries (HTTP ' . $status . '): ' . substr($detail, 0, 300);
                return false;
            }

            // Dropped mid-upload — ask the session how much it has and
            // continue from there (may be less than we think we sent).
            sleep(min(10, $failures * 2));
            $offset = $this->queryUploadOffset($session, $size, $token);
            fseek($fh, $offset);
        }

        fclose($fh);

        if (is_array($final) && !empty($final['id'])) {
            return array(
                'id'   => (string)$final['id'],
                'link' => isset($final['webViewLink']) ? (string)$final['webViewLink']
                        : 'https://drive.google.com/file/d/' . $final['id'] . '/view',
            );
        }

        $this->error = 'Drive upload ended without a file id';
        return false;
    }

    /**
     * Ask a resumable session how many bytes it has so far.
     * Returns the offset to continue writing from.
     */
    private function queryUploadOffset($session, $size, $token)
    {
        $hdrs = array();
        $resp = $this->httpRaw('PUT', $session, '', array(
            'Authorization: Bearer ' . $token,
            'Content-Range: bytes */' . $size,
        ), 60, $hdrs);

        if ($resp !== null && isset($hdrs['range']) && preg_match('/bytes=(\d+)-(\d+)/', $hdrs['range'], $m)) {
            return (int)$m[2] + 1;
        }
        return 0; // session holds nothing (or is gone) — start over
    }

    /**
     * Low-level HTTP request that also captures response headers (needed
     * for resumable Location / Range). No retry — callers decide.
     */
    private function httpRaw($method, $url, $body, array $headers, $timeout, &$respHeaders)
    {
        $respHeaders = array();
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$respHeaders) {
                $len = strlen($line);
                $t   = trim($line);
                if (strpos($t, ':') !== false) {
                    list($k, $v) = explode(':', $t, 2);
                    $respHeaders[strtolower(trim($k))] = trim($v);
                }
                return $len;
            },
        ));

        $raw    = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errMsg = curl_error($ch);
        unset($ch); // curl handles free themselves since PHP 8.0 (curl_close deprecated in 8.5)

        if ($raw === false) {
            $this->error = 'HTTPS request failed: ' . $errMsg;
            return null;
        }

        return array($status, (string)$raw);
    }

    /**
     * Find-or-create the app's own folder ("FBMSO Backups") in the user's
     * Drive. Needed for drive.file scope — files made by the app are only
     * reachable through folders the app itself created.
     */
    private function ensureFolder($token)
    {
        $q = "name='" . self::FOLDER_NAME . "' and mimeType='" . self::FOLDER_MIME . "' and trashed=false";
        $resp = $this->httpGet(self::FILES_URL . '?q=' . urlencode($q) . '&supportsAllDrives=true&fields=files(id)', $token);
        if ($resp !== null) {
            list($status, $raw) = $resp;
            $data = json_decode($raw, true);
            if ($status >= 200 && $status < 300 && !empty($data['files'][0]['id'])) {
                return (string)$data['files'][0]['id'];
            }
        }

        $resp = $this->httpPost(self::FILES_URL . '?supportsAllDrives=true', json_encode(array(
            'name'     => self::FOLDER_NAME,
            'mimeType' => self::FOLDER_MIME,
        )), array(
            'Content-Type: application/json; charset=UTF-8',
            'Authorization: Bearer ' . $token,
        ));

        if ($resp === null) {
            return null;
        }

        list($status, $raw) = $resp;
        $data = json_decode($raw, true);
        if ($status >= 200 && $status < 300 && !empty($data['id'])) {
            return (string)$data['id'];
        }

        $msg = isset($data['error']['message']) ? (string)$data['error']['message'] : trim((string)$raw);
        $this->error = 'could not create the "' . self::FOLDER_NAME . '" folder (HTTP ' . $status . '): ' . substr($msg, 0, 200);
        return null;
    }

    /**
     * Mint/exchange for an access token — OAuth refresh grant or SA JWT.
     */
    private function accessToken()
    {
        if ($this->mode === 'oauth') {
            $resp = $this->tokenPost(array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->oauth['refresh_token'],
                'client_id'     => $this->oauth['client_id'],
                'client_secret' => $this->oauth['client_secret'],
            ));

            if ($resp === null) {
                return null;
            }

            list($status, $raw) = $resp;
            $data = json_decode($raw, true);
            if ($status >= 200 && $status < 300 && !empty($data['access_token'])) {
                return (string)$data['access_token'];
            }

            $msg = isset($data['error_description']) ? (string)$data['error_description']
                 : (isset($data['error']) ? (string)$data['error'] : trim((string)$raw));
            $this->error = 'Google token refresh failed (HTTP ' . $status . '): ' . substr($msg, 0, 300);
            return null;
        }

        $now = time();
        $claims = array(
            'iss'   => $this->sa['client_email'],
            'scope' => self::SCOPE,
            'aud'   => self::TOKEN_URL,
            'iat'   => $now,
            'exp'   => $now + 3600,
        );

        $jwt = $this->b64(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')))
             . '.' . $this->b64(json_encode($claims));

        $key = openssl_pkey_get_private($this->sa['private_key']);
        if ($key === false) {
            $this->error = 'service account private_key could not be loaded';
            return null;
        }

        $signature = '';
        if (!openssl_sign($jwt, $signature, $key, OPENSSL_ALGO_SHA256)) {
            $this->error = 'could not sign the token request';
            return null;
        }

        $jwt .= '.' . $this->b64($signature);

        $resp = $this->tokenPost(array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ));

        if ($resp === null) {
            return null;
        }

        list($status, $raw) = $resp;
        $data = json_decode($raw, true);

        if ($status >= 200 && $status < 300 && !empty($data['access_token'])) {
            return (string)$data['access_token'];
        }

        $msg = isset($data['error_description']) ? (string)$data['error_description']
             : (isset($data['error']) ? (string)$data['error'] : trim((string)$raw));
        $this->error = 'Google token request failed (HTTP ' . $status . '): ' . substr($msg, 0, 300);
        return null;
    }

    /**
     * POST url-encoded params to Google's token service. Tries the canonical
     * endpoint, then the same service on the reachable www.googleapis.com
     * edge — some hosts' routes to oauth2.googleapis.com time out entirely.
     */
    private function tokenPost(array $params)
    {
        $body    = http_build_query($params);
        $headers = array('Content-Type: application/x-www-form-urlencoded');

        foreach (array(self::TOKEN_URL, self::TOKEN_URL_ALT) as $url) {
            $resp = $this->httpPost($url, $body, $headers, 60);
            if ($resp !== null) {
                return $resp;
            }
            $lastErr = $this->error;
        }

        $this->error = isset($lastErr) ? $lastErr : 'Google token endpoint unreachable';
        return null;
    }

    private function httpPost($url, $body, array $headers, $timeout = 300)
    {
        // Two attempts: a shared host's egress occasionally drops a connect
        // attempt — retrying once hides that from the caller.
        for ($try = 0; $try < 2; $try++) {
            if ($try > 0) {
                sleep(2);
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT        => $timeout,
                // Shared hosts frequently have broken IPv6 routes — googleapis
                // resolves v6-first and the connect hangs until timeout.
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            ));

            $raw     = curl_exec($ch);
            $curlErr = curl_errno($ch);
            $status  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errMsg  = curl_error($ch);
            unset($ch); // curl handles free themselves since PHP 8.0 (curl_close deprecated in 8.5)

            if ($raw !== false) {
                return array($status, (string)$raw);
            }
            // Only retry transport failures, not HTTP responses.
            if (!in_array($curlErr, array(CURLE_COULDNT_CONNECT, CURLE_OPERATION_TIMEDOUT, CURLE_SEND_ERROR, CURLE_RECV_ERROR), true)) {
                break;
            }
        }

        $this->error = 'HTTPS request failed: ' . $errMsg;
        return null;
    }

    private function httpGet($url, $token)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array('Authorization: Bearer ' . $token),
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        ));

        $raw     = curl_exec($ch);
        $curlErr = curl_error($ch);
        $status  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch); // curl handles free themselves since PHP 8.0 (curl_close deprecated in 8.5)

        if ($raw === false) {
            $this->error = 'HTTPS request failed: ' . $curlErr;
            return null;
        }

        return array($status, (string)$raw);
    }

    private function b64($data)
    {
        return rtrim(strtr(base64_encode((string)$data), '+/', '-_'), '=');
    }
}
