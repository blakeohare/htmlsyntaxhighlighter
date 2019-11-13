<?php

    function highlight_syntax($code, $language, $classes = null) {
        $syntax_highlighter = new BlakesHtmlSyntaxHighlighter($language);
        return $syntax_highlighter->highlight($code, $classes);
    }

    /*
        Generates HTML snippets.

        The whole snippet is wrapped in a <div class="bhsh_code">...</div>
        Within the block there are spans with classes:
        - bhsh_string
        - bhsh_keyword
        - bhsh_constant
        - bhsh_control
        - bhsh_classname
        - bhsh_number
        - bhsh_comment
    */
    class BlakesHtmlSyntaxHighlighter {

        private $language;
        private $tab_size = 4;
        private $string_types = array();
        private $comment_types = array();
        private $token_lookup = array();
        private $built_in_class_name_lookup = array();
        private $class_name_chars = array();

        function __construct($language) {
            $this->language = strtolower(trim($language));
            switch ($language) {

                case 'json':
                    $this->tab_size = 4;
                    $this->string_types = array('"');
                    break;

                case 'python':
                    $this->tab_size = 2;
                    $this->string_types = array('"', "'");
                    $this->comment_types = array('#');
                    break;

                case 'none':
                default:
                    break;
            }
            $this->token_lookup = $this->build_lookup_table();

            for ($i = 0; $i < 26; ++$i) {
                $this->class_name_chars[chr($i + ord('a'))] = true;
                $this->class_name_chars[chr($i + ord('A'))] = true;
                if ($i < 10) $this->class_name_chars[$i . ''] = true;
            }
            $this->class_name_chars['_'] = true;
        }

        private function build_lookup_table() {
            $control_flow = array();
            $keywords = array();
            $constants = array();
            $classes = array();

            switch ($this->language) {
                case 'python':
                    $control_flow = explode(' ', 'continue elif else except finally for if import pass raise return try while with yield');
                    $keywords = explode(' ', 'and as class def from in lambda not or print');
                    $constants = explode(' ', 'False None self super True');
                    $classes = explode(' ', 'Exception');
                    break;

                case 'json':
                    $constants = explode(' ', 'null true false');
                    break;

                case 'none':
                default:
                    break;
            }

            $named = array(
                'keyword' => $keywords,
                'control' => $control_flow,
                'constant' => $constants,
                'class' => $classes,
            );

            $token_lookup = array();
            foreach ($named as $name => $tokens) {
                foreach ($tokens as $token) {
                    $token_lookup[$token] = $name;
                }
            }

            return $token_lookup;
        }

        function set_tab_size($n) {
            $this->tab_size = $n;
            return $this;
        }

        // Trims blank lines at the beginning and end and rtrims all lines. Canonicalizes \r\n into \n
        private function tidy_whitespace($code) {
            $lines = explode("\n", str_replace("\r", "\n", str_replace("\r\n", "\n", $code)));
            $enabled = false;
            $new_lines = array();
            for ($i = 0; $i < count($lines); ++$i) {
                $line = rtrim($lines[$i]);
                if (strlen($line) > 0) {
                    $enabled = true;
                }

                if ($enabled) array_push($new_lines, $line);
            }
            $lines = $new_lines;
            while (count($lines) > 0 && strlen($lines[count($lines) - 1]) === 0) {
                array_pop($lines);
            }
            $code = implode("\n", $lines);
            $tab = '';
            for ($i = 0; $i < $this->tab_size; ++$i) {
                $tab .= ' ';
            }
            $code = str_replace("\t", $tab, $code);
            return $code;
        }

        function scanner_skip_whitespace($str, $index) {
            while ($index < strlen($str)) {
                switch ($str[$index]) {
                    case ' ':
                    case "\n":
                    case "\r":
                    case "\t":
                        $index++;
                        break;
                    default:
                        return $index;
                }
            }
            return $index;
        }

        function scanner_pop_token($str, $start_index) {
            $index = $this->scanner_skip_whitespace($str, $start_index);
            $sb = array();
            $end = strlen($str);
            for ($i = $index; $i < strlen($str); ++$i) {
                $c = $str[$i];
                if ($this->class_name_chars[$c]) {
                    array_push($sb, $c);
                } else {
                    $end = $i;
                    break;
                }
            }

            $token = implode($sb);
            return array('token' => $token, 'found' => strlen($token) > 0, 'index' => $end);
        }

        function string_occurs_at($haystack, $needle, $index) {
            $nlength = strlen($needle);
            $hlength = strlen($haystack);
            if ($nlength === 0) return false;
            if ($index < 0) return false;
            if ($index + $nlength > $hlength) return false;
            if ($haystack[$index] !== $needle[0]) return false;
            if ($nlength === 1) return true;
            if (substr($haystack, $index, $nlength) === $needle) return true;
            return false;
        }

        function python_class_scan($code) {
            // TODO: gather names from the subclasses in class definitions
            $classes = array();
            $lines = explode("\n", $code);
            foreach ($lines as $raw_line) {
                $line = trim($raw_line);
                if (strlen($line) > 0) {
                    if ($line[0] === 'c' && substr($line, 0, 6) === 'class ') {
                        $token = $this->scanner_pop_token($line, 5);
                        if ($token['found']) {
                            $classes[$token['token']] = true;
                        }
                    } else {
                        $raise_index = strpos($line, 'raise ');
                        if ($raise_index !== false) {
                            $token = $this->scanner_pop_token($line, $raise_index + 5);
                            if ($token['found']) {
                                $class_name = $token['token'];
                                $index = $token['index'];
                                $index = $this->scanner_skip_whitespace($line, $index);
                                if ($this->string_occurs_at($line, '(', $index)) {
                                    $classes[$class_name] = true;
                                }
                            }
                        }
                    }
                }
            }

            $output = array();
            foreach ($classes as $class_name => $_) {
                if (strtoupper($class_name[0]) === $class_name[0]) {
                    array_push($output, $class_name);
                }
            }

            return $output;
        }

        function highlight($code, $class_names = null) {
            $class_names = $class_names == null ? array() : $class_names;
            $more_classes = array();
            switch ($this->language) {
                case 'python': $more_classes = $this->python_class_scan($code); break;
                default: break;
            }
            $class_names = array_merge($class_names, $more_classes);
            $class_name_lookup = array();
            if ($class_names !== null) {
                foreach ($class_names as $cn) $class_name_lookup[$cn] = true;
            }
            $code = $this->tidy_whitespace($code);
            $output = array('<div class="bhsh_code">', "\n");
            $token_stream = new BlakesSyntaxHighlighterTokenStream($code, $this->string_types, $this->comment_types);
            while (true) {
                $token = $token_stream->pop_token();
                if ($token === null) break;
                $type = $this->token_lookup[$token];
                if ($type) {
                    array_push($output, '<span class="bhsh_', $type, '">', htmlspecialchars($token), '</span>');
                } else if ($class_name_lookup[$token]) {
                    array_push($output, '<span class="bhsh_class">', htmlspecialchars($token), '</span>');
                } else {
                    $c = $token[0];
                    $type = null;
                    switch ($c) {
                        case '@':
                            $type = 'annotation';
                            break;
                        case '/':
                        case '#':
                            $type = 'comment';
                            break;
                        case '"':
                        case "'":
                        case "`":
                            $type = 'string';
                            break;
                        case '.':
                            $type = 'number';
                            break;
                        default:
                            if (ord($c) >= ord('0') && ord($c) <= ord('9')) {
                                $type = 'number';
                            }
                            break;
                    }
                    if ($type !== null) {
                        array_push($output,
                            '<span class="bhsh_', $type, '">',
                            str_replace("\n", "<br/>\n", str_replace(' ', '&nbsp;', htmlspecialchars($token))),
                            '</span>');
                    } else {
                        switch ($token) {
                            case ' ':
                                array_push($output, '&nbsp;');
                                break;
                            case "\n":
                                array_push($output, "<br/>\n");
                                break;
                            default:
                                array_push($output, htmlspecialchars($token));
                                break;
                        }
                    }
                }
            }

            array_push($output, "\n", '</div>', "\n");
            return implode('', $output);
        }
    }

    class BlakesSyntaxHighlighterTokenStream {
        var $index;
        var $str;
        var $length;
        var $nums;
        var $letters;
        var $string_types;
        var $comment_types;

        function __construct($str, $string_types, $comment_types) {
            $this->index = 0;
            $this->str = $str;
            $this->length = strlen($str);

            $this->nums = array();
            $this->letters = array();
            for ($i = 0; $i < 10; ++$i) $this->letters['' . $i] = true;
            $letters = 'abcdefghijklmnopqrstuvwxyz';
            for ($i = 0; $i < 26; ++$i) {
                $this->letters[$letters[$i]] = true;
                $this->letters[strtoupper($letters[$i])] = true;
            }

            $this->string_types = array();
            $this->comment_types = array();
            foreach ($string_types as $st) {
                $this->string_types[$st] = true;
            }
            foreach ($comment_types as $ct) {
                $this->comment_types[$ct] = true;
            }
        }

        function has_more() {
            return $this->index < $this->length;
        }

        function pop_token() {
            if ($this->index >= $this->length) return null;
            $c = $this->str[$this->index];

            switch ($c) {
                case ' ':
                case "\n":
                    $this->index++;
                    return $c;
            }
            $c2 = $this->index + 1 < $this->length ? $this->str[$this->index + 1] : '';
            if ($c === '_' || $this->letters[$c]) return $this->pop_entity(false, false);
            if ($this->nums[$c]) return $this->pop_entity(true, false);
            if ($c === '.' && $this->nums[$c2]) return $this->pop_entity(true, false);
            if ($c === '@' && $this->letters[$c2]) return $this->pop_entity(false, true);

            switch ($c) {
                case '#':
                    if ($this->comment_types['#']) return $this->pop_till("\n", false);
                    break;

                case '/':
                    if ($this->comment_types['//'] && $c2 === '/') return $this->pop_till("\n", false);
                    if ($this->comment_types['/*'] && $c2 === '*') {
                        $this->index += 2; // /*/ should not be allowed
                        return '/*' . $this->pop_till('*/', true);
                    }
                    break;

                case '"':
                case "'":
                case "`":
                    if ($this->string_types[$c]) return $this->pop_string($c);
                    break;
            }

            $c = mb_substr($this->str, $this->index, 1);
            $this->index += strlen($c);
            return $c;
        }

        function pop_till($terminator, $including_terminator) {
            $start = $this->index;
            $end = $this->length;
            $term_char = $terminator[0];
            $term_len = strlen($terminator);
            for ($i = $start; $i < $this->length; ++$i) {
                if ($this->str[$i] === $term_char && substr($this->str, $i, $term_len)) {
                    $end = $i + ($including_terminator ? $term_len : 0);
                    break;
                }
            }
            $this->index = $end;
            return substr($this->str, $start, $end - $start);
        }

        function pop_string($terminator) {
            $sb = array($this->str[$this->index++]);
            $keep_going = true;
            while ($keep_going && $this->index < $this->length) {
                $c = $this->str[$this->index];
                array_push($sb, $c);
                if ($c === $terminator) {
                    $keep_going = false;
                    $this->index++;
                } else if ($c === "\\" && $this->index + 1 < $this->length) {
                    array_push($sb, $this->str[$this->index + 1]);
                    $this->index += 2;
                } else {
                    $this->index++;
                }
            }
            return implode('', $sb);
        }

        function pop_entity($is_number, $is_annotation) {
            $sb = array();
            $c = $this->str[$this->index];
            $period_found = false;
            $at_found = false;
            while ($this->index < $this->length) {
                $c = $this->str[$this->index];
                if ($this->letters[$c] ||
                    $this->nums[$c] ||
                    $c === '_' ||
                    ($is_annotation && $c === '@' && !$at_found) ||
                    ($is_number && $c === '.' && !$period_found) ||
                    ($is_annotation && $c === '.')) {
                    array_push($sb, $c);
                    if ($c === '.')  $period_found = true;
                } else {
                    break;
                }
                $this->index++;
            }

            return implode('', $sb);
        }
    }
?>