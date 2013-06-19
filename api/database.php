<?php

// A small database class
class Database
{
    private $db;

    public function __construct($host, $username, $password, $database) {
        // Connect to the MySQL server
        $this->db = new mysqli($host, $username, $password, $database);

        if ($this->db->connect_errno > 0) {
            die('An error occurred while trying to connect to the database. Error: ' . $this->db->connect_error);
        }
    }

    public function select($columns, $table, $where = false, $or = false) {
        // A lazy method for SELECT
        if (is_array($columns)) {
            $columns = '`' . join('`,`', $columns) . '`';
        } else {
            $columns = "`{$columns}`";
        }

        if (is_array($where)) {
            // If where is an array, stringify it
            $where_array = $where;
            $where = '';

            $i = 0;
            $all_clauses = count($where);
            foreach ($where_array as $key=>$value) {
                $key = $this->escape_string($key);

                if (!is_numeric($value)) {
                    $value = "'" . $this->escape_string($value) . "'";
                }

                $where .= "`{$key}`={$value}";

                if ($i<$all_clauses-1) {
                    $where .= ($or) ? ' OR ' : ' AND ';
                }
                $i++;
            }
        }

        $statement = "SELECT {$columns} FROM `{$table}`" . (($where) ? "WHERE {$where}" : "");

        if (!$result = $this->db->query($statement)) {
            return false;
        }

        return $result;
    }

    public function insert($table, $columns, $values) {
        // A lazy method for INSERT
        if (is_array($columns)) {
            $columns = '`' . join('`,`', $columns) . '`';
        } else {
            $columns = "`{$columns}`";
        }

        if (is_array($values)) {
            // Stringify the given values
            $values_array = $values;
            $values = '';

            $i = 0;
            $all_values = count($values_array);
            foreach ($values_array as $value) {
                if (!is_numeric($value)) {
                    $value = "'" . $this->escape_string($value) . "'";
                }

                $values .= "{$value}";

                if ($i<$all_values-1) {
                    $values .= ',';
                }
                $i++;
            }
        } else if (is_string($values)) {
            $values = $this->escape_string($values);
        }

        $statement = "INSERT INTO `{$table}` ({$columns}) VALUES ({$values})";

        if (!$result = $this->db->query($statement)) {
            return false;
        }

        return $result;
    }

    private function escape_string($string) {
        return $this->db->escape_string($string);
    }

    public function __destruct() {
        $this->db->close();
    }
}

?>