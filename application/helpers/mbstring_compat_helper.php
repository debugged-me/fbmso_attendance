<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * mbstring fallback shims.
 *
 * The production PHP build does not load ext/mbstring while local XAMPP
 * does, so any request that reaches an mb_*() call dies with "Call to
 * undefined function" (observed: student picker on Accounting/Payment).
 * The functions below cover the subset of mbstring this app and its
 * bundled libraries (PHPExcel, SimpleXLSX) actually call. When the real
 * extension is present every wrapper is skipped by function_exists(),
 * so this file is inert locally and becomes dormant again if the host
 * enables mbstring.
 *
 * Behaviour notes:
 *  - Encoding is treated as UTF-8 when omitted or 'UTF-8'; any other
 *    encoding ('8bit', latin1, ...) falls back to byte-wise string ops.
 *  - Case folding covers ASCII plus Latin-1/Latin Extended-A pairs
 *    (e.g. N-tilde/n-tilde, A-grave..Thorn, Ā-Ž, ß->SS, İ->i), which is
 *    what real data here contains. Fuller Unicode case maps need the
 *    real extension.
 */

/* Constants other code may reference must exist even without the ext. */
if (!defined('MB_CASE_UPPER'))        define('MB_CASE_UPPER', 0);
if (!defined('MB_CASE_LOWER'))        define('MB_CASE_LOWER', 1);
if (!defined('MB_CASE_TITLE'))        define('MB_CASE_TITLE', 2);
if (!defined('MB_CASE_FOLD'))         define('MB_CASE_FOLD', 3);
if (!defined('MB_CASE_UPPER_SIMPLE')) define('MB_CASE_UPPER_SIMPLE', 4);
if (!defined('MB_CASE_LOWER_SIMPLE')) define('MB_CASE_LOWER_SIMPLE', 5);
if (!defined('MB_CASE_TITLE_SIMPLE')) define('MB_CASE_TITLE_SIMPLE', 6);
if (!defined('MB_CASE_FOLD_SIMPLE'))  define('MB_CASE_FOLD_SIMPLE', 7);

/* -------------------------------------------------------------------
 * Internals. Always defined (private _fbmso_mb_* names) so they can be
 * exercised by tests even on hosts that have the real extension.
 * ------------------------------------------------------------------*/

