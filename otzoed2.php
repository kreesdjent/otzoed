<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '/2/admin/otzovik/begin_admin.php';

class Otzocol {
    public $tablename;
    public $related_tablename;
    public $name;
    public $alias;
    public $desc;
    public $type;
    public $size;
    public $null;
    public $key;
    public $default;
    public $extra;
    public $value;
    public $enum;
    public $unique;
    public $readonly = false;

    function __construct($props) {
        foreach ($props as $k => $v) {
            $k = strtolower($k);
            if (!property_exists($this, $k)) continue;
            $this->$k = $v;
        }
        $this->alias = $props['name'];
        $this->size = 0;
        if ($props['type']) {
            if (stristr($props['type'], '(')) {
                $size = explode('(', $props['type']);
                $this->type = trim($size[0]);
                if ($this->type == 'enum') {
                    $this->enum = explode(',', $size[1]);
                    array_walk($this->enum, function(&$item) { $item = trim($item, "()' "); });
                } else {
                    $this->size = (int)trim($size[1], ' )');
                }
            }
        }
        if ($this->extra == 'auto_increment') $this->readonly = true;
        if ($this->name == 'ip') $this->readonly = true;
        if ($this->name == 'ua') $this->readonly = true;
    }
}

class Otzotable {
    public $name;
    public $col;
    public $ind;
    public $cols_count;
    
    function __construct($table_name) {
        if (empty($table_name)) throw new Exception("Не задано имя таблицы");
        global $db;
        $result = mysql_query("SHOW TABLES LIKE '".mysql_real_escape_string($table_name)."'");
        if (mysql_num_rows($result) == 0) throw new Exception("Таблица '$table_name' не найдна в базе '$db'");

        $this->name = $table_name;

        $result = mysql_query("SHOW INDEXES FROM $db.".mysql_real_escape_string($table_name));
        while ($row = mysql_fetch_assoc($result)) {
            $this->ind[$row['Column_name']]['key_name'] = $row['Key_name'];
            $this->ind[$row['Column_name']]['non_unique'] = $row['Non_unique'];
            $this->ind[$row['Column_name']]['seq_in_index'] = $row['Seq_in_index'];
        }

        $result = mysql_query("SHOW COLUMNS FROM $db.".mysql_real_escape_string($table_name));
        $this->cols_count = mysql_num_rows($result);
        $this->col = new stdClass();
        while ($row = mysql_fetch_assoc($result)) {
            $row['name'] = $row['Field'];
            unset($row['Field']);
            $row = array_combine(array_map('strtolower', array_keys($row)), array_values($row));
            $newcol = new Otzocol($row);
            $newcol->tablename = $table_name;
            $newcol->unique = false;
            if (isset($this->ind[$row['name']]))
                if ($this->ind[$row['name']]['non_unique'] == 0)
                    $newcol->unique = true;

            $this->col->{$row['name']} = $newcol;
        }
    }
}

class OtzoVal {
    public $name;
    public $value;
    function __construct($name, $value) {
        $this->name = $name;
        $this->value = $value;
    }
    function output() {
        echo $this->value;
    }
}

class OtzoRow extends OtzoVal {
    function __construct($name, $row) {
        $this->name = $name;
        foreach ($row as $k => $v) {
            $this->value[] = new OtzoVal($k, $v);
        }
    }
    function outputRow() {
        echo '<tr>';
        foreach ($this->value as $obj) {
            echo '<td>';
            $obj->output();
            echo '</td>';
        }
        echo '</tr>';
    }
    function outputBlock() {
        echo '<table>';
        foreach ($this->value as $obj) {
            echo '<tr>';
            echo "<td style='font-weight: bold;'>$obj->name</td>";
            echo "<td>"; $obj->output(); echo "</td>";
            echo '</tr>';
        }
        echo '</table>';
    }
}

class OtzoList extends OtzoVal {
    public $rows;
    public $rows_count;
    public $table;
    public $query;
    static function fromTable($tablename, $where, $limit, $offset) {
        global $db;
        $obj = new self($tablename, 'table');
        $obj->table = new Otzotable($tablename);
        $obj->query = "SELECT * FROM $db.$tablename WHERE $where LIMIT $offset, $limit";
        $result = mysql_query($obj->query);
        if (mysql_error()) throw new Exception(mysql_error().'<br>'.$obj->query);
        $obj->rows_count = mysql_num_rows($result);
        if ($obj->rows_count == 0) throw new Exception("Запись WHERE $where в таблице '$tablename' отсутствует");
        while ($row = mysql_fetch_assoc($result)) {
            $obj->rows[] = $row;
        }
        foreach ($obj->table->col as $col) {
            for ($i = 0; $i < $obj->rows_count; $i++) {
                $cols[$col->name][$i] = $obj->rows[$i][$col->name];
            }
        }
        foreach ($cols as $k => $v) {
            $obj->cols[$k] = Otzo::fromList($k, $v);
            foreach ($obj->cols[$k]->rows as $i => $row) {
                $obj->rows[$i][$k] = $row;
            }
        }
        return $obj;
    }
    static function fromArray($name, $vals) {
        $obj = new self($name, 'list');
        $obj->rows_count = count($vals);
        foreach ($vals as $val) {
            $obj->rows[] = new OtzoVal($col_name, $val);
        }
        return $obj;
    }
}


