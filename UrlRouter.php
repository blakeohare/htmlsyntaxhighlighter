<?php

    class UrlRouter {

        private $routes = array();

        /*
            Mapping instructions is a large text string.
            Each line consists of 2 or 3 pieces separated by a '|' character.

            A line looks something like this:
            /forum/{str:category_name}/{dec:thread_id}/reply | ForumReply | LOGGED_IN_ONLY

            The first piece is the URL path of the routed action
            Special variables can be designated inside {} and are a type followed
            by a colon, followed by a name. The types that are supported are:
                str: a string
                dec: a decimal number
                page: a decimal number that is prefixed with the word "page".
            A special variable must be a full "directory" portion of a path.
            For example, "/blog/{str:article_lookup}" is valid but "/results/page{dec:pagenum}" is not.
            For the latter invalid case, use the page variable type instead.

            The second portion is the instruction. When the pattern matches, this will be returned in the
            response and should describe some sort of command that should run.

            The third portion is a list of filter flags and is optional. Flags are separated by commas.
            When you override the UrlRouter class, you can implement the function apply_filter_flags.
            This function takes in a request context and a list of the flags. When you call
            the $url_router->route($path, $request_context) function, the $request_context (which can
            be any value you want) will be passed to apply_filter_flags. If you return a false-y value,
            the routing entry will be skipped.

            All lines are checked sequentially and the first one that applies will be the result.

            The result that is returned is the following array:
            array(
                'vars' => array(
                    'foo' => 42,
                    'bar' => 'baz',
                    ... // These are all the special variables in the path
                ),
                'action' => "Foo" // This is the instruction in the 2nd portion as a trimmed string.
            )

            Blank or whitespace lines are okay
            Comments can be added on their own line and begin with a '#' character.
        */
        function __construct($mapping_instructions) {

            // valid non-comment non-blank lines only
            $lines = array();
            foreach (explode("\n", $mapping_instructions) as $line) {
                $line = trim($line);
                if ($line !== '' && $line[0] !== '#') {
                    $parts = explode('|', $line);
                    if (count($parts) >= 2) {
                        array_push($lines, $parts);
                    }
                }
            }

            foreach ($lines as $parts) {
                $path_raw = trim($parts[0]);
                $instruction_raw = trim($parts[1]);
                $flags_raw = count($parts) > 2 ? trim($parts[2]) : '';

                $pattern = array();
                foreach (explode('/', $path_raw) as $pattern_part) {
                    if ($pattern_part !== '') array_push($pattern, $pattern_part);
                }

                $flags = array();
                foreach (explode(',', $flags_raw) as $flag) {
                    $flag = trim($flag);
                    if ($flag !== '') array_push($flags, $flag);
                }

                array_push($this->routes, array(
                    'pattern' => $pattern,
                    'action' => $instruction_raw,
                    'flags' => $flags
                ));
            }
        }

        function find_match($path, $request_context) {
            if ($path === '') return null;

            // trim off leading slash
            if ($path[0] === '/') $path = substr($path, 1);

            // trim off trailing slash
            if ($path[strlen($path) - 1] === '/') $path = substr($path, 0, strlen($path) - 1);

            $url_parts = $path === '' ? array() : explode('/', $path);

            foreach ($this->routes as $routing_instruction) {
                $vars = $this->get_vars_if_match($url_parts, $routing_instruction['pattern']);
                if ($vars !== null) {
                    if ($this->apply_filter_flags($routing_instruction['flags'], $request_context)) {
                        return array(
                            'vars' => $vars,
                            'action' => $routing_instruction['action'],
                        );
                    }
                }
            }
            return null;

        }

        function get_vars_if_match($parts, $pattern) {
            $parts_length = count($parts);
            $pattern_length = count($pattern);
            $pattern_length_check = $pattern_length;
            $has_asterisk = $pattern[$pattern_length - 1] == '*';

            if ($parts_length < $pattern_length) {
                if ($has_asterisk && $parts_length == $pattern_length - 1) {
                    // This might be fine
                } else {
                    // the url is too short to match
                    return null;
                }
            }

            if ($parts_length > $pattern_length && !$has_asterisk) {
                // the pattern is too short to match
                return null;
            }

            if ($has_asterisk) {
                $pattern_length_check--;
            }

            $vars = array();

            // check the pattern against the provided URL.
            // the length is sufficient and if the provided URL is longer, that's okay, because an asterisk is already verified in that case.
            for ($i = 0; $i < $pattern_length_check; ++$i) {
                $url_part = $parts[$i];
                $pattern_part = $pattern[$i];

                if ($url_part == $pattern_part) {
                    // This is fine
                } else if ($pattern_part[0] == '{' && $pattern_part[strlen($pattern_part) - 1] == '}') {
                    // This is a variable such as {str:name} or {dec:id}
                    $pattern_var = explode(':', substr($pattern_part, 1, strlen($pattern_part) - 2));
                    if (count($pattern_var) != 2) return null; // invalid format in pattern
                    $type = trim($pattern_var[0]);
                    $name = trim($pattern_var[1]);
                    $value = $parts[$i];
                    switch ($type) {
                        case 'str':
                            $vars[$name] = $url_part;
                            break;

                        case 'dec':
                            $intval = intval($url_part);
                            // this will treat non-decimal values as not found. If you want to do something else, pass as a string and parse yourself
                            if ($intval . '' != $url_part) return null;
                            $vars[$name] = $intval;
                            break;

                        case 'page':
                            if (substr($url_part, 0, 4) !== 'page') return null;
                            $page_num = max(0, intval(substr($url_part, 4)));
                            if ('page' . $page_num !== $url_part) return null; // not an integer
                            $vars[$name] = $page_num;
                            break;

                        default:
                            return null;
                    }
                } else {
                    // a mismatch
                    return null;
                }
            }

            return $vars;
        }

        function apply_filter_flags($flags, $request_context) {
            // override me!
            return true;
        }
    }

?>