if (!function_exists('_fbmso_mb_is_utf8')) {

    function _fbmso_mb_is_utf8($encoding)
    {
        if ($encoding === null || $encoding === '') {
            return TRUE;
        }
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$encoding)) === 'UTF8';
    }

    /** Encode a Unicode codepoint as UTF-8 without utf8_encode/mb_chr. */
    function _fbmso_mb_chr($cp)
    {
        if ($cp < 0x80)   return chr($cp);
        if ($cp < 0x800)  return chr(0xC0 | ($cp >> 6)) . chr(0x80 | ($cp & 0x3F));
        if ($cp < 0x10000) return chr(0xE0 | ($cp >> 12)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
        return chr(0xF0 | ($cp >> 18)) . chr(0x80 | (($cp >> 12) & 0x3F)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
    }

    /** upper codepoint => lower UTF-8 string. */
    function _fbmso_mb_case_pairs()
    {
        static $pairs = NULL;
        if ($pairs !== NULL) {
            return $pairs;
        }
        $pairs = array();
        // Latin-1 Supplement capitals -> smalls (× has no case pair).
        for ($cp = 0xC0; $cp <= 0xDE; $cp++) {
            if ($cp === 0xD7) continue;
            $pairs[$cp] = _fbmso_mb_chr($cp + 0x20);
        }
        // Latin Extended-A. Capital/small pairs are adjacent codepoints but
        // which side is the capital flips between blocks, so the block
        // starts are listed explicitly (0x138 ĸ and 0x149 ŉ are smalls and
        // must not be treated as capitals).
        for ($cp = 0x100; $cp <= 0x12F; $cp += 2) $pairs[$cp] = _fbmso_mb_chr($cp + 1);
        foreach (array(0x132, 0x134, 0x136) as $cp) $pairs[$cp] = _fbmso_mb_chr($cp + 1);
        for ($cp = 0x139; $cp <= 0x147; $cp += 2) $pairs[$cp] = _fbmso_mb_chr($cp + 1);
        for ($cp = 0x14A; $cp <= 0x176; $cp += 2) $pairs[$cp] = _fbmso_mb_chr($cp + 1);
        for ($cp = 0x179; $cp <= 0x17D; $cp += 2) $pairs[$cp] = _fbmso_mb_chr($cp + 1);
        $pairs[0x130] = "i\u{307}";               // İ -> i + combining dot
        $pairs[0x178] = _fbmso_mb_chr(0xFF);      // Ÿ -> ÿ
        // Greek capitals -> smalls (accented vowels have uneven offsets).
        $greekAccent = array(0x386=>0x3AC, 0x388=>0x3AD, 0x389=>0x3AE, 0x38A=>0x3AF, 0x38C=>0x3CC, 0x38E=>0x3CD, 0x38F=>0x3CE);
        foreach ($greekAccent as $up => $lo) $pairs[$up] = _fbmso_mb_chr($lo);
        for ($cp = 0x391; $cp <= 0x3A9; $cp++) {
            if ($cp === 0x3A2) continue;
            $pairs[$cp] = _fbmso_mb_chr($cp + 0x20);
        }
        // Cyrillic capitals -> smalls.
        for ($cp = 0x400; $cp <= 0x40F; $cp++) $pairs[$cp] = _fbmso_mb_chr($cp + 0x50);
        for ($cp = 0x410; $cp <= 0x42F; $cp++) $pairs[$cp] = _fbmso_mb_chr($cp + 0x20);
        return $pairs;
    }

    /** One-way lower => upper mappings that are not in the pairs table:
     *  final sigma, long s, ŉ, ß and the ﬀ..ﬆ ligatures. */
    function _fbmso_mb_extra_uppers()
    {
        static $extra = NULL;
        if ($extra === NULL) {
            $extra = array(
                _fbmso_mb_chr(0x3C2) => _fbmso_mb_chr(0x3A3), // ς -> Σ
                _fbmso_mb_chr(0x17F) => 'S',                  // ſ -> S
                _fbmso_mb_chr(0x149) => "\u{2BC}N",           // ŉ -> ʼN
                _fbmso_mb_chr(0xDF)  => 'SS',                 // ß -> SS
                _fbmso_mb_chr(0xFB00) => 'FF', _fbmso_mb_chr(0xFB01) => 'FI',
                _fbmso_mb_chr(0xFB02) => 'FL', _fbmso_mb_chr(0xFB03) => 'FFI',
                _fbmso_mb_chr(0xFB04) => 'FFL', _fbmso_mb_chr(0xFB05) => 'ST',
                _fbmso_mb_chr(0xFB06) => 'ST',
            );
        }
        return $extra;
    }

    function _fbmso_mb_upper_to_lower()
    {
        static $map = NULL;
        if ($map === NULL) {
            $map = array();
            foreach (_fbmso_mb_case_pairs() as $upper => $lower) {
                $map[_fbmso_mb_chr($upper)] = $lower;
            }
        }
        return $map;
    }

    function _fbmso_mb_lower_to_upper()
    {
        static $map = NULL;
        if ($map === NULL) {
            $map = _fbmso_mb_extra_uppers();
            foreach (_fbmso_mb_case_pairs() as $upper => $lower) {
                if ($upper === 0x130) continue; // i+dot -> ASCII 'I' handled natively
                $map[$lower] = _fbmso_mb_chr($upper);
            }
        }
        return $map;
    }

    function _fbmso_mb_strlen($string, $encoding = NULL)
    {
        $string = (string)$string;
        if (!_fbmso_mb_is_utf8($encoding)) {
            return strlen($string);
        }
        if (function_exists('iconv_strlen')) {
            $len = @iconv_strlen($string, 'UTF-8');
            if ($len !== FALSE) {
                return $len;
            }
        }
        $count = preg_match_all('/./us', $string);
        return $count === FALSE ? strlen($string) : $count;
    }

    function _fbmso_mb_substr($string, $start, $length = NULL, $encoding = NULL)
    {
        $string = (string)$string;
        if (_fbmso_mb_is_utf8($encoding)) {
            $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
            if ($chars !== FALSE) {
                return implode('', array_slice($chars, (int)$start, $length === NULL ? NULL : (int)$length));
            }
        }
        return $length === NULL ? substr($string, (int)$start) : substr($string, (int)$start, (int)$length);
    }

    function _fbmso_mb_strpos($haystack, $needle, $offset = 0, $encoding = NULL)
    {
        $haystack = (string)$haystack;
        $needle   = (string)$needle;
        if (!_fbmso_mb_is_utf8($encoding)) {
            return strpos($haystack, $needle, (int)$offset);
        }
        if (function_exists('iconv_strpos')) {
            $pos = @iconv_strpos($haystack, $needle, (int)$offset, 'UTF-8');
            if ($pos !== FALSE || @iconv_strlen($haystack, 'UTF-8') !== FALSE) {
                return $pos;
            }
        }
        // Byte-offset the char offset, find the byte position, convert back.
        $byteOffset = strlen(_fbmso_mb_substr($haystack, 0, (int)$offset, 'UTF-8'));
        $pos = strpos($haystack, $needle, $byteOffset);
        return $pos === FALSE ? FALSE : _fbmso_mb_strlen(substr($haystack, 0, $pos), 'UTF-8');
    }

    function _fbmso_mb_strrpos($haystack, $needle, $offset = 0, $encoding = NULL)
    {
        $haystack = (string)$haystack;
        if (!_fbmso_mb_is_utf8($encoding)) {
            return strrpos($haystack, (string)$needle, (int)$offset);
        }
        $byteOffset = strlen(_fbmso_mb_substr($haystack, 0, (int)$offset, 'UTF-8'));
        $pos = strrpos($haystack, (string)$needle, $byteOffset);
        return $pos === FALSE ? FALSE : _fbmso_mb_strlen(substr($haystack, 0, $pos), 'UTF-8');
    }

    function _fbmso_mb_stripos($haystack, $needle, $offset = 0, $encoding = NULL)
    {
        if (!_fbmso_mb_is_utf8($encoding)) {
            return stripos((string)$haystack, (string)$needle, (int)$offset);
        }
        if (function_exists('iconv_stripos')) {
            $pos = @iconv_stripos((string)$haystack, (string)$needle, (int)$offset, 'UTF-8');
            if ($pos !== FALSE || @iconv_strlen((string)$haystack, 'UTF-8') !== FALSE) {
                return $pos;
            }
        }
        return _fbmso_mb_strpos(
            _fbmso_mb_strtolower((string)$haystack, 'UTF-8'),
            _fbmso_mb_strtolower((string)$needle, 'UTF-8'),
            (int)$offset,
            'UTF-8'
        );
    }

    function _fbmso_mb_strtolower($string, $encoding = NULL)
    {
        $string = strtolower((string)$string); // ASCII only; UTF-8 bytes untouched
        return _fbmso_mb_is_utf8($encoding) ? strtr($string, _fbmso_mb_upper_to_lower()) : $string;
    }

    function _fbmso_mb_strtoupper($string, $encoding = NULL)
    {
        $string = strtoupper((string)$string);
        return _fbmso_mb_is_utf8($encoding) ? strtr($string, _fbmso_mb_lower_to_upper()) : $string;
    }

    function _fbmso_mb_convert_case($string, $mode, $encoding = NULL)
    {
        if ($mode === MB_CASE_UPPER || $mode === MB_CASE_UPPER_SIMPLE) {
            return _fbmso_mb_strtoupper($string, $encoding);
        }
        if ($mode === MB_CASE_TITLE || $mode === MB_CASE_TITLE_SIMPLE) {
            $lower = _fbmso_mb_strtolower($string, $encoding);
            $titled = preg_replace_callback('/(^|[^\pL\pN])(\pL)/u', function ($m) {
                return $m[1] . _fbmso_mb_strtoupper($m[2], 'UTF-8');
            }, $lower);
            return $titled === NULL ? ucwords($lower) : $titled;
        }
        return _fbmso_mb_strtolower($string, $encoding); // LOWER / FOLD / *_SIMPLE
    }

    function _fbmso_mb_convert_encoding($string, $to_encoding, $from_encoding = NULL)
    {
        $string = (string)$string;
        if (is_array($from_encoding)) {
            $from_encoding = $from_encoding ? reset($from_encoding) : 'UTF-8';
        }
        $from = _fbmso_mb_norm_enc($from_encoding === NULL ? 'UTF-8' : $from_encoding);
        $to   = _fbmso_mb_norm_enc($to_encoding);
        if ($from === $to) {
            return $string;
        }
        if ($to === 'HTMLENTITIES') {
            return htmlentities($string, ENT_QUOTES, $from === 'UTF8' ? 'UTF-8' : (string)$from_encoding);
        }
        if ($from === 'HTMLENTITIES') {
            return html_entity_decode($string, ENT_QUOTES, $to === 'UTF8' ? 'UTF-8' : (string)$to_encoding);
        }
        if (function_exists('iconv')) {
            $out = @iconv((string)$from_encoding, (string)$to_encoding, $string);
            if ($out !== FALSE) {
                return $out;
            }
        }
        return $string;
    }

    function _fbmso_mb_norm_enc($encoding)
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$encoding));
    }

    function _fbmso_mb_str_split($string, $length = 1, $encoding = NULL)
    {
        $length = max(1, (int)$length);
        $string = (string)$string;
        if ($string === '') {
            return array();
        }
        $chars = _fbmso_mb_is_utf8($encoding) ? preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY) : FALSE;
        if ($chars === FALSE) {
            $chars = str_split($string);
        }
        return $length === 1 ? $chars : array_map('implode', array_chunk($chars, $length));
    }

    function _fbmso_mb_str_replace($search, $replace, $subject, &$count = NULL)
    {
        return str_replace($search, $replace, $subject, $count);
    }

    function _fbmso_mb_check_encoding($value = NULL, $encoding = NULL)
    {
        if ($value === NULL || !_fbmso_mb_is_utf8($encoding)) {
            return TRUE;
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                if (!_fbmso_mb_check_encoding($v, $encoding)) {
                    return FALSE;
                }
            }
            return TRUE;
        }
        return preg_match('//u', (string)$value) === 1;
    }

    function _fbmso_mb_detect_encoding($string, $encodings = NULL, $strict = FALSE)
    {
        $string = (string)$string;
        $list = is_array($encodings) && $encodings ? $encodings : ($encodings !== NULL ? array($encodings) : array('ASCII', 'UTF-8'));
        foreach ($list as $enc) {
            $n = _fbmso_mb_norm_enc($enc);
            if ($n === 'ASCII' && preg_match('/^[\x00-\x7F]*$/', $string)) {
                return 'ASCII';
            }
            if ($n === 'UTF8' && preg_match('//u', $string)) {
                return 'UTF-8';
            }
            if (($n === 'ISO88591' || $n === 'LATIN1' || $n === '8BIT') && $string !== '') {
                return $enc;
            }
        }
        return FALSE;
    }
}

