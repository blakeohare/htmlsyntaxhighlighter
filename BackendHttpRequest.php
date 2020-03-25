<?php

    /*
        Helper class to wrap the functionality of the built-in PHP curl library.

        Usage:
            $response = (new BackendHttpRequest())
                ->set_url('http://purple0.com')
                ->set_method('POST')
                ->set_content_json(array('data' => true))
                ->set_user_agent("MyServer/1.0")
                ->send();

        Response:
        array(
            'method' => (string) original request method,
            'url' => (string) original request URL,
            'has_error' => (bool) true if error,
            'sc' => (int) status code,
            'error' => (string) the error,
            'headers' => array(
                'name1' => value1,
                'name2' => value2,
                ...
            ),
            'content_type' => (string),
            'is_json' => (bool) true, if there's a JSON result,
            'json' => array(
                (data, if present)
            ),
            'content' => (string) response body,
        );

    */
    class BackendHttpRequest {
        var $url;
        var $method;
        var $content = null;
        var $content_path = null;
        var $content_type = null;
        var $header_names = array();
        var $header_values = array();
        var $user_agent = "PHP Utils BackendHttpRequest (https://github.com/blakeohare/phputils)";

        function set_url($url) {
            $this->url = $url;
            return $this;
        }

        function set_method($method) {
            $this->method = strtoupper($method);
            return $this;
        }

        function set_content_string($content, $content_type) {
            $this->content_type = $content_type;
            $this->content = $content;
            $this->content_path = null;
            return $this;
        }

        function set_content_file($file_path, $content_type) {
            return $this->set_content_string(file_get_contents($file_path), $content_type);
        }

        function set_content_json($data) {
            return $this->set_content_string(
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                "application/json");
        }

        function set_header($name, $value) {
            switch (strtolower($name)) {
                case 'content-type': $this->content_type = $value; return $this;
                case 'user-agent': return $this->set_user_agent($value);
                default: break;
            }
            array_push($this->header_names, $name);
            array_push($this->header_values, $value);
            return $this;
        }

        function set_user_agent($value) {
            $this->user_agent = trim($value);
            return $this;
        }

        function send() {

            if ($this->method == 'GET' && $this->content_type !== null) {
                throw new Exception("Sent a request that had content, but was GET");
            }

            $c = curl_init();

            curl_setopt($c, CURLOPT_URL, $this->url);

            if ($this->method == 'POST') {
                if ($this->content_path !== null) {
                    // TODO: this is actually disabled right now. Setting a file path immediately loads its contents as a string and uses the other codepath.
                    $args = array();
                    $args['file'] = new CurlFile($this->content_path, $this->content_type);
                    curl_setopt($c, CURLOPT_POSTFIELDS, $args);
                } else {
                    curl_setopt($c, CURLOPT_POSTFIELDS, $this->content);
                }
                curl_setopt($c, CURLOPT_POST, 1);
            } else {
                throw new Exception("Not implemented.");
            }

            curl_setopt($c, CURLOPT_USERAGENT, $this->user_agent);

            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c, CURLOPT_VERBOSE, 1);
            curl_setopt($c, CURLOPT_HEADER, 1);

            $header_count = count($this->header_names);
            if ($header_count > 0 || $this->content_type !== null) {
                $zipped_headers = array();

                if ($this->content_type !== null) {
                    array_push($zipped_headers, 'Content-Type: ' . $this->content_type);
                }

                for ($i = 0; $i < $header_count; ++$i) {
                    array_push($zipped_headers, $this->header_names[$i] . ': ' . $this->header_values[$i]);
                }

                curl_setopt($c, CURLOPT_HTTPHEADER, $zipped_headers);
            }

            curl_setopt($c, CURLINFO_HEADER_OUT, true);

            $result = curl_exec($c);

            $error = curl_error($c);
            $headers = array();
            $content = null;
            $sc = 0;
            $content_type = null;
            $has_error = strlen($error) > 0;

            $info = curl_getinfo($c);

            if (!$has_error) {
                $header_size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
                $sc = curl_getinfo($c, CURLINFO_HTTP_CODE);

                $raw_header = substr($result, 0, $header_size);
                $content = substr($result, $header_size);

                $raw_headers = explode("\n", $raw_header);
                for ($i = 1; $i < count($raw_headers); ++$i) {
                    $row = trim($raw_headers[$i]);
                    $colon = strpos($row, ':');
                    if ($colon !== false) {
                        $key = strtolower(trim(substr($row, 0, $colon)));
                        $value = trim(substr($row, $colon + 1));
                        $headers[$key] = $value;
                        if ($key == "content-type") {
                            $content_type = $value;
                        }
                    }
                }
            }

            curl_close($c);

            $json = null;
            if ($content_type == "application/json") {
                $trimmed_content = trim($content);
                if (strlen($trimmed_content) > 2 && $trimmed_content[0] == '{' && $trimmed_content[strlen($trimmed_content) - 1] == '}') {
                    $json = json_decode($trimmed_content, true);
                }
            }

            return array(
                'method' => $this->method,
                'url' => $this->url,
                'has_error' => $has_error,
                'sc' => $sc,
                'error' => $error,
                'headers' => $headers,
                'content_type' => $content_type,
                'json' => $json,
                'is_json' => $json !== null,
                'content' => $content);
        }
    }

?>