<?php
/**
 * TEMPORARY outbound-connectivity probe for diagnosing Drive upload failures.
 * DELETE THIS FILE after use — it exposes server network details.
 */
header('Content-Type: text/plain; charset=utf-8');

$targets = array(
    'Google OAuth token endpoint' => 'https://oauth2.googleapis.com/token',
    'Google Drive API'            => 'https://www.googleapis.com/drive/v3/about',
    'Google (plain homepage)'     => 'https://www.google.com/',
    'Brevo API (your mail works)' => 'https://api.brevo.com/v3/account',
    'GitHub API (control)'        => 'https://api.github.com/',
);

echo "Outbound connectivity test — " . date('Y-m-d H:i:s') . "\n";
echo "PHP " . PHP_VERSION . " / cURL " . (function_exists('curl_version') ? curl_version()['version'] : 'MISSING') . "\n\n";

foreach ($targets as $label => $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY         => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    ));
    curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $ip    = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
    $ct    = round(curl_getinfo($ch, CURLINFO_CONNECT_TIME) * 1000);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    printf(
        "%-32s %s\n     ip=%s connect=%dms http=%d err=%d %s\n\n",
        $label,
        $errno === 0 ? 'REACHABLE' : 'FAILED',
        $ip ?: '-', $ct, $code, $errno, $error
    );
}
echo "Done. Now delete this file (nettest.php) from the server.\n";