class Otzo {
    public $name;
    public $value;
    public $table;
    
    static function init($name, $value) {
        $obj = new self();
        $obj->name = $name;
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $obj->value[] = static::init($k, $v);
            }
        } else {
            $obj->value = $value;
        }
        return $obj;
    }
    
    static function fromTable($table_name, $where, $limit, $offset) {
        global $db;
        $table = new Otzotable($table_name);
        $query = "SELECT * FROM $db.$table_name WHERE $where LIMIT $offset, $limit";
        $result = mysql_query($query);
        if (mysql_error()) throw new Exception(mysql_error().'<br>'.$query);
        $rows_count = mysql_num_rows($result);
        if ($rows_count == 0) throw new Exception("Запись WHERE $where в таблице '$table_name' отсутствует");
        elseif ($rows_count == 1) {
            $rows = mysql_fetch_assoc($result);
        } else {
            while ($row = mysql_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        $obj = static::init($table_name, $rows);
        $obj->table = $table;
        return $obj;
    }
          
    public function output() {
        if (!is_array($this->value)) {
            echo $this->value;
            return;
        } elseif (count($this->value) == 1) {
            $this->outputBlock();
        } else {
            $this->outputTable();
        }
    }
    
    public function outputTable() {
        echo "<div><b>$this->name</b></div>";
        echo '<table>';
        echo '<thead><tr>';
        foreach ($this->table->col as $col) {
            echo "<th>$col->name</th>";
        }
        echo '</tr></thead><tbody>';
        foreach ($this->value as $obj) {
            $obj->outputRow();
        }
        echo '</tbody></table>';
    }
    
    public function outputRow() {
        echo '<tr>';
        foreach ($this->value as $obj) {
            echo '<td>';
            $obj->output();
            echo '</td>';
        }
        echo '</tr>';
    }
    
    public function outputList() {
        echo "<div><b>$this->name</b></div>";
        foreach ($this->value as $obj) {
            $obj->outputBlock();
        }
    }
    
    public function outputBlock() {
        echo "<div><b>$this->name</b></div>";
        echo '<table>';
        foreach ($this->value as $obj) {
            echo '<tr>';
            echo "<td style='font-weight: bold;'>$obj->name</td>";
            echo "<td>"; $obj->output(); echo "</td>";
            echo '</tr>';
        }
        echo '</table>';
    }
}


class FractalObject {
    public $name;
    public $value;
    
    static $handler;
    
    static function getHandler($name) {
        if (isset(self::$handler[$name])) return self::$handler[$name];
        else return get_class();
    }
    
    static function registerHandler($name) {
        $name = explode(',', str_replace(' ', '', $name));
        foreach ($name as $k) {
            self::$handler[$k] = get_called_class();
        }
    }
    
    static function batch($values) {
        //array_walk($values, function(&$item) { $item .= 'b'; });
        return $values;
    }
    
    function __construct($name, $value) {
        $this->name = $name;
        if (is_array($value) && count($value) == 1) $value = $value[0];
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $class = static::getHandler($k);
                $this->value[] = new $class($k, $v);
                $this->value[count($this->value)-1]->parent = $this;
            }
        } else {
            $this->value = $value;
        }
        self::$list[$name][] = $this;
    }
    
    function flatten() {
        $result = array();
        if (is_array($this->value)) {
            foreach ($this->value as $obj) {
                $result2 = $obj->flatten();
                foreach ($result2 as $k => $v) {
                    if (!isset($result[$k])) $result[$k] = array();
                    $result[$k] += array_unique(array_merge($result[$k], $v));
                }
            }
        } else {
            $result[$this->name] = array($this->value);
        }
        return $result;
    }
    
    function grow() {
        $array = $this->flatten();
        foreach ($array as $k => $v) {
            $class = static::getHandler($k);
            $batch = $class::batch($v);
            $v2 = is_object($batch) ? $batch->value : $batch;
            foreach ($v as $i => $old_val) {
                if (!isset($v2[$i])) $v2[$i] = $old_val;
                $new_val = is_object($v2[$i]) ? $v2[$i]->value : $v2[$i];
                $this->replaceRecursive($k, $old_val, $new_val);
            }
        }
    }
    
    function replaceRecursive($name, $old_val, $new_val) {
        if ($old_val == $new_val) return;
        if (is_array($this->value)) {
            foreach ($this->value as $obj) {
                $obj->replaceRecursive($name, $old_val, $new_val);
            }
        } elseif ($this->name == $name && $this->value == $old_val) {
            $this->value = $new_val;
        }
    }
    
    function output($view = 'table') {
        if (is_array($this->value)) {
            if ($view == 'table') $this->outputTable();
            elseif ($view == 'list') $this->outputList();
        } else {
            echo $this->value;
        }
    }
    
    function outputTable() {
        echo "<div><b>{$this->name}</b></div>";
        echo '<table>';
        echo '<thead><tr>';
        foreach ($this->value[0]->value as $obj) {
            echo "<th>$obj->name</th>";
        }
        echo '</tr></thead><tbody>';
        foreach ($this->value as $item) {
            echo '<tr>';
            foreach ($item->value as $obj) {
                echo '<td>';
                $obj->output('list');
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    function outputList() {
        echo '<table>';
        foreach ($this->value as $obj) {
            echo '<tr>';
            echo "<td style='font-weight: bold;'>$obj->name</td>";
            echo "<td>"; $obj->output('list'); echo "</td>";
            echo '</tr>';
        }
        echo '</table>';
    }
}

class OtzoDB extends FractalObject {
    static $thandler;
    static $query_log;
    static function getTHandler($name) {
        if (isset(self::$thandler[$name])) return self::$thandler[$name];
        else return get_class();
    }
    static function registerTHandler($name) {
        $name = explode(',', str_replace(' ', '', $name));
        foreach ($name as $k) {
            self::$thandler[$k] = get_called_class();
        }
    }
    
    static function fromTable($tablename, $where, $limit, $offset) {
        $class = static::getTHandler($tablename);
        $obj = $class::get($tablename, $where, $limit, $offset);
        return $obj;
    }
    
    static function get($tablename, $where, $limit, $offset) {
        global $db;
        return static::query("SELECT * FROM $db.$tablename WHERE $where ORDER BY id DESC LIMIT $offset, $limit");
    }
    
    static function query($query) {
        $start_time = microtime(true);
        $result = mysql_query($query);
        $elapsed = round(microtime(true) - $start_time, 3);
        if (mysql_error()) throw new Exception(mysql_error().'<br>'.$query);
        $num_rows = mysql_num_rows($result);
        self::$query_log[] = array('sql' => $query, 'num_rows' => $num_rows, 'elapsed' => $elapsed);
        //if ($rows_count == 0) throw new Exception("Ничего не найдено по запросу: $query");
        if ($num_rows == 0) return null;
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return new self($query, $rows);
    }
}

class Review extends OtzoDB {
    static function get($tablename, $where, $limit, $offset) {
        global $db;
        return static::query("SELECT id, user_id, product_id, title, product, status, status_m FROM $db.reviews WHERE $where ORDER BY id DESC LIMIT $offset, $limit");
    }
    
    static function batch($values) {
        global $db;
        $ids = array();
        foreach ($values as $v) {
            if ($v == 0) continue;
            $ids[] = $v;
        }
        if (count($ids) == 0) return $values;
        return static::query("SELECT id, title, product FROM $db.reviews WHERE id IN(".implode(',', $ids).") ORDER BY FIELD(id, ".implode(',', $ids).")");
    }
}
Review::registerHandler('review_id');
Review::registerTHandler('reviews');

class User extends OtzoDB {
    static function get($tablename, $where, $limit, $offset) {
        global $db;
        return static::query("SELECT id, login, avatar, karma, reviews FROM $db.users WHERE $where ORDER BY id DESC LIMIT $offset, $limit");
    }
    
    static function batch($values) {
        global $db;
        $ids = array();
        foreach ($values as $v) {
            if ($v == 0) continue;
            if ($v < 0) $v = -$v;
            $ids[] = $v;
        }
        if (count($ids) == 0) return $values;
        return static::query("SELECT id, login, avatar FROM $db.users WHERE id IN(".implode(',', $ids).") ORDER BY FIELD(id, ".implode(',', $ids).")");
    }
}
User::registerHandler('user_id, status_m, puser_id');
User::registerTHandler('users');


$popka = OtzoDB::fromTable('reviews', '1', 1, 0);
//$popka->output('table');
//$popka->output('list');
//$popka->grow();
$popka->output('list');

echo '<pre>';
print_r(OtzoDB::$query_log);
print_r($popka);
//$popka->grow();
//var_dump($popka);
echo '</pre>';

//проблема 1: вешая хендлер на название поля мы не можем сделать два разных хендлера на одинаковые поля в разных таблицах, например, status
//проблема 2: пачечная функция не даёт инфы нужной для создания некоторых объектов, например, users.reviews