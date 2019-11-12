<?php

    /*
        Produces a line-by-line diff of the two pieces of text.

        $diff = (new TextDiff($left_text, $right_text))->get_diff();

        The output is an array of each line of text.
        If the line is the same between the two, 2 spaces are prefixed.
        If the line is removed from the left side, a hyphen followed by a space is prefixed to the line.
        If the line is added from the right side, a plus followed by a space is prefixed to the line.

        e.g. suppose left is...
            a
            b
            d
        and right is...
            a
            c
            d

        The output would be: ["  a", "- b", "+ c", "  d"]
    */
    class TextDiff {

        private $diff;

        function __construct($left, $right) {
            $this->perform_diff($left, $right);
        }

        private function to_lines($text) {
            $text = str_replace("\r\n", "\n", $text);
            $text = str_replace("\r", "\n", $text);
            return explode("\n", $text);
        }

        function get_diff() {
            return $this->diff;
        }

        private function perform_diff($left_text, $right_text) {
            $left = $this->to_lines($left_text);
            $right = $this->to_lines($right_text);
            $left_length = count($left);
            $right_length = count($right);

            $footer = array();

            while ($left_length > 0 && $right_length > 0 && $left[$left_length - 1] === $right[$right_length - 1]) {
                array_push($footer, array_pop($left));
                array_pop($right);
                $left_length -= 1;
                $right_length -= 1;
            }

            $footer = array_reverse($footer);

            $offset = 0;
            while ($left_length - $offset > 0 && $right_length - $offset > 0 && $left[$offset] === $right[$offset]) {
                $offset += 1;
            }

            if ($offset > 0) {
                $header = array_slice($left, 0, $offset);
                $left = array_slice($left, $offset);
                $right = array_slice($right, $offset);
                $left_length = count($left);
                $right_length = count($right);
            } else {
                $header = array();
            }

            $cost = array('0_0' => 0);
            $source = array('0_0' => 'START');
            $lines = array('0_0' => null);

            for ($y = 1; $y <= $left_length; ++$y) {
                $key = '0_' . $y;
                $cost[$key] = $y;
                $source[$key] = '0_' . ($y - 1);
                $lines[$key] = '- ' . $left[$y - 1];
            }

            for ($x = 1; $x <= $right_length; ++$x) {
                $key = $x . '_0';
                $cost[$key] = $x;
                $source[$key] = ($x - 1) . '_0';
                $lines[$key] = '+ ' . $right[$y - 1];
            }

            for ($y = 1; $y <= $left_length; ++$y) {
                for ($x = 1; $x <= $right_length; ++$x) {
                    $left_line = $left[$y - 1];
                    $right_line = $right[$x - 1];
                    $key = $x . '_' . $y;
                    if ($left_line === $right_line) {
                        $parent = ($x - 1) . '_' . ($y - 1);
                        $cost[$key] = $cost[$parent];
                        $source[$key] = $parent;
                        $lines[$key] = '  ' . $left_line;
                    } else {
                        $from_up = $x . '_' . ($y - 1);
                        $from_left = ($x - 1) . '_' . $y;
                        if ($cost[$from_up] < $cost[$from_left]) {
                            $cost[$key] = $cost[$from_up] + 1;
                            $source[$key] = $from_up;
                            $lines[$key] = '- ' . $left_line;
                        } else {
                            $cost[$key] = $cost[$from_left] + 1;
                            $source[$key] = $from_left;
                            $lines[$key] = '+ ' . $right_line;
                        }
                    }
                }
            }

            $key_walker = $right_length . '_' . $left_length;
            $output = array();
            while ($source[$key_walker] !== 'START') {
                array_push($output, $lines[$key_walker]);
                $key_walker = $source[$key_walker];
            }

            for ($i = count($header) - 1; $i >= 0; --$i) {
                array_push($output, '  ' . $header[$i]);
            }

            $this->diff = array_reverse($output);

            foreach ($footer as $line) {
                array_push($this->diff, '  ' . $line);
            }
        }
    }

?>