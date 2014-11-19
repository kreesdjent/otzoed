<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '/2/admin/otzovik/begin_admin.php';

class FractalObject {
    public $name;
    public $value;
    public $child;
    public $parent;
    private $drawn = false;
    
    static $handler;
    static $list_by_name;
    static $list_by_handler;
    
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
    
    static function batch($objects) {
        return;
    }
    
    function has_child() {
        return is_array($this->child);
    }
    
    function is_uniform() {
        if ($this->has_child()) {
            reset($this->child);
            return is_numeric(key($this->child));
        } else return false;
    }
    
    function uniform_keys() {
        if ($this->is_uniform()) {
            return array_keys($this->child[0]->child);
        } elseif ($this->has_child()) {
            return array_keys($this->child);
        } else {
            return null;
        }
    }
    
    function flatten_by_name() {
        $result = array();
        if ($this->drawn) return $result;
        $this->drawn = true;
        if (is_array($this->child)) {
            foreach ($this->child as $obj) {
                $result2 = $obj->flatten_by_name();
                foreach ($result2 as $k => $v) {
                    if (!isset($result[$k])) $result[$k] = array();
                    $result[$k] += array_merge($result[$k], $v);
                }
            }
        } else {
            $result[$this->name] = array($this);
        }
        $this->drawn = false;
        return $result;
    }
    
    function flatten_by_handler() {
        $result = array();
        if ($this->drawn) return $result;
        $this->drawn = true;
        if ($this->has_child()) {
            foreach ($this->child as $obj) {
                $result2 = $obj->flatten_by_handler();
                foreach ($result2 as $k => $v) {
                    if (!isset($result[$k])) $result[$k] = array();
                    $result[$k] += array_merge($result[$k], $v);
                }
            }
        } else {
            $result[get_called_class()] = array($this);
        }
        $this->drawn = false;
        return $result;
    }
       
    function grow() {
        $lists = $this->flatten_by_handler();
        foreach ($lists as $k => $list) {
            //$class = static::getHandler($k);
            //if ($class == get_class()) continue;
            //$class::batch($list);
            $k::batch($list);
        }
    }
    
    function __construct($name, $value, $root_value = '') {
        $this->name = $name;
        $this->value = $root_value;
        if (is_array($value) && count($value) == 1) $value = $value[0];
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $class = static::getHandler($k);
                //if ($v == 1428681) var_dump($class, get_class(), isset(self::$list_by_handler[$class]));
                if ($class != get_class() && isset(self::$list_by_handler[$class])) {
                    foreach (self::$list_by_handler[$class] as $obj) {
                        if ($v == $obj->value) {
                            //var_dump($class, $obj->value);
                            $this->child[$k] = $obj;
                            break;
                        }
                    }
                    if (isset($this->child[$k])) continue;
                }
                $root_value = isset($v['id']) ? $v['id'] : '';
                $this->child[$k] = new $class($k, $v, $root_value);
                $this->child[$k]->parent = $this;
            }
        } else {
            $this->value = $value;
        }
        $class = get_called_class();
        if (!is_numeric($name)) self::$list_by_name[$name][] = $this;
        //$key = is_array($this->child) ? key($this->child) : 'key';
        //if ($class != get_class() && !is_numeric($name) && !is_numeric($key)) 
            self::$list_by_handler[$class][] = $this;
    }
        
    function output($view = 'table', $mootview = null) {
        if ($this->drawn) {
            echo 'RECURSION';
            return;
        }
        $this->drawn = true;
        if (!$mootview) $mootview = $view;
        if ($this->has_child()) {
            if ($view == 'table') $this->outputTable($mootview);
            elseif ($view == 'list') $this->outputList($mootview);
        } else {
            echo $this->value;
        }
        $this->drawn = false;
    }
    
    function outputTable($mootview = null) {
        if (!$mootview) $mootview = 'table';
        if ($this->is_uniform()) {
            echo "<div><b>class</b> ".get_called_class()."</div>";
            echo "<div><b>value</b> $this->value</div>";
        } else {
            echo "<div><b>class</b>: ".get_called_class()."</div>";
            echo "<div><b>{$this->name}</b>: $this->value</div>";
        }
        echo '<table style="border: 1px solid silver;">';
        echo '<thead><tr>';
        //if ($this->name == 'Review_query') { var_dump(key($this->child), $this->name, array_keys($this->child)); exit; }
        if ($this->is_uniform()) {
            echo '<th>class</th><th>'.implode('</th><th>', $this->uniform_keys()).'</th>';
            echo '</tr></thead><tbody>';
            foreach ($this->child as $item) {
                echo '<tr>';
                echo "<td>".get_called_class()."</td>";
                foreach ($item->child as $obj) {
                    $view = $obj->is_uniform() ? $mootview : 'list';
                    echo '<td>';
                    $obj->output($view, $mootview);
                    echo '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<th>class</th><th>'.implode('</th><th>', $this->uniform_keys()).'</th>';
            echo '</tr></thead><tbody>';
            echo '<tr>';
            echo "<td>".get_called_class()."</td>";
            foreach ($this->child as $obj) {
                $view = $obj->is_uniform() ? $mootview : 'list';
                echo '<td>';
                $obj->output($view, $mootview);
                echo '</td>';
            }
            echo '</tr>';
            echo '</tbody></table>';
        }
    }
    
    function outputList($mootview = null) {
        if (!$mootview) $mootview = 'list';
        echo '<table style="border: 1px solid silver;">';
        echo "<tr><td style='font-weight: bold;'>class</td><td>".get_called_class()."</td></tr>";
        echo "<tr><td style='font-weight: bold;'>value</td><td>$this->value</td></tr>";
        foreach ($this->child as $obj) {
            echo '<tr>';
            echo "<td style='font-weight: bold;'>$obj->name</td>";
            $view = $obj->is_uniform() ? $mootview : 'list';
            echo "<td>"; $obj->output($view, $mootview); echo "</td>";
            echo '</tr>';
        }
        echo '</table>';
    }
}

class OtzoDB extends FractalObject {
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
        //if ($rows_count == 0) throw new Exception("Ничего не найдено по запросу: $query");
        if ($num_rows == 0) return null;
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return new static(get_called_class().'_query', $rows, $query);
    }
}