/* -------------------------------------------------------------------
 * Global mb_* wrappers — only defined when the extension is absent.
 * ------------------------------------------------------------------*/

if (!function_exists('mb_internal_encoding')) {
    function mb_internal_encoding($encoding = NULL) { return $encoding === NULL ? 'UTF-8' : TRUE; }
}
if (!function_exists('mb_substitute_character')) {
    function mb_substitute_character($substitute_character = NULL) { return TRUE; }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = NULL) { return _fbmso_mb_strlen($string, $encoding); }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = NULL, $encoding = NULL) { return _fbmso_mb_substr($string, $start, $length, $encoding); }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0, $encoding = NULL) { return _fbmso_mb_strpos($haystack, $needle, $offset, $encoding); }
}
if (!function_exists('mb_strrpos')) {
    function mb_strrpos($haystack, $needle, $offset = 0, $encoding = NULL) { return _fbmso_mb_strrpos($haystack, $needle, $offset, $encoding); }
}
if (!function_exists('mb_stripos')) {
    function mb_stripos($haystack, $needle, $offset = 0, $encoding = NULL) { return _fbmso_mb_stripos($haystack, $needle, $offset, $encoding); }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = NULL) { return _fbmso_mb_strtolower($string, $encoding); }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($string, $encoding = NULL) { return _fbmso_mb_strtoupper($string, $encoding); }
}
if (!function_exists('mb_convert_case')) {
    function mb_convert_case($string, $mode, $encoding = NULL) { return _fbmso_mb_convert_case($string, $mode, $encoding); }
}
if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($string, $to_encoding, $from_encoding = NULL) { return _fbmso_mb_convert_encoding($string, $to_encoding, $from_encoding); }
}
if (!function_exists('mb_str_split')) {
    function mb_str_split($string, $length = 1, $encoding = NULL) { return _fbmso_mb_str_split($string, $length, $encoding); }
}
if (!function_exists('mb_str_replace')) {
    function mb_str_replace($search, $replace, $subject, &$count = NULL) { return _fbmso_mb_str_replace($search, $replace, $subject, $count); }
}
if (!function_exists('mb_check_encoding')) {
    function mb_check_encoding($value = NULL, $encoding = NULL) { return _fbmso_mb_check_encoding($value, $encoding); }
}
if (!function_exists('mb_detect_encoding')) {
    function mb_detect_encoding($string, $encodings = NULL, $strict = FALSE) { return _fbmso_mb_detect_encoding($string, $encodings, $strict); }
}
