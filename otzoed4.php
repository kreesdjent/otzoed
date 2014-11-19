<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '/2/admin/otzovik/begin_admin.php';

class Thing {
    static $handler;
    static function getHandler($name, $tag = 0) {
        $class = get_called_class();
        if (is_numeric($name)) return $class;
        elseif (isset(self::$handler[$tag][$class.'.'.$name])) return self::$handler[$tag][$class.'.'.$name];
        elseif (isset(self::$handler[$tag][$name])) return self::$handler[$tag][$name];
        else return get_class();
    }
    static function registerHandler($name, $tag = 0) {
        $name = explode(',', str_replace(' ', '', $name));
        foreach ($name as $k) {
            self::$handler[$tag][$k] = get_called_class();
        }
    }
    static function batch($list) {
        return;
    }
    
    public $value;
    
    function __construct($array) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = new self($v);
            }
        }
        $this->value = $array;
    }
    
    function flatten_by_handler() {
        $result = array();
        $old_hand = array();
        foreach ($this->value as $k => $v) {
            if (is_object($v)) {
                $result2 = $v->flatten_by_handler();
                foreach ($result2 as $kk => $vv) {
                    if (!isset($result[$kk])) $result[$kk] = array();
                    $result[$kk] += array_merge($result[$kk], $vv);
                }
            } else {
                $hand = self::getHandler($k);
                if ($hand == get_class()) continue;
                if (in_array($hand, $old_hand)) break;
                $old_hand[] = $hand;
                $result[$hand][] = $this;
            }
        }
        return $result;
    }
    
    function grow() {
        $lists = $this->flatten_by_handler();
        foreach ($lists as $k => $list) {
            $k::batch($list);
        }
    }
    
    function output($view = null) {
        $is_table = false;
        if ($view == null || $view == 'table') {
            $is_table = true;
            $first = reset($this->value);
            $keys = array();
            if (is_object($first)) $keys = array_keys($first->value);
            foreach ($this->value as $k => $v) {
                if (!is_object($v)) { $is_table = false; break; }
                if (count(array_diff($keys, array_keys($v->value)))) { $is_table = false; break; }
            }
        } elseif ($view == 'list') {
            $is_table = false;
        }
        if ($is_table) $this->outputTable($view);
        else 
            $this->outputList($view);
    }
    
    function outputTable($view) {
        $first = reset($this->value);
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>'.implode('</th><th>', array_keys($first->value)).'</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        foreach ($this->value as $k => $v) {
            echo '<tr>';
            foreach ($v->value as $kk => $vv) {
                echo '<td>';
                if (is_object($vv)) {
                    $vv->output($view);
                } else {
                    echo $vv;
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    function outputList($view) {
        echo '<table>';
        foreach ($this->value as $k => $v) {
            echo '<tr>';
            echo "<td><b>$k</b></td>";
            echo '<td>';
            if (is_object($v)) {
                $v->output($view);
            } else {
                echo $v;
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

class OtzoDB extends Thing {
    static $query_log;
    
    static function fromTable($tablename, $where, $limit, $offset) {
        $class = static::getHandler($tablename, 'table');
        if ($class == get_parent_class()) $class = get_class();
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
        $elapsed = round(microtime(true) - $start_time, 4);
        if (mysql_error()) throw new Exception(mysql_error().'<br>'.$query);
        $num_rows = mysql_num_rows($result);
        self::$query_log[] = array('sql' => $query, 'num_rows' => $num_rows, 'elapsed' => $elapsed);
        if ($num_rows == 0) return null;
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return new static($rows);
    }
}

class Review extends OtzoDB {
    static function get($tablename, $where, $limit, $offset) {
        global $db;
        return static::query("SELECT id, user_id, product_id, title, product, status, status_m FROM $db.reviews WHERE $where ORDER BY id DESC LIMIT $offset, $limit");
    }
}
Review::registerHandler('review_id, User.reviews');
Review::registerHandler('reviews', 'table');

class User extends OtzoDB {
    static function get($tablename, $where, $limit, $offset) {
        global $db;
        return static::query("SELECT id, login, avatar, karma, reviews FROM $db.users WHERE $where ORDER BY id DESC LIMIT $offset, $limit");
    }
    static function batch($list) {
        global $db;
        $ids = array();
        foreach ($list as $obj) {
            $v = abs($obj->value);
            if ($v == 0) continue;
            $ids[] = $v;
        }
        $ids = array_unique($ids);
        if (count($ids) == 0) return;
        $where = count($ids) == 1 ? "id=".$ids[0] : "id IN(".implode(',', $ids).")";
        $new_obj = static::query("SELECT id, login, avatar, reviews FROM $db.users WHERE $where");
        $array = is_numeric(key($new_obj->child)) ? $new_obj->child : array($new_obj);
        foreach ($array as $new_obj) {
            foreach ($objects as $obj) {
                if (abs($obj->value) == $new_obj->child['id']->value) {
                    $obj->child = $new_obj->child;
                }
            }
        }
    }
}
User::registerHandler('user_id, status_m, puser_id');
User::registerHandler('users', 'table');




$t = OtzoDB::fromTable($_GET['t'], $_GET['w'], $_GET['l'], $_GET['o']);




?>
<style type="text/css">
    table td { vertical-align: top; border: 1px solid silver; }
</style>
<pre>
<b>QUERY LOG</b><br>
<ol style='margin: 0;'><?foreach (OtzoDB::$query_log as $q) {
    echo "<li>{$q['sql']}</li>";
    echo "<ul><li>num_rows = {$q['num_rows']}</li><li>elapsed  = {$q['elapsed']}</li></ul>";
}?></ol>
<hr>
<b>VIEW</b><br>
<?$t->output(); var_dump($t->flatten_by_handler());?>
<hr>
<b>DUMP</b><br>
<?print_r($t);?>
</pre>