class Review extends OtzoDB {
    static function get($tablename, $where, $limit, $offset) {
        global $db;
        return static::query("SELECT id, user_id, product_id, title, product, status, status_m FROM $db.reviews WHERE $where ORDER BY id DESC LIMIT $offset, $limit");
    }
    
    static function batch($objects) {
        global $db;
        $review_id = $user_id = array();
        foreach ($objects as $obj) {
            if ($obj->name == 'reviews') {
                if ($obj->value == 0) continue;
                $user_id[] = $obj->parent->child['id']->value;
            } else {
                $v = $obj->value;
                if ($v == 0) continue;
                $review_id[] = $v;
            }
        }
        if (count($review_id) + count($user_id) == 0) return;
        if (count($review_id) == 1) $where[] = "id=".$review_id[0];
        elseif (count($review_id) > 1) $where[] = "id IN(".implode(',', $review_id).")";
        if (count($user_id) == 1) $where[] = "user_id=".$user_id[0];
        elseif (count($user_id) > 1) $where[] = "user_id IN(".implode(',', $user_id).")";
        $new_obj = static::query("SELECT id, user_id, product_id, title, product FROM $db.reviews WHERE ".implode(' OR ', $where)." ORDER BY id DESC");
        $array = $new_obj->is_uniform() ? $new_obj->child : array($new_obj);
        foreach ($array as $new_obj) {
            foreach ($objects as $obj) {
                if ($obj->name == 'reviews' && $obj->parent->child['id']->value == $new_obj->child['user_id']->value) {
                    $obj->child[] = $new_obj;
                    $new_obj->parent = $obj->child;
                } elseif (abs($obj->value) == $new_obj->child['id']->value) {
                    $obj->child = $new_obj->child;
                }
            }
        }
    }
}
Review::registerHandler('review_id, User.reviews');
Review::registerHandler('reviews', 'table');

class User extends OtzoDB {
    static function get($tablename, $where, $limit, $offset) {
        global $db;
        return static::query("SELECT id, login, avatar, karma, reviews FROM $db.users WHERE $where ORDER BY id DESC LIMIT $offset, $limit");
    }
    
    static function batch($objects) {
        global $db;
        $ids = array();
        foreach ($objects as $obj) {
            $v = abs($obj->value);
            if ($v == 0) continue;
            $ids[] = $v;
        }
        $ids = array_unique($ids);
        if (count($ids) == 0) return;
        $where = count($ids) == 1 ? "id=".$ids[0] : "id IN(".implode(',', $ids).")";
        $new_obj = static::query("SELECT id, login, avatar, reviews FROM $db.users WHERE $where");
        $array = $new_obj->is_uniform() ? $new_obj->child : array($new_obj);
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

echo '<pre>';

$tablename = $_GET['t'];
$where = isset($_GET['where']) ? $_GET['where'] : 1;
$limit = isset($_GET['limit']) ? $_GET['limit'] : 2;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$popka = OtzoDB::fromTable($tablename, $where, $limit, $offset);
$popka->grow();
$popka->grow();
$popka->output('table');


echo '<hr>';
echo "<b>QUERY LOG</b><br>";
echo "<ol style='margin: 0;'>";
foreach (OtzoDB::$query_log as $q) {
    echo "<li>{$q['sql']}</li>";
    echo "<ul><li>num_rows = {$q['num_rows']}</li><li>elapsed  = {$q['elapsed']}</li></ul>";
}
echo "</ol>";
echo '<hr>';
echo "<b>HANDLERS</b><br>";
foreach (FractalObject::$handler as $tag => $handlers) {
    echo "<b>tag $tag</b><br>";
    echo "<ol style='margin: 0;'>";
    foreach ($handlers as $name => $hand) {
        echo "<li>$name = $hand</li>";
    }
    echo "</ol>";
}

echo '<hr>';
echo "<b>OBJECT LIST BY HANDLER</b><br>";
foreach (FractalObject::$list_by_handler as $k => $list) {
    echo "<b>$k</b><br>";
    echo "<ol style='margin: 0;'>";
    foreach ($list as $obj) {
        echo "<li>$obj->name = $obj->value</li>";
    }
    echo "</ol>";
}
echo '<hr>';
echo "<b>OBJECT LIST BY NAME</b><br>";
foreach (FractalObject::$list_by_name as $k => $list) {
    echo "<b>$k</b><br>";
    echo "<ol style='margin: 0;'>";
    foreach ($list as $obj) {
        echo "<li>$obj->name = $obj->value</li>";
    }
    echo "</ol>";
}
//print_r($popka);
echo '</pre>';

//проблема 1: постоянно создаются одни и те же объекты, например одинаковые user_id в разных отзывах
//проблема 2: если мы будем подменять их созданными ранее, то будет рекурсия