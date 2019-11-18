<?php

	class RequestContext {

		var $path;
		var $url_parts;
		var $ip;
		var $method;
		var $content;
		var $content_type;
		var $form;
		var $query;
		var $json;

		function __construct() {
			$this->query = array();
			foreach ($_GET as $key => $value) {
				$this->query[$key] = $value;
			}
			$this->ip = $_SERVER['REMOTE_ADDR'];
			$this->content = null;
			$this->method = strtoupper(trim($_SERVER['REQUEST_METHOD']));
			if ($this->method != 'GET') {
				$this->content = file_get_contents("php://input");
			}

			if ($this->method == 'POST') {
				$this->form = array();
				foreach ($_POST as $post_key => $post_value) {
					$this->form[$post_key] = $post_value;
				}
			}

			$url_parts = array();
			$this->path = explode('?', $_SERVER['REQUEST_URI'])[0];
			$url_parts = explode('/', $this->path);
			$start = 0;
			$end = count($url_parts) - 1;
			if ($url_parts[0] === '') $start++;
			if ($url_parts[count($url_parts) - 1] === '') $end--;
			$this->url_parts = array();
			for ($i = $start; $i <= $end; ++$i) {
				array_push($this->url_parts, $url_parts[$i]);
			}

			$this->content_type = strtolower(trim(explode(';', $_SERVER["CONTENT_TYPE"])[0]));

			$this->json = $this->content_type === 'application/json'
				? json_decode($this->content, true)
				: null;
		}
	}

?>