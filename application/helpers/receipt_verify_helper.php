<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Receipt verification helpers — shared by the Accounting controller (which
// prints the QR code) and the public Verify controller (which checks it).

if (!function_exists('receipt_verify_signature')) {
    /**
     * HMAC-bind an O.R. to its student number so the public verification URL
     * can't be forged or enumerated without the app's encryption key.
     */
    function receipt_verify_signature($orNumber, $studentNumber)
    {
        $CI =& get_instance();
        return substr(
            hash_hmac('sha256', (string)$orNumber . '|' . (string)$studentNumber, (string)$CI->config->item('encryption_key')),
            0,
            16
        );
    }
}

if (!function_exists('receipt_verify_url')) {
    /**
     * Absolute verify URL encoded in the receipt's QR code.
     * Returns '' when the payment lacks an O.R. or student number.
     */
    function receipt_verify_url($orNumber, $studentNumber)
    {
        $or = trim((string)$orNumber);
        $sn = trim((string)$studentNumber);
        if ($or === '' || $sn === '') {
            return '';
        }
        return site_url('verify/receipt/' . rawurlencode($or) . '/' . receipt_verify_signature($or, $sn));
    }
}
