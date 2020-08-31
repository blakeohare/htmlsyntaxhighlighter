<?php
    class TextUtil {

        public static function decode_to_characters($byte_str) {
            $len = strlen($byte_str);
            $b1 = $len >= 1 ? $byte_str[0] : '';
            $b2 = $len >= 2 ? $byte_str[1] : '';
            $b3 = $len >= 3 ? $byte_str[2] : '';
            $bom3 = implode('|', array($b1, $b2, $b3));
            if ($bom3 === '239|187|191') {
                $encoding = 'utf8';
                $output = TextUtil::decode_utf8_chars_impl($byte_str, 3);
            } else {
                // TODO: add other checks
                $output = TextUtil::decode_utf8_chars_impl($byte_str, 0);
            }

            if ($output !== null) {
                return $output;
            }
            return TextUtil::decode_extended_ascii_chars_impl($byte_str);
        }

        private static function decode_utf8_chars_impl($byte_str, $start_index) {
            $output = array();
            $nums = array();
            $len = strlen($byte_str);
            for ($i = 0; $i < $len; ++$i) {
                array_push($nums, ord($byte_str[$i]));
            }
            for ($i = $start_index; $i < $len; ++$i) {
                $cc = $nums[$i];
                if ((0x80 & $cc) === 0) {
                    array_push($output, chr($cc));
                } else if ((0xE0 & $cc) === 0xC0) {
                    if ($i + 1 < $len && ($nums[$i + 1] & 0xC0) === 0x80) {
                        array_push($output, substr($byte_str, $i, 2));
                        $i += 1;
                    } else {
                        return null;
                    }
                } else if ((0xF0 & $cc) === 0xE0) {
                    if ($i + 2 < $len &&
                        ($nums[$i + 1] & 0xC0) === 0x80 &&
                        ($nums[$i + 2] & 0xC0) === 0x80) {
                        array_push($output, substr($byte_str, $i, 3));
                        $i += 2;
                    } else {
                        return null;
                    }
                } else if ((0xF8 & $cc) === 0xF0) {
                    if ($i + 3 < $len &&
                        ($nums[$i + 1] & 0xC0) === 0x80 &&
                        ($nums[$i + 2] & 0xC0) === 0x80 &&
                        ($nums[$i + 3] & 0xC0) === 0x80) {
                        array_push($output, substr($byte_str, $i, 4));
                        $i += 3;
                    } else {
                        return null;
                    }
                } else if ((0xFC & $cc) === 0xF8) {
                    if ($i + 4 < $len &&
                        ($nums[$i + 1] & 0xC0) === 0x80 &&
                        ($nums[$i + 2] & 0xC0) === 0x80 &&
                        ($nums[$i + 3] & 0xC0) === 0x80 &&
                        ($nums[$i + 4] & 0xC0) === 0x80) {
                        array_push($output, substr($byte_str, $i, 5));
                        $i += 4;
                    } else {
                        return null;
                    }
                } else if ((0xFE & $cc) === 0xFC) {
                    if ($i + 5 < $len &&
                        ($nums[$i + 1] & 0xC0) === 0x80 &&
                        ($nums[$i + 2] & 0xC0) === 0x80 &&
                        ($nums[$i + 3] & 0xC0) === 0x80 &&
                        ($nums[$i + 4] & 0xC0) === 0x80 &&
                        ($nums[$i + 5] & 0xC0) === 0x80) {
                        array_push($output, substr($byte_str, $i, 6));
                        $i += 5;
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            }
            return $output;
        }

        public static function decode_extended_ascii_chars_impl($byte_str) {
            $len = strlen($byte_str);
            $output = array();
            $lookup = null;
            for ($i = 0; $i < $len; ++$i) {
                $c = $byte_str[$i];
                $cc = ord($c);
                if ($cc < 128) {
                    if ($cc < 9) array_push($output, '?');
                    else array_push($output, $c);
                } else {
                    if ($lookup == null) {
                        $lookup = array(
                            chr(0xC3) . chr(0xA7),
                            chr(0xC3) . chr(0xBC),
                            // TODO: fill in the rest
                        );
                    }
                    $eacc = $cc - 128;

                    if ($eacc < count($lookup)) {
                        array_push($output, $lookup[$eacc]);
                    } else {
                        array_push($output, '?');
                    }
                }
            }
            return $output;
        }
    }

?>