<?php
    class TextUtil {

        private static $_ascii_lookup = null;

        private static function _initialize_ascii_lookup() {
            $extended_ascii = array(
                'k128' => '226,130,172',
                'k129' => '',
                'k130' => '226,128,154',
                'k131' => '198,146',
                'k132' => '226,128,158',
                'k133' => '226,128,166',
                'k134' => '226,128,160',
                'k135' => '226,128,161',
                'k136' => '203,134',
                'k137' => '226,128,176',
                'k138' => '197,160',
                'k139' => '226,128,185',
                'k140' => '197,146',
                'k141' => '',
                'k142' => '197,189',
                'k143' => '',
                'k144' => '',
                'k145' => '226,128,152',
                'k146' => '226,128,153',
                'k147' => '226,128,156',
                'k148' => '226,128,157',
                'k149' => '226,128,162',
                'k150' => '226,128,147',
                'k151' => '226,128,148',
                'k152' => '203,156',
                'k153' => '226,132,162',
                'k154' => '197,161',
                'k155' => '226,128,186',
                'k156' => '197,147',
                'k157' => '',
                'k158' => '197,190',
                'k159' => '197,184',
                'k160' => '194,160',
                'k161' => '194,161',
                'k162' => '194,162',
                'k163' => '194,163',
                'k164' => '194,164',
                'k165' => '194,165',
                'k166' => '194,166',
                'k167' => '194,167',
                'k168' => '194,168',
                'k169' => '194,169',
                'k170' => '194,170',
                'k171' => '194,171',
                'k172' => '194,172',
                'k173' => '',
                'k174' => '194,174',
                'k175' => '194,175',
                'k176' => '194,176',
                'k177' => '194,177',
                'k178' => '194,178',
                'k179' => '194,179',
                'k180' => '194,180',
                'k181' => '194,181',
                'k182' => '194,182',
                'k183' => '194,183',
                'k184' => '194,184',
                'k185' => '194,185',
                'k186' => '194,186',
                'k187' => '194,187',
                'k188' => '194,188',
                'k189' => '194,189',
                'k190' => '194,190',
                'k191' => '194,191',
                'k192' => '195,128',
                'k193' => '195,129',
                'k194' => '195,130',
                'k195' => '195,131',
                'k196' => '195,132',
                'k197' => '195,133',
                'k198' => '195,134',
                'k199' => '195,135',
                'k200' => '195,136',
                'k201' => '195,137',
                'k202' => '195,138',
                'k203' => '195,139',
                'k204' => '195,140',
                'k205' => '195,141',
                'k206' => '195,142',
                'k207' => '195,143',
                'k208' => '195,144',
                'k209' => '195,145',
                'k210' => '195,146',
                'k211' => '195,147',
                'k212' => '195,148',
                'k213' => '195,149',
                'k214' => '195,150',
                'k215' => '195,151',
                'k216' => '195,152',
                'k217' => '195,153',
                'k218' => '195,154',
                'k219' => '195,155',
                'k220' => '195,156',
                'k221' => '195,157',
                'k222' => '195,158',
                'k223' => '195,159',
                'k224' => '195,160',
                'k225' => '195,161',
                'k226' => '195,162',
                'k227' => '195,163',
                'k228' => '195,164',
                'k229' => '195,165',
                'k230' => '195,166',
                'k231' => '195,167',
                'k232' => '195,168',
                'k233' => '195,169',
                'k234' => '195,170',
                'k235' => '195,171',
                'k236' => '195,172',
                'k237' => '195,173',
                'k238' => '195,174',
                'k239' => '195,175',
                'k240' => '195,176',
                'k241' => '195,177',
                'k242' => '195,178',
                'k243' => '195,179',
                'k244' => '195,180',
                'k245' => '195,181',
                'k246' => '195,182',
                'k247' => '195,183',
                'k248' => '195,184',
                'k249' => '195,185',
                'k250' => '195,186',
                'k251' => '195,187',
                'k252' => '195,188',
                'k253' => '195,189',
                'k254' => '195,190',
                'k255' => '195,191',
            );

            $lookup = array();
            for ($i = 0; $i < 256; ++$i) {
                if ($i >= 128) {
                    $t = $extended_ascii['k' . $i];
                    if ($t === '') {
                        array_push($lookup, null);
                    } else {
                        $t = explode(',', $t);
                        $c = '';
                        foreach ($t as $code) {
                            $c .= chr(intval($code));
                        }
                        array_push($lookup, $c);
                    }
                } else if (($i < 127 && $i >= 32) || $i === 9 || $i === 10 || $i === 13) {
                    array_push($lookup, chr($i));
                } else {
                    array_push($lookup, null);
                }
            }

            TextUtil::$_ascii_lookup = $lookup;
        }

        public static function to_utf8_chars($str) {

            $bytes = array();
            $len = strlen($str);
            $is_super_basic = true;

            // BOM's are part of UTF but are not valid characters for a character sequence and should
            // be kindly acknowledged for their informative contribution and quickly lopped off.
            // TODO: if you do happen to detect an alternate BOM, decode the string as such.
            $start = 0;
            if ($len >= 3 && ord($str[0]) === 239 && ord($str[1]) === 187 && ord($str[2]) === 191) {
                $start = 3;
            }

            for ($i = $start; $i < $len; ++$i) {
                $c = ord($str[$i]);
                if ($is_super_basic) {
                    if ($c > 127) $is_super_basic = false;
                    else if ($c < 32 && ($c !== 9 && $c !== 10 && $c !== 13)) $is_super_basic = false;
                }
                array_push($bytes, $c);
            }

            if ($is_super_basic) { // everything is in the lower ascii range without control characters
                $output = array();
                for ($i = $start; $i < $len; ++$i) {
                    array_push($output, $str[$i]);
                }
                return $output;
            }

            // This next part builds up an array of characters, however the elements in this
            // list exist as pairs. The odd number index elements are a single letter that describe
            // the next even number index element, which is a string of the character.
            // C --> Next element is a valid 1 to 4 byte UTF-8 character as a single string
            // A --> Next element is an arbitrary byte as a string of unknown purpose. First bit is 1
            // X --> Same as above, but the first bit is 0 and so it was discarded and the next string is empty

            $chars = array();
            $i = 0;
            while ($i < $len) {
                $c = $bytes[$i];

                if ($c < 128) {
                    if ($c < 32) {
                        switch ($c) {
                            case 9: // tab
                            case 10: // \n
                            case 13: // \r
                                $ok = true;
                                break;
                            default:
                                $ok = false;
                                break;
                        }
                    } else {
                        $ok = $c !== 127;
                    }

                    if ($ok) {
                        array_push($chars, 'C', chr($c));
                    } else {
                        array_push($chars, 'X', '');
                    }
                    ++$i;
                } else {
                    $invalid_char = false;
                    if (($c & 0xE0) === 0xC0) { // 110xxxxx
                        if ($i + 1 < $len &&
                            ($bytes[$i + 1] & 0xC0) === 0x80) { // 10xxxxxx
                            array_push($chars, 'C', substr($str, $i, 2));
                            $i += 2;
                        } else {
                            $invalid_char = true;
                        }
                    } else if (($c & 0xF0) === 0xE0) { // 1110xxxxx
                        if ($i + 2 < $len &&
                            ($bytes[$i + 1] & 0xC0) === 0x80 && // 10xxxxxx
                            ($bytes[$i + 2] & 0xC0) === 0x80) { // 10xxxxxx
                            array_push($chars, 'C', substr($str, $i, 3));
                            $i += 3;
                        } else {
                            $invalid_char = true;
                        }
                    } else if (($c & 0xF8) === 0xF0) { // 11110xxx
                        if ($i + 3 < $len &&
                            ($bytes[$i + 1] & 0xC0) === 0x80 && // 10xxxxxx
                            ($bytes[$i + 2] & 0xC0) === 0x80 && // 10xxxxxx
                            ($bytes[$i + 3] & 0xC0) === 0x80) { // 10xxxxxx
                            array_push($chars, 'C', substr($str, $i, 4));
                            $i += 4;
                        } else {
                            $invalid_char = true;
                        }
                    } else {
                        $invalid_char = true;
                    }

                    if ($invalid_char) {
                        array_push($chars, 'A', $str[$i]);
                        ++$i;
                    }
                }
            }

            // Finalize a list of characters. UTF-8 compatible characters should be copied to this
            // output list as-is. If there are pairs of arbitrary ASCII characters, attempt to
            // decode them as EUC-KR. If it is valid, get the valid UTF-8 encoding for that character
            // and skip the next character. Otherwise, all other arbitrary ASCII characters should be
            // converted into their closest UTF-8 equivalent, according to ISO-8859-1. If there is no
            // valid character for that byte, then use a '?' character.
            $output = array();

            $len = count($chars);
            for ($i = 0; $i < $len; $i += 2) {
                $t = $chars[$i];

                switch ($chars[$i]) {
                    case 'A':
                        $c = $chars[$i + 1];
                        $found = false;
                        // TODO: consider tweaking this EUC-KR thing, maybe filtering to a specific range of emoji chars.
                        // For now this seems to cause more false positives.
                        /*
                        if ($i + 2 < $len && $chars[$i + 2] === 'A') {
                            $c2 = $chars[$i + 1] . $chars[$i + 3];
                            // Why EUC-KR? It's pretty common to encounter when people copy and paste
                            // special emoji-like characters.
                            if (mb_check_encoding($c2, 'EUC-KR')) {
                                $c2 = mb_convert_encoding($c2, 'UTF-8', 'EUC-KR');
                                array_push($output, $c2);
                                $i += 2;
                                break;
                            }
                        }
                        //*/
                        if (TextUtil::$_ascii_lookup === null) {
                            TextUtil::_initialize_ascii_lookup();
                        }
                        $ext_ascii_char = TextUtil::$_ascii_lookup[ord($chars[$i + 1])];
                        if ($ext_ascii_char === null) $ext_ascii_char = '?';

                        array_push($output, $ext_ascii_char);
                        break;

                    case 'X': // some sort of invalid control character.
                        // skip it!
                        break;

                    case 'C': // a valid UTF-8 encoded-character (1 to 4 bytes)
                        array_push($output, $chars[$i + 1]);
                        break;
                }
            }
            return $output;
        }

        public static function to_utf8($unknown_string) {
            $chars = TextUtil::to_utf8_chars($unknown_string);
            return implode('', $chars);
        }

        public static function reasonable_string($str) {
            return TextUtil::canonicalize_string($str, array('TRIM', 'SOME_SPACES', 'LINE_ENDINGS', 'RTRIM_LINES'));
        }

        private static $_whitespace_chars_utf8 = null;

        // Converts a string into a bonafied UTF-8 string. Also does string trimming and line canonicalization.
        // Whitespace trimming handles atypical whitespace characters. $actions is a list of actions to perform.
        // Types of actions:
        // * LTRIM / RTRIM / TRIM - trims whitespace including special unicode whitespace (ZWS & NBSP)
        // * SPACES - converts all zero width spaces and non-breaking spaces into empty strings or regular spaces.
        // * SOME_SPACES - consolidate consecutive zero-width spaces, remove them if they're adjacent to whitespace
        //       and convert non-breaking spaces to regular spaces.
        // * LINE_ENDING - converts all line ending schemes (\r\n and \r) into just \n's
        // * RTRIM_LINES - trims the whitespace from the end of lines
        public static function canonicalize_string($str, $actions) {

            $nbsp = chr(194) . chr(160); // non-breaking space
            $zwsp = chr(226) . chr(128) . chr(139); // zero-width space
            $fwsp = chr(227) . chr(128) . chr(128); // full-width space

            if (TextUtil::$_whitespace_chars_utf8 === null) {
                TextUtil::$_whitespace_chars_utf8 = array();
                foreach (array(' ', "\r", "\n", "\t", $nbsp, $zwsp, $fwsp) as $ws) {
                    TextUtil::$_whitespace_chars_utf8[$ws] = true;
                }
            }

            $do_ltrim = false;
            $do_rtrim = false;
            $do_spaces = false;
            $do_some_spaces = false;
            $do_line_endings = false;
            $do_rtrim_lines = false;
            foreach ($actions as $action) {
                switch (strtoupper($action)) {
                    case 'LTRIM': $do_ltrim = true; break;
                    case 'RTRIM': $do_rtrim = true; break;
                    case 'TRIM': $do_ltrim = true; $do_rtrim = true; break;
                    case 'SPACES': $do_spaces = true; break;
                    case 'SOME_SPACES': $do_some_spaces = true; break;
                    case 'RTRIM_LINES': $do_rtrim_lines = true; break;

                    case 'LINE_ENDING':
                    case 'LINE_ENDINGS':
                        $do_line_endings = true; break;

                    default: throw new Exception("Unknown string canonicalizer action: " . $action);
                }
            }

            if ($str === '') return $str;

            $chars = TextUtil::to_utf8_chars($str);

            $start = 0;
            $end = count($chars);
            if ($do_ltrim) {
                while ($start < $end && TextUtil::$_whitespace_chars_utf8[$chars[$start]]) {
                    $chars[$start] = '';
                    $start++;
                }
            }
            if ($do_rtrim) {
                while ($end > $start && TextUtil::$_whitespace_chars_utf8[$chars[$end - 1]]) {
                    $end--;
                    $chars[$end] = '';
                }
            }

            if ($do_spaces) {
                for ($i = $start; $i < $end; ++$i) {
                    $c = $chars[$i];
                    if ($c === $zwsp) $chars[$i] = '';
                    else if ($c === $nbsp) $chars[$i] = ' ';
                }
            }

            if ($do_line_endings) {
                for ($i = $start; $i < $end; ++$i) {
                    if ($chars[$i] === "\r") {
                        if ($i + 1 < $end && $chars[$i + 1] == "\n") {
                            $chars[$i] = '';
                        } else {
                            $chars[$i] = "\n";
                        }
                    }
                }
            }

            if ($do_rtrim_lines) {
                $last_line_end = $start - 1;
                for ($i = $start; $i < $end; ++$i) {
                    $c = $chars[$i];
                    if ($c === "\n" || $c === "\r") {
                        for ($j = $i - 1; $j > $last_line_end; $j--) {
                            $p = $chars[$j];
                            if (TextUtil::$_whitespace_chars_utf8[$p] || $p === '') {
                                $chars[$j] = '';
                            } else {
                                $last_line_end = $i;
                                break;
                            }
                        }
                    }
                }

                for ($i = $end - 1; $i > $last_line_end; $i--) {
                    $p = $chars[$i];
                    if (TextUtil::$_whitespace_chars_utf8[$p] || $p === '') {
                        $chars[$i] = '';
                    } else {
                        break;
                    }
                }
            }

            if ($do_some_spaces) {
                $chars2 = array();
                $prev = '';
                for ($i = $start; $i < $end; ++$i) {
                    $c = $chars[$i];
                    if ($c !== '') {
                        if ($c === $zwsp && $p === $zwsp) {
                            // skip it!
                        } else {
                            array_push($chars2, $c === $nbsp ? ' ' : $c);
                        }
                    }
                    $prev = $c;
                }
                $chars = $chars2; // all non-breaking spaces have been swapped and zero-width spaces consolidated, and empty slots removed
                $start = 0;
                $end = count($chars);
                while ($start < $end && $chars[$start] === $zwsp) {
                    $chars[$start] = '';
                    $start++;
                }
                while ($start < $end && $chars[$end - 1] === $zwsp) {
                    $end--;
                    $chars[$end] = '';
                }
                for ($i = $start + 1; $i < $end - 1; ++$i) {
                    $c = $chars[$i];
                    if ($c === $zwsp) {
                        $prev = $chars[$i - 1];
                        $next = $chars[$i + 1];
                        if (TextUtil::$_whitespace_chars_utf8[$prev] ||
                            TextUtil::$_whitespace_chars_utf8[$next]) {
                            $chars[$i] = '';
                        }
                    }
                }
            }

            return implode('', $chars);
        }
    }

?>