<style type="text/css">
    hr { border: 0; border-bottom: 1px dashed silver; }
</style>
<pre style="-moz-tab-size: 14;">
<form method="GET" action="">
    //SELECT id, user_id, review_id, comment FROM comments ORDER BY id LIMIT 4
    //SELECT reviews.id, user_id, product_id, puser_id FROM reviews JOIN products ON products.id=reviews.product_id WHERE status_m>0 ORDER BY reviews.id LIMIT 4
    //SELECT id, user_id, product_id, cat_id, product, title, status, status_m FROM reviews WHERE status_m>0 ORDER BY id LIMIT 4
    //SELECT id, login, karma, reviews FROM users LIMIT 5
    <textarea name="query" style="width: 1000px; height: 50px;"><?=$_GET['query']?></textarea>
    <input type="submit" value="GO">
</form>
<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '/2/admin/otzovik/begin_admin.php';

trait AdronRegistry {
    protected static $registry;
    static function outputRegistry() {
        foreach (self::$registry as $c => $list) {
            echo $c.' ('.count($list).')::';
            echo '<ol>';
            foreach ($list as $obj) {
                echo '<li>'.$obj->name()."\t= ".$obj->val().'</li>';
            }
            echo '</ol>';
        }
    }
    function addToRegistry() {
        self::$registry[get_called_class()][] = $this;
    }
    static function findInRegistry($name, $val) {
        $class = get_called_class();
        if (!isset(self::$registry[$class])) return false;
        foreach (self::$registry[$class] as $item) {
            if ($item->name() == $name && $item->val() == $val) return $item;
        }
        return false;
    }
}

trait AdronHandler {
    protected static $handler;
    static function outputHandlers() {
        print_r(self::$handler);
    }
    static function setHandler($name, $class) {
        $called_class = get_called_class();
        $name = explode(',', str_replace(' ', '', $name));
        foreach ($name as $pn) {
            self::$handler[$called_class][$pn] = $class;
        }
    }
    static function getHandler($name) {
        $class = get_called_class();
        if (isset(self::$handler[$class][$name])) return self::$handler[$class][$name];
        $parent = get_parent_class($class);
        return $parent ? $parent::getHandler($name) : false;
    }
    static function listHandlers($include_inherited = false) {
        $class = get_called_class();
        $result = isset(self::$handler[$class]) ? self::$handler[$class] : array();
        if ($include_inherited) {
            $parent = get_parent_class($class);
            if ($parent) $result = array_merge($parent::listHandlers(true), $result) ;
        }
        return $result;
    }
}

//------------------------------------------------------------------------------

class Adron {
    use AdronRegistry, AdronHandler;

    protected $__name;
    protected $__val;
    protected $__items;
           
    function __construct($val = null) {
        if ($val) {
            $this->__val = $val;
        } elseif (isset($this->id)) {
            $this->__val = $this->id;
        }
        $this->addToRegistry();
    }
    function name($name = null) {
        if ($name) $this->__name = $name;
        return $this->__name;
    }
    function val($val = null) {
        if ($val) $this->__val = $val;
        return $this->__val;
    }
    function item($index = null) {
        if (is_a($index, get_class())) {
            if (!property_exists($this, $index->__name)) {
                $this->__items[] = $index;
            } elseif (!isset($this->__items[$index->__name])) { 
                $this->__items[$index->__name] = $index;
            }
            return $this;
        } elseif (isset($index)) {
            return isset($this->__items[$index]) ? $this->__items[$index] : null;
        } else {
            return is_array($this->__items) ? reset($this->__items) : null;
        }
    }
    static function findOrCreate($name, $val) {
        $class = static::getHandler($name);
        if (!$class) return null;
        $item = $class::findInRegistry($name, $val);
        if (!$item) {
            $item = new $class($val);
            $item->name($name);
        }
        return $item;
    }
    /*function grow() { //рекурсия!!!
        if ($this->__items) {
            foreach ($this->__items as $item) $item->grow();
        } else {
            foreach ($this as $k => $v) {
                if (stristr($k, '__')) continue;
                $item = static::findOrCreate($k, $v);
                if ($item) $this->item($item);
            }
        }
    }*/
    protected static $batch;
    protected function prebatch() { //рекурсия!!!
        if ($this->__items) {
            foreach ($this->__items as $item) $item->prebatch();
        } else {
            foreach ($this as $k => $v) {
                if (stristr($k, '__')) continue;
                $class = static::getHandler($k);
                if (!$class) continue;
                static::$batch[$class][$k][] = $v;
            }
        }
    }
    static function batch($vals) {
        return null;
    }
    function grow() {
        static::$batch = array();
        $this->prebatch();
        foreach (static::$batch as $class => $vals) $class::batch($vals);
        $this->reallyGrow();
    }
    protected function reallyGrow() { //рекурсия!!!
        if ($this->__items) {
            foreach ($this->__items as $item) $item->reallyGrow();
        } else {
            foreach ($this as $k => $v) {
                if (stristr($k, '__')) continue;
                $item = static::findOrCreate($k, $v);
                if ($item) $this->item($item);
            }
        }
    }
}

//------------------------------------------------------------------------------

class Query extends Adron {
    public $num_rows = 0;
    public $elapsed = 0;
    public $error = false;
    function __construct($query, $itemname = null) {
        $start = microtime(true);
        $result = mysql_query($query);
        if (mysql_error()) {
            $this->error = mysql_error();
            $this->name('error');
            parent::__construct($query);
        } else {
            $this->elapsed = round(microtime(true)-$start, 4);
            $this->num_rows = mysql_num_rows($result);
            $name = mysql_field_table($result, 0);
            $this->name($name);
            parent::__construct($query);
            $class = static::getHandler($name);
            if (!$class) $class = 'Adron';
            if (empty($itemname)) $itemname = $name;
            while ($item = mysql_fetch_object($result, $class)) {
                $item->name($itemname);
                $this->item($item);
            }
        }
    }
}

//------------------------------------------------------------------------------

class Review extends Adron {
    
}
//Query::setHandler('reviews', 'Review');
Adron::setHandler('review_id, reviews', 'Review');

//------------------------------------------------------------------------------

class ReviewStatus extends Adron {
    function __construct($val = null) {
        parent::__construct($val);
        switch ($val) {
            case 4: $this->desc = 'Одобрен'; break;
            case 11: $this->desc = 'Одобрен как экспертный'; break;
        }
    }
}
Review::setHandler('status', 'ReviewStatus');

//------------------------------------------------------------------------------

class User extends Adron {

}
Query::setHandler('users', 'User');
Adron::setHandler('user_id, from_user', 'User');
Review::setHandler('status_m', 'User');

//------------------------------------------------------------------------------

class UserReviews extends Query {

}
User::setHandler('reviews', 'UserReviews');

//------------------------------------------------------------------------------

if (empty($_GET['query'])) exit;
$list = new Query($_GET['query']);
$list->grow();

?>
<hr><? print_r($list); ?>
<hr><? Adron::outputHandlers(); ?>
<hr><? Adron::outputRegistry(); ?>
</pre>


<?
    //один объект может быть потомком нескольких объектов, одиночное свойство parent бессмыслено
    //избавиться от $val в конструкторе
?>