<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// gitlost removed namespace stuff, renamed to TLN_Normalizer to avoid conflicts, allowed to be included more than once.
// gitlost subbed tln_is_valid_utf8()/tln_in_XXX() for PCRE UTF-8 mode tests, added full isNormalized() check by falling back to normalize(), made mb_overload_string safe.
// https://github.com/symfony/polyfill/tree/master/src/Intl/Normalizer

// namespace Symfony\Polyfill\Intl\Normalizer; // gitlost

/**
 * Normalizer is a PHP fallback implementation of the Normalizer class provided by the intl extension.
 *
 * It has been validated with Unicode 8.0.0 Normalization Conformance Test. // gitlost
 * See http://www.unicode.org/reports/tr15/ for detailed info about Unicode normalizations.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
if ( ! class_exists( 'TLN_Normalizer' ) ) : class TLN_Normalizer // Allow file to be included more than once (for testing) // gitlost
{
    const NONE = 1;
    const FORM_D = 2;
    const FORM_KD = 3;
    const FORM_C = 4;
    const FORM_KC = 5;
    const NFD = 2;
    const NFKD = 3;
    const NFC = 4;
    const NFKC = 5;

    private static $C;
    private static $D;
    private static $KD;
    private static $cC;
    private static $ulenMask = array("\xC0" => 2, "\xD0" => 2, "\xE0" => 3, "\xF0" => 4);
    private static $ASCII = "\x20\x65\x69\x61\x73\x6E\x74\x72\x6F\x6C\x75\x64\x5D\x5B\x63\x6D\x70\x27\x0A\x67\x7C\x68\x76\x2E\x66\x62\x2C\x3A\x3D\x2D\x71\x31\x30\x43\x32\x2A\x79\x78\x29\x28\x4C\x39\x41\x53\x2F\x50\x22\x45\x6A\x4D\x49\x6B\x33\x3E\x35\x54\x3C\x44\x34\x7D\x42\x7B\x38\x46\x77\x52\x36\x37\x55\x47\x4E\x3B\x4A\x7A\x56\x23\x48\x4F\x57\x5F\x26\x21\x4B\x3F\x58\x51\x25\x59\x5C\x09\x5A\x2B\x7E\x5E\x24\x40\x60\x7F\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";
	private static $s, $form, $normalize; // gitlost Cache of call to normalize() in isNormalized().

    public static function isNormalized($s, $form = self::NFC)
    {
        if ($form <= self::NONE || self::NFKC < $form) {
			return false;
		}
        if (!isset($s[strspn($s .= '', self::$ASCII)])) {
            return true;
        }
		// gitlost begin
        if (self::NFC === $form) { // if (self::NFC === $form && preg_match('//u', $s) && !preg_match('/[^\x00-\x{2FF}]/u', $s)) {
			if (!tln_is_valid_utf8($s)) {
				return false;
			}
			if (tln_in_nfc_noes($s)) { // If contains characters that can never be in a NFC normalized string.
				return false;
			}
			if (!tln_in_nfc_maybes_or_reorders($s)) { // If doesn't have any characters that might be recomposed for NFC or reordered.
				return true;
			}
		}

		$normalize = self::normalize($s, $form); // Give true answer by doing full normalize check.
		$result = ($s === $normalize);

		if ($result) {
			self::$s = self::$form = self::$normalize = null; // Clear cache.
		} else {
			self::$s = $s; self::$form = $form; self::$normalize = $normalize; // Cache for immediate use in normalize().
		}

        return $result;
		// gitlost end
    }

    public static function normalize($s, $form = self::NFC)
    {
		// gitlost begin
        switch ($form) { // Check $form first to avoid call to tln_is_valid_utf8() when using cache.
            case self::NONE: return tln_is_valid_utf8($s .= '') ? $s : false; // Note must still check validity.
            case self::NFC: $C = true; $K = false; break;
            case self::NFD: $C = false; $K = false; break;
            case self::NFKC: $C = true; $K = true; break;
            case self::NFKD: $C = false; $K = true; break;
            default: return false;
        }

		if (null !== self::$normalize && $s === self::$s && $form === self::$form) { //  Use cache if available.
			$result = self::$normalize;
			self::$s = self::$form = self::$normalize = null; // Clear cache (try to keep memory usage to a min).
			return $result;
		}

        if (!tln_is_valid_utf8($s .= '')) { // if (!preg_match('//u', $s .= '')) {
            return false;
        }
		// gitlost end

        if ('' === $s) {
            return '';
        }

        if ($K && null === self::$KD) {
            self::$KD = self::getData('compatibilityDecomposition');
        }

        if (null === self::$D) {
            self::$D = self::getData('canonicalDecomposition');
            self::$cC = self::getData('combiningClass');
        }

        if (null !== $mbEncoding = (2 /* MB_OVERLOAD_STRING */ & (int) ini_get('mbstring.func_overload')) ? mb_internal_encoding() : null) {
            mb_internal_encoding('8bit');
        }

        $r = self::decompose($s, $K);

        if ($C) {
            if (null === self::$C) {
                self::$C = self::getData('canonicalComposition');
            }

            $r = self::recompose($r);
		}
        if (null !== $mbEncoding) {
            mb_internal_encoding($mbEncoding);
        }

		return $r;
    }

    private static function recompose($s)
    {
        $ASCII = self::$ASCII;
        $compMap = self::$C;
        $combClass = self::$cC;
        $ulenMask = self::$ulenMask;

        $result = $tail = '';

        $i = $s[0] < "\x80" ? 1 : $ulenMask[$s[0] & "\xF0"];
        $len = strlen($s);

        $lastUchr = substr($s, 0, $i);
        $lastUcls = isset($combClass[$lastUchr]) ? 256 : 0;

        while ($i < $len) {
            if ($s[$i] < "\x80") {
                // ASCII chars

                if ($tail) {
                    $lastUchr .= $tail;
                    $tail = '';
                }

                if ($j = strspn($s, $ASCII, $i + 1)) {
                    $lastUchr .= substr($s, $i, $j);
                    $i += $j;
                }

                $result .= $lastUchr;
                $lastUchr = $s[$i];
                $lastUcls = 0;
                ++$i;
                continue;
            }

            $ulen = $ulenMask[$s[$i] & "\xF0"];
            $uchr = substr($s, $i, $ulen);

            if ($lastUchr < "\xE1\x84\x80" || "\xE1\x84\x92" < $lastUchr
                ||   $uchr < "\xE1\x85\xA1" || "\xE1\x85\xB5" < $uchr
                || $lastUcls) {
                // Table lookup and combining chars composition

                $ucls = isset($combClass[$uchr]) ? $combClass[$uchr] : 0;

                if (isset($compMap[$lastUchr.$uchr]) && (!$lastUcls || $lastUcls < $ucls)) {
                    $lastUchr = $compMap[$lastUchr.$uchr];
                } elseif ($lastUcls = $ucls) {
                    $tail .= $uchr;
                } else {
                    if ($tail) {
                        $lastUchr .= $tail;
                        $tail = '';
                    }

                    $result .= $lastUchr;
                    $lastUchr = $uchr;
                }
            } else {
                // Hangul chars

                $L = ord($lastUchr[2]) - 0x80;
                $V = ord($uchr[2]) - 0xA1;
                $T = 0;

                $uchr = substr($s, $i + $ulen, 3);

                if ("\xE1\x86\xA7" <= $uchr && $uchr <= "\xE1\x87\x82") {
                    $T = ord($uchr[2]) - 0xA7;
                    0 > $T && $T += 0x40;
                    $ulen += 3;
                }

                $L = 0xAC00 + ($L * 21 + $V) * 28 + $T;
                $lastUchr = chr(0xE0 | $L >> 12).chr(0x80 | $L >> 6 & 0x3F).chr(0x80 | $L & 0x3F);
            }

            $i += $ulen;
        }

        return $result.$lastUchr.$tail;
    }

    private static function decompose($s, $c)
    {
        $result = '';

        $ASCII = self::$ASCII;
        $decompMap = self::$D;
        $combClass = self::$cC;
        $ulenMask = self::$ulenMask;
        if ($c) {
            $compatMap = self::$KD;
        }

        $c = array();
        $i = 0;
        $len = strlen($s);

        while ($i < $len) {
            if ($s[$i] < "\x80") {
                // ASCII chars

                if ($c) {
                    ksort($c);
                    $result .= implode('', $c);
                    $c = array();
                }

                $j = 1 + strspn($s, $ASCII, $i + 1);
                $result .= substr($s, $i, $j);
                $i += $j;
                continue;
            }

            $ulen = $ulenMask[$s[$i] & "\xF0"];
            $uchr = substr($s, $i, $ulen);
            $i += $ulen;

            if ($uchr < "\xEA\xB0\x80" || "\xED\x9E\xA3" < $uchr) {
                // Table lookup

                if ($uchr !== $j = isset($compatMap[$uchr]) ? $compatMap[$uchr] : (isset($decompMap[$uchr]) ? $decompMap[$uchr] : $uchr)) {
                    $uchr = $j;

                    $j = strlen($uchr);
                    $ulen = $uchr[0] < "\x80" ? 1 : $ulenMask[$uchr[0] & "\xF0"];

                    if ($ulen != $j) {
                        // Put trailing chars in $s

                        $j -= $ulen;
                        $i -= $j;

                        if (0 > $i) {
                            $s = str_repeat(' ', -$i).$s;
                            $len -= $i;
                            $i = 0;
                        }

                        while ($j--) {
                            $s[$i + $j] = $uchr[$ulen + $j];
                        }

                        $uchr = substr($uchr, 0, $ulen);
                    }
                }
                if (isset($combClass[$uchr])) {
                    // Combining chars, for sorting

                    if (!isset($c[$combClass[$uchr]])) {
                        $c[$combClass[$uchr]] = '';
                    }
                    $c[$combClass[$uchr]] .= $uchr;
                    continue;
                }
            } else {
                // Hangul chars

                $uchr = unpack('C*', $uchr);
                $j = (($uchr[1] - 224) << 12) + (($uchr[2] - 128) << 6) + $uchr[3] - 0xAC80;

                $uchr = "\xE1\x84".chr(0x80 + (int) ($j / 588))
                       ."\xE1\x85".chr(0xA1 + (int) (($j % 588) / 28));

                if ($j %= 28) {
                    $uchr .= $j < 25
                        ? ("\xE1\x86".chr(0xA7 + $j))
                        : ("\xE1\x87".chr(0x67 + $j));
                }
            }
            if ($c) {
                ksort($c);
                $result .= implode('', $c);
                $c = array();
            }

            $result .= $uchr;
        }

        if ($c) {
            ksort($c);
            $result .= implode('', $c);
        }

        return $result;
    }

    private static function getData($file)
    {
		return require dirname( __FILE__ ).'/Resources/unidata/'.$file.'.php'; // gitlost (__DIR__ is 5.3.0)
		/* gitlost
        if (file_exists($file = __DIR__.'/Resources/unidata/'.$file.'.php')) {
            return require $file;
        }

        return false;
		gitlost */
    }
} endif; // gitlost
// gitlost begin
if ( ! function_exists( 'tln_is_valid_utf8' ) ) {

	// To avoid a PCRE dependency, the Symfony Normalizer polyfill has been modified to call tln_is_valid_utf8() instead of using preg_match() directly.
	function tln_is_valid_utf8( $string ) {

		// See https://www.w3.org/International/questions/qa-forms-utf-8
		return 1 === preg_match(
			'/\A(?:
			  [\x00-\x7f]                                     # ASCII
			| [\xc2-\xdf][\x80-\xbf]                          # non-overlong 2-byte
			| \xe0[\xa0-\xbf][\x80-\xbf]                      # excluding overlongs
			| [\xe1-\xec\xee\xef][\x80-\xbf][\x80-\xbf]       # straight 3-byte
			| \xed[\x80-\x9f][\x80-\xbf]                      # excluding surrogates
			| \xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]           # planes 1-3
			| [\xf1-\xf3][\x80-\xbf][\x80-\xbf][\x80-\xbf]    # planes 4-15
			| \xf4[\x80-\x8f][\x80-\xbf][\x80-\xbf]           # plane 16
			)*+\z/x',
			$string
		);
	}

	require dirname( __FILE__ ) . '/tln_ins.php';
}
// gitlost end
