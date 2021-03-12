<?php

    $_PHP_UTILS_DB = null;

    class DBResult {
        var $result;
        var $index = 0;
        var $size_cache;

        function DBResult($result) {
            $this->result = $result;
            $this->size_cache = $result->num_rows;
        }

        function size() {
            return $this->size_cache;
        }

        function has_more() {
            return $this->index < $this->size_cache;
        }

        function has_any() {
            return $this->size_cache > 0;
        }

        function next() {
            $this->index++;
            $result = $this->result;
            return $result->fetch_assoc();
        }

        function as_rows() {
            $output = array();
            while ($this->has_more()) {
                array_push($output, $this->next());
            }
            return $output;
        }

        function as_row() {
            if ($this->has_any()) {
                return $this->next();
            }
            return null;
        }

        function as_item() {
            $row = $this->as_row();
            if ($row !== null) {
                foreach ($row as $_ => $value) {
                    return $value;
                }
            }
            return null;
        }

        function as_int_item($default_value = null) {
            $v = $this->as_item();
            if ($v === null) return $default_value;
            return intval($v);
        }

        function as_flat_list($first_column_only = true) {
            $output = array();
            while ($this->has_more()) {
                $row = $this->next();
                foreach ($row as $_ => $value) {
                    array_push($output, $value);
                    if ($first_column_only) break;
                }
            }
            return $output;
        }

        function as_flat_int_list($first_column_only = true) {
            $nums = $this->as_flat_list($first_column_only);
            for ($i = 0; $i < count($nums); ++$i) {
                $nums[$i] = intval($nums[$i]);
            }
            return $nums;
        }
    }

    class Database {
        var $connection = null;
        var $input = array();
        var $counter = 0;
        var $errors = array();
        var $queries = array();
        private $creds = null;

        function init($host, $user, $password, $dbname) {
            global $_PHP_UTILS_DB;
            $_PHP_UTILS_DB = $this;

            $this->creds = array(
                'host' => $host,
                'user' => $user,
                'password' => $password,
                'dbname' => $dbname,
            );
        }

        // TODO: make this private
        function get_connection() {

            if ($this->connection !== null) return $this->connection;

            if ($this->creds === null) {
                throw new Exception("Cannot use database without initializing credentials.");
            }

            $creds = $this->creds;
            $this->creds = null;

            $this->connection = new mysqli($creds['host'], $creds['user'], $creds['password'], $creds['dbname']);
            if ($this->connection->connect_errno) {
                header('HTTP/1.1 500 Internal Server Error', true, 500);
                echo '<html><body><div>The site is down. Take a tea break and then come back.</div></body></html>';
                exit;
            }
        }

        function get_time() {
            $unix_time = microtime(true);
            return $unix_time;
        }

        function echo_synopsis() {
            $total = 0;
            foreach ($this->queries as $query) {
                $time = $query['after'] - $query['before'];
                $query['time'] = $time;
                $total += $time;
            }
            echo '<div style="color:#888;" id="sql_debug">';
            echo '<div>';
            echo count($this->queries) . ' queries.<br />';
            echo "took a total of $total seconds.";
            echo '</div>';
            echo '<table border="0" cellspacing="0" cellpadding="4">';
            echo '<tr><td>SQL</td><td>Time</td><td>%</td></tr>';
            $alternator = false;
            foreach ($this->queries as $query) {
                $color = $alternator ? 'ddd' : 'fff';
                if ($query['time'] > .01) {
                    $color = 'f00';
                }
                $p = $query['time'] / $total;
                $p = intval(1000 * $p) / 10;
                echo '<tr style="background-color:#' . $color . '">';
                echo '<td style="font-size:9px;">' . nl2br(htmlspecialchars(trim($query['sql']))) . '</td>';
                echo '<td>' . $query['time'] . '</td>';
                echo '<td>' . $p . '%</td>';
                echo '<td>' . htmlspecialchars($query['error']) . '</td>';
                echo '</tr>';
                $alternator = !$alternator;
            }

            echo '</table>';
            echo '</div>';
        }

        function query($q, $hide_errors = false) {
            $this->get_connection();
            $before = microtime(true);

            $now = $this->get_time();
            $result = $this->connection->query($q);
            $elapsed = $this->get_time() - $now;
            $after = microtime(true);

            array_push($this->input, $q);
            $error = '';

            if ($this->connection->errno != 0 && !$hide_errors) {
                $error = nl2br(htmlspecialchars($this->connection->error));
                $query = htmlspecialchars($q);
                $query = str_replace("\t", "    ", $query);
                $query = str_replace(" ", "&nbsp;", $query);
                $query = nl2br($query);

                echo '<div style="text-align:left;padding:12px;font-family:&quot;Lucida Console&quot;; background-color:#fee; border:1px solid #f00; color:#000;">';
                echo '<div style="font-weight:bold;font-style:italic;">' . $error . '</div>';
                echo '<div style="padding-top:12px; font-size:13px; color:#555; padding-bottom:6px;">' . $query . '</div>';
                echo '<div style="border-top:1px solid #000;">';
                echo '<pre>';
                debug_print_backtrace();
                echo '</pre>';
                echo '</div>';
                echo '</div>';
            }

            $this->counter++;

            array_push($this->queries, array(
                'sql' => $q,
                'time' => $elapsed,
                'before' => $before,
                'after' => $after,
                'error' => $error));

            return new DBResult($result);
        }

        function affected_rows() {
            $this->get_connection();
            return $this->connection->affected_rows;
        }

        function ensure_utf8_string($str) {
            $chars = TextUtil::to_utf8_chars('' . $str);
            if (count($chars) > 65535) {
                $chars = array_slice($chars, 0, 65535);
            }
            return implode('', $chars);
        }

        function insert($table, $columns, $try = false) {
            $this->get_connection();

            $fields = array();
            $values = array();
            foreach ($columns as $key => $value) {
                array_push($fields, "`$key`");
                $value = $this->ensure_utf8_string($value);
                array_push($values, "'" . $this->connection->real_escape_string($value) . "'");
            }
            $fields = implode(', ', $fields);
            $values = implode(', ', $values);

            $this->query("INSERT INTO `$table` ($fields) VALUES ($values)", $try);

            return $this->connection->insert_id;
        }

        function try_insert($table, $columns) {
            $this->get_connection();

            $result = $this->insert($table, $columns, true);
            if ($this->connection->errno != 0) {
                return false;
            }
            return $result;
        }

        function update($table, $columns, $where, $limit = -1) {
            $this->get_connection();

            $sets = array();
            foreach ($columns as $key => $value) {
                if ($key[0] === '@') {
                    $actual_key = substr($key, 1);
                    $sql_value = $value;
                } else {
                    $actual_key = $key;
                    $value = $this->ensure_utf8_string($value);
                    $sql_value = "'" . $this->connection->real_escape_string($value) . "'";
                }

                array_push($sets, "`$actual_key` = $sql_value");
            }

            $sets = implode(', ', $sets);

            $limit = $limit >= 0 ? " LIMIT $limit" : '';
            $this->query("UPDATE `$table` SET $sets WHERE $where $limit");
            return $this->connection->affected_rows;
        }

        function update_query($query) {
            $this->get_connection();

            $this->query($query);
            return $this->connection->affected_rows;
        }

        function delete($table, $where, $limit = -1) {
            $this->get_connection();

            $limit = $limit >= 0 ? "LIMIT $limit" : '';
            $this->query("DELETE FROM `$table` WHERE $where $limit");
            return $this->connection->affected_rows;
        }

        function get_errors() {
            return $this->errors;
        }

        function show_tables() {
            $this->get_connection();

            $result = $this->connection->query("SHOW TABLES");
            $output = array();
            for ($i = 0; $i < $result->num_rows; ++$i) {
                $row = $result->fetch_row();
                $name = $row[0];
                if (substr($name, 0, 2) !== 'ZZ') {
                    array_push($output, $name);
                }
            }
            sort($output);
            return $output;
        }
    }

    function sql_initialize($host, $user, $password, $dbname) {
        global $_PHP_UTILS_DB;
        $_PHP_UTILS_DB = new Database();
        $_PHP_UTILS_DB->init($host, $user, $password, $dbname);
    }

    function sql_get_db() {
        global $_PHP_UTILS_DB;
        if ($_PHP_UTILS_DB === null) throw new Exception("Cannot use this function until the database is initialized.");
        $_PHP_UTILS_DB->get_connection();
        return $_PHP_UTILS_DB;
    }

    function sql_try_query($query) {
        return sql_get_db()->query($query, true);
    }

    function sql_select($query) {
        return sql_get_db()->query($query);
    }

    function sql_select_row($query) {
        $result = sql_select($query);
        if ($result->has_any()) {
            return $result->next();
        }
        return null;
    }

    function sql_select_as_array($query) {
        $output = array();
        $result = sql_select($query);
        while ($result->has_more()) {
            array_push($output, $row = $result->next());
        }
        return $output;
    }

    function sql_insert($table, $values) {
        return sql_get_db()->insert($table, $values);
    }

    function sql_try_insert($table, $values) {
        return sql_get_db()->try_insert($table, $values);
    }

    function sql_update($table, $columns, $where, $limit = -1) {
        return sql_get_db()->update($table, $columns, $where, $limit);
    }

    function sql_update_query($query) {
        return sql_get_db()->update_query($query);
    }

    function sql_delete($table, $where, $limit = -1) {
        return sql_get_db()->delete($table, $where, $limit);
    }

    function sql_show_tables() {
        return sql_get_db()->show_tables();
    }

    /*
        +1's a column in a table using the where clause.
        If none exists, tries to insert one using the insertValues.
        Returns false if update and insert both fail.
        True otherwise.
    */
    function sql_increment_upsert($table, $column, $where, $insertValues) {
        $db = sql_get_db();
        $table = sql_safe_string($table);
        $column = sql_safe_string($column);
        $affected_rows = $db->update_query("UPDATE `$table` SET `$column` = `$column` + 1 WHERE $where LIMIT 1");
        if ($affected_rows == 1) {
            return true;
        }
        return $db->try_insert($table, $insertValues);
    }

    function sql_safe_string($value) {
        return sql_get_db()->connection->real_escape_string($value);
    }
    // Aliases for sql_safe_string that I keep accidentally typing.
    function sql_sanitize($value) { return sql_safe_string($value); }
    function sql_sanitize_string($value) { return sql_safe_string($value); }

    function sql_debug_info() {
        sql_get_db()->echo_synopsis();
        return '';
    }

    function sql_select_by_ids($table, $ids, $lookup_column, $select_columns = null) {
        if (count($ids) == 0) return array();
        $unique_ids = array();
        foreach ($ids as $id) {
            $safe_id = sql_safe_string($id);
            $unique_ids[$safe_id] = $safe_id;
        }
        $ids = array_values($unique_ids);
        $lookup_column = sql_safe_string($lookup_column);
        $columns = array($lookup_column => $lookup_column);
        if ($select_columns !== null) {
            foreach ($select_columns as $column) {
                $column = sql_safe_string($column);
                $columns[$column] = $column;
            }
        }
        $db_rows = sql_select("
            SELECT
                `" . implode("`, `", array_values($columns)) . "`
            FROM `" . sql_safe_string($table) . "`
            WHERE
                `" . $lookup_column . "` IN ('" . implode("', '", $ids) . "')
            LIMIT " . count($ids));
        $output = array();
        while ($db_rows->has_more()) {
            $row = $db_rows->next();
            $key = $row[$lookup_column];
            $output[$key] = $row;
        }
        return $output;
    }

    function sql_select_by_id($table, $id, $lookup_column, $select_columns) {
        $multi_id = sql_select_by_ids($table, array($id), $lookup_column, $select_columns);
        if (isset($multi_id[$id])) return $multi_id[$id];
        return null;
    }

?>