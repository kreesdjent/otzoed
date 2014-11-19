<?php
    class Column {
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

        public $controls;
        public $interface;
        
        public $readonly = false;
        public $show_alias = true;
        public $enable_interface = true;
        public $reload_interface = true;
        public $show_in_multiview = false;
        public $show_widget = false;

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

            if (empty($this->jsclass)) {
                if (in_array($this->type, array('int', 'mediumint', 'smallint', 'tinyint', 'varchar', 'char', 'float')))
                    $this->jsclass = 'InputColumn';
                elseif (in_array($this->type, array('timestamp', 'datetime', 'date')))
                    $this->jsclass = 'DateColumn';
                elseif (in_array($this->type, array('text', 'mediumtext')))
                    $this->jsclass = 'TextColumn';
            }
        }

        function setValue($val) {
            $this->value = $val;
            $this->setControls();
            if ($this->enable_interface) $this->setInterface();
        }

        function setControls() {
            if ($this->related_tablename && $this->value)
                $this->controls .= '<a class="mr-icon pen" title="Перейти к редактированию элемента" href="otzoed.php?t='.$this->related_tablename.'&id='.$this->value.'"></a>';
            if ($this->show_widget && $this->value)
                $this->controls .= '<span class="toggle-widget mr-icon fil" title="Развернуть элемент"></span>';
        }

        function setInterface() {
            if ($this->readonly) $this->reload_interface = false;
            if ($this->enum) {
                $this->interface = '<select class="customenum" autocomplete="off">';
                foreach ($this->enum as $val => $text) {
                    $this->interface .= "<option value='$val'".($this->value == $val ? ' selected' : '').">$text</option>";
                }
                $this->interface .= '</select>';
                $this->reload_interface = false;
            }

            if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $this->value)) {
                $this->value = '<a href="https://www.nic.ru/whois/?query='.$this->value.'" target="_blank">'.$this->value.'</a>';
            } elseif (preg_match('/\.(jpg|jpeg|png)$/uis', $this->value)) {
                $this->interface = '<img src="'.$this->value.'">';
            } elseif (preg_match('/^https?:\/\//uis', $this->value)) {
                $this->interface = '<a href="'.$this->value.'" target="_blank">перейти</a>';
            }
        }

        function getWidget() {
            return "<div class='col-widget $this->name'>".$this->interface."</div>";
        }

        function showName() {
            $name = $this->show_alias ? $this->alias : $this->name;
            echo '<div class="col-name '.$this->name.'">'.$name.'</div>';
        }

        function showValue() {
            $class = strtolower(get_called_class());
            echo "<div class='col-display $this->name $class".($this->interface ? ' has-interface' : '')."'>";
            echo "<div class='col-control'><div class='col-value $this->name $class'>$this->value</div><div class='col-controls'>$this->controls</div></div>";
            if ($this->interface) echo "<div class='col-interface $this->name $class'>$this->interface</div><div style='clear: both'></div>";
            echo "</div>";
        }

        public $jsclass;
        protected $exportedObj;
        protected function prepareExport() {
            if (empty($this->jsclass)) $this->jsclass = get_called_class();
            $this->exportedObj = array();
            $this->exportedObj['name'] = $this->name;
            $this->exportedObj['type'] = $this->type;
            $this->exportedObj['size'] = $this->size;
            $this->exportedObj['readonly'] = $this->readonly;
            $this->exportedObj['unique'] = $this->unique;
            $this->exportedObj['reload_interface'] = $this->reload_interface;
            //if ($this->alias) $this->exportedObj['alias'] = $this->alias;
            if ($this->desc) $this->exportedObj['desc'] = $this->desc;
        }
        function exportJS() {
            $this->prepareExport();
            echo "col.$this->name = new $this->jsclass(".json_encode($this->exportedObj, JSON_UNESCAPED_UNICODE).");\n\t";
        }

        protected static $multivalue;
        static function setMultivalue($vals) {
            return; // типо виртуальная функция, которая понадобится в потомках класса
        }
        function getMultivalue() {
            $val = static::$multivalue[$this->value];
            if (!$val) {
                if ($this->name == 'id') $val = "<a href='otzoed.php?t={$this->tablename}&id={$this->value}'>{$this->value}</a>";
                if ($this->name == 'ip') $val = "<a href='https://www.nic.ru/whois/?query=$this->value' target='_blank'>$this->value</a>";
            }
            return $val ? $val : $this->value;
        }
        function showMultivalue() {
            $class = strtolower(get_called_class());
            $val = $this->getMultivalue();
            echo "<td class='$this->name $class'>$val</td>";
        }
    }

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

    class EnumColumn extends Column {
        public $jsclass = 'EnumColumn';
        function setInterface() {
            $this->interface = ''; // удаляем customenum
        }
        protected function prepareExport() {
            parent::prepareExport();
            if ($this->enum) $this->exportedObj['enums'] = $this->enum;
        }
    }

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

    class CatColumn extends Column {
        public $related_tablename = 'cats';
        public $show_widget = true;
        public $jsclass = 'CatColumn';
        function setInterface() {
            parent::setInterface();
            global $db;
            if ($this->value > 0) {
                $result = mysql_query("
                    SELECT c1.id as c1, c1.catname as n1, c2.id as c2, c2.catname as n2, c3.id as c3, c3.catname as n3
                    FROM $db.cats as c1
                    LEFT JOIN $db.cats as c2 ON c1.parent_id=c2.id
                    LEFT JOIN $db.cats as c3 ON c2.parent_id=c3.id
                    WHERE c1.id=$this->value
                    LIMIT 1
                ");
                if (mysql_num_rows($result) == 0) {
                    $this->interface = "<a href='#' class='cat-item' data-cid='0'>Категория не существует</a>";
                    return;
                }

                $r = mysql_fetch_assoc($result);

                if ($r['c3']) { $cat_keys[] = $r['c3']; $cat_names[] = "<a href='#' class='cat-item' data-cid='0'>{$r['n3']}</a>"; }
                if ($r['c2']) { $cat_keys[] = $r['c2']; $cat_names[] = "<a href='#' class='cat-item' data-cid='".($r['c3'] ? $r['c3'] : 0)."'>{$r['n2']}</a>"; }
                if ($r['c1']) { $cat_keys[] = $r['c1']; $cat_names[] = "<a href='#' class='cat-item' data-cid='{$r['c2']}'>{$r['n1']}</a>"; }

                $this->interface = '<span class="cat-icon img'.$cat_keys[0].'"></span>';
                $this->interface .= '<span class="cat-name">'.implode(' > ', $cat_names).'</span>';
            }

            $result = mysql_query("SELECT id, catname FROM $db.cats WHERE parent_id='$this->value' ORDER BY catname");
            if (mysql_num_rows($result) == 0) return;
            $this->interface .= '<div class="cat-scroll">';
            while ($r = mysql_fetch_array($result)) {
                if ($this->value == 0) $this->interface .= '<span class="cat-icon img'.$r['id'].'"></span>';
                $this->interface .= "<a href='#' class='cat-item' data-cid='{$r['id']}'>{$r['catname']}</a><br>";
            }
            $this->interface .= '</div>';
        }
        protected static $multivalue;
        static function setMultivalue($vals) {
            if (!$vals) return;
            if (count($vals) == 0) return;
            global $db;
            $vals = array_map('mysql_real_escape_string', $vals);
            $q = "
                SELECT c1.id as c1, c1.catname as n1, c2.id as c2, c2.catname as n2, c3.id as c3, c3.catname as n3
                FROM $db.cats as c1
                LEFT JOIN $db.cats as c2 ON c1.parent_id=c2.id
                LEFT JOIN $db.cats as c3 ON c2.parent_id=c3.id
                WHERE c1.id IN(".implode(', ', $vals).")
            ";
            $result = mysql_query($q);
            if (mysql_error()) throw new Exception(mysql_error().'<br>'.$q);
            while ($r = mysql_fetch_assoc($result)) {
                $cat = array();
                if ($r['c1']) { $cat_key = $r['c1']; $cat[] = "<a href='otzoed.php?t=cats&id={$r['c1']}'>{$r['n1']}</a>"; }
                if ($r['c2']) { $cat_key = $r['c2']; $cat[] = "<a href='otzoed.php?t=cats&id={$r['c2']}'>{$r['n2']}</a>"; }
                if ($r['c3']) { $cat_key = $r['c3']; $cat[] = "<a href='otzoed.php?t=cats&id={$r['c3']}'>{$r['n3']}</a>"; }
                //$cat[count($cat)-1] = '<br>'.$cat[count($cat)-1];
                static::$multivalue[$r['c1']] = '<span class="cat-icon img'.$cat_key.'"></span>';
                static::$multivalue[$r['c1']] .= '<div>'.implode('<br>', $cat).'</div>';
            }
        }
    }

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

    class RevColumn extends Column {
        public $related_tablename = 'reviews';
        public $show_widget = true;
        public $jsclass = 'InputColumn';
        function setInterface() {
            parent::setInterface();
            if ($this->value == 0) {
                $this->interface = 'Отзыв не задан';
                return;
            }
            global $db;
            $result = mysql_query("SELECT product_id, product, title, status FROM $db.reviews WHERE id=$this->value LIMIT 1");
            if (mysql_num_rows($result) == 0) {
                $this->interface = '<font color="red">Отзыв не существует</font>';
                return;
            }
            $row = mysql_fetch_assoc($result);
            $this->interface = '<a href="otzoed.php?t=products&id='.$row['product_id'].'">'.$row['product'].'</a> &ndash; ';
            $this->interface .= '<a href="otzoed.php?t=reviews&id='.$this->value.'">'.$row['title'].'</a> ['.$row['status'].']';
        }
        protected static $multivalue;
        static function setMultivalue($vals) {
            if (!$vals) return;
            if (count($vals) == 0) return;
            global $db;
            $vals = array_map('mysql_real_escape_string', $vals);
            $q = "SELECT id, product_id, title, product FROM $db.reviews WHERE id IN(".implode(', ', $vals).")";
            $result = mysql_query($q);
            if (mysql_error()) throw new Exception(mysql_error().'<br>'.$q);
            while ($row = mysql_fetch_assoc($result)) {
                static::$multivalue[$row['id']] = "<a href='otzoed.php?t=products&id={$row['product_id']}'>{$row['product']}</a><br><a href='otzoed.php?t=reviews&id={$row['id']}'>{$row['title']}</a>";
            }
        }
    }

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

    class ProdColumn extends Column {
        public $related_tablename = 'products';
        public $jsclass = 'InputColumn';
        public $show_widget = true;
        function setInterface() {
            parent::setInterface();
            if ($this->value == 0) {
                $this->interface = 'Объект не задан';
                return;
            }
            global $db;
            $result = mysql_query("SELECT name, hits_p, hits_r, image_url, reviews FROM $db.products WHERE id=$this->value LIMIT 1");
            if (mysql_num_rows($result) == 0) {
                $this->interface = '<font color="red">Объект не существует</font>';
                return;
            }
            $row = mysql_fetch_assoc($result);
            $this->interface = '<img style="margin-right: 4px; float: left;" src="'.str_replace('.png', '_m.png', $row['image_url']).'">';
            $this->interface .= '<a href="otzoed.php?t=products&id='.$this->value.'">'.$row['name'].'</a> ';
            $this->interface .= '['.$row['hits_p'].'/'.$row['hits_r'].']';
            if ($row['reviews']) $this->interface .= '<br>Отзывы: <a href="otzoed.php?t=reviews&product_id='.$this->value.'">'.$row['reviews'].'</a>';
        }
        protected static $multivalue;
        static function setMultivalue($vals) {
            if (!$vals) return;
            if (count($vals) == 0) return;
            global $db;
            $vals = array_map('mysql_real_escape_string', $vals);
            $q = "SELECT id, name, image_url FROM $db.products WHERE id IN(".implode(', ', $vals).")";
            $result = mysql_query($q);
            if (mysql_error()) throw new Exception(mysql_error().'<br>'.$q);
            while ($row = mysql_fetch_assoc($result)) {
                static::$multivalue[$row['id']] = '<img src="'.str_replace('.png', '_m.png', $row['image_url']).'">';
                static::$multivalue[$row['id']] .= "<a href='otzoed.php?t=products&id={$row['id']}'>{$row['name']}</a>";
            }
        }
    }

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

    class UserColumn extends Column {
        public $related_tablename = 'users';
        public $jsclass = 'InputColumn';
        public $show_widget = true;
        function setInterface() {
            parent::setInterface();
            if (!isset($this->value)) {
                $this->interface = 'NULL';
                return;
            }
            $val = (int)$this->value;
            if ($val < 0) {
                if ($this->name == 'status_m') {
                    $val = -$val;
                    $lock = true;
                } elseif ($this->name == 'referal') {
                    switch ($val) {
                        case -1: $this->interface = 'Зарегистрировался при написании коммента'; break;
                        case -2: $this->interface = 'Зарегистрировался при написании отзыва'; break;
                        case -3: $this->interface = 'Зарегистрировался как аноним'; break;
                        default: $this->interface = 'Неизвестное значение'; break;
                    }
                    return;
                } else {
                    $this->interface = 'Пользователь не задан';
                    return;
                }
            }
            global $admin_names;
            if ($admin_names[$val]) {
                if ($val>1) $this->interface = '<img style="margin-right: 4px; float: left; width: 60px;" src="http://'.DOMAIN.'/i/social/system.png">';
                $this->interface .= ($lock ? '<b>ЛОК</b> ' : '').'<a class="'.$admin_names[$val].'" href="otzoed.php?t='.$this->tablename.'&'.$this->name.'='.$val.'">'.$admin_names[$val].'</a>';
            } else {
                global $db;
                $result = mysql_query("SELECT login, karma, active, avatar, exten, ip FROM $db.users WHERE id=$val LIMIT 1");
                if (mysql_num_rows($result) == 0) {
                    $this->interface = ($lock ? '<b>ЛОК</b> ' : '').'<font color="red">Пользователь не существует</font>';
                    return;
                }
                $row = mysql_fetch_assoc($result);
                $revs = mysql_fetch_assoc(mysql_query("SELECT (SELECT COUNT(*) FROM $db.reviews WHERE user_id=$val AND status>2) as good, (SELECT COUNT(*) FROM $db.reviews WHERE user_id=$val AND status<0) as bad"));
                $this->interface = '<img style="margin-right: 4px; float: left; width: 60px;" src="'.($row['avatar']?$row['avatar']:'http://'.DOMAIN.'/i/blank.png').'">';
                $this->interface .= ($lock ? '<b>ЛОК</b> ' : '').'<a class="active'.$row['active'].'" href="otzoed.php?t=users&id='.$val.'">'.$row['login'].'</a><br>';
                $this->interface .= '<span style="font-size: 14px;">';
                $this->interface .= 'Карма: <span class="karma'.min(1, max(-1, $row['karma'])).'">'.$row['karma'].'</span><br>';
                $this->interface .= 'Отзывы: <a style="color: green;" href="otzoed.php?t=reviews&where=user_id='.$val.' AND status>2">'.$revs['good'].'</a> / ';
                $this->interface .= '<a style="color: red;" href="otzoed.php?t=reviews&where=user_id='.$val.' AND status<0">'.$revs['bad'].'</a><br>';
                if ($row['ip']) $this->interface .= '<a href="https://www.nic.ru/whois/?query='.$row['ip'].'" target="_blank">'.$row['ip'].'</a><br>';
                switch ((int)$row['active']) {
                    case -1: $this->interface .= '<font color="red">Забанен</font>'; break;
                    case 0: $this->interface .= 'Давно не писал'; break;
                    case 1: $this->interface .= 'Активен'; break;
                    case 2:
                    case 3:
                    case 4: $this->interface .= '<a style="color: green;" href="otzoed.php?t=products&official='.$val.'">Официал'.(str_repeat('+', $row['active']-2)).'</a>'; break;
                    default: $this->interface .= '<font color="red">active='.$row['active'].'</font>'; break;
                }
                $this->interface .= '<br>';
                if ($row['exten']) $this->interface .= ' <b>'.$row['exten'].'</b>';
                $this->interface .= '</span>';
            }
        }
        protected static $multivalue;
        static function setMultivalue($vals, $tablename, $colname) {
            if (!$vals) return;
            if (count($vals) == 0) return;
            global $admin_names, $db;
            $unset = array();
            foreach($vals as $k => $v) {
                $v = (int)$v;
                if ($v < 0) $v = -$v;
                if ($admin_names[$v]) {
                    //static::$multivalue[$v] = '<span class="'.$admin_names[$v].'">'.$admin_names[$v].'</span>';
                    static::$multivalue[$v] = '<img src="http://'.DOMAIN.'/i/social/system.png">';
                    static::$multivalue[$v] .= '<a class="admin '.$admin_names[$v].'" href="otzoed.php?t='.$tablename.'&'.$colname.'='.$v.'">'.$admin_names[$v].'</a>';
                    $unset[] = $k;
                }
                $vals[$k] = $v;
            }
            foreach ($unset as $u) unset($vals[$u]);
            if (count($vals) == 0) return;
            $q = "SELECT id, login, avatar FROM $db.users WHERE id IN(".implode(', ', $vals).")";
            $result = mysql_query($q);
            if (mysql_error()) throw new Exception(mysql_error().'<br>'.$q);
            while ($row = mysql_fetch_assoc($result)) {
                static::$multivalue[$row['id']] = '<img src="'.($row['avatar']?$row['avatar']:'http://'.DOMAIN.'/i/blank.png').'">';
                static::$multivalue[$row['id']] .= "<a href='otzoed.php?t=users&id={$row['id']}'>{$row['login']}</a>";
            }
        }
        function getMultivalue() {
            $val = $this->value;
            $lock = false;
            if ($val < 0) {
                $val = -$val;
                $lock = true;
            }
            $val = static::$multivalue[$val];
            if ($val && $lock) $val = '<b>ЛОК</b> '.$val;
            return $val ? $val : $this->value;
        }
    }

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

    class Table {
        public $tablename;
        public $col;
        public $ind;
        public $col_count;

        protected $show_aliases = true;
        protected $current_id;
        protected $result;
        protected $limit = 50;
        protected $offset = 0;
        protected $where;
        protected $num_rows;
        protected $num_pages;
        protected $current_page;
        protected $page_link;
        protected $query;
        
        protected static $view_single = 'default/view_single.php';
        protected static $view_multi  = 'default/view_multi.php';

        public static function getColHandler($props) {
            $handler = 'Column';
            $size = explode('(', $props['type']);
            $type = trim($size[0]);

            if ($props['name'] == 'review_id')
                $handler = 'RevColumn';
            elseif ($props['name'] == 'cat_id')
                $handler = 'CatColumn';
            elseif (in_array($props['name'], array('product_id', 'pid')))
                $handler = 'ProdColumn';
            elseif (in_array($props['name'], array('user_id', 'puser_id', 'official', 'status_m', 'referal', 'friend_id', 'from_user', 'admin_id')))
                $handler = 'UserColumn';
            elseif ($props['name'] == 'ip')
                $handler = 'Column';
            elseif ($type == 'enum')
                $handler = 'EnumColumn';
            
            return $handler;
        }

        function __construct($table_name) {
            if (empty($table_name)) throw new Exception("Не задано имя таблицы");
            global $db;
            $result = mysql_query("SHOW TABLES LIKE '".mysql_real_escape_string($table_name)."'");
            if (mysql_num_rows($result) == 0) throw new Exception("Таблица '$table_name' не найдна в базе '$db'");

            $this->tablename = $table_name;
            $this->col = new stdClass();
            $this->row = array();

            $result = mysql_query("SHOW INDEXES FROM $db.".mysql_real_escape_string($table_name));
            while ($row = mysql_fetch_assoc($result)) {
                $this->ind[$row['Column_name']]['key_name'] = $row['Key_name'];
                $this->ind[$row['Column_name']]['non_unique'] = $row['Non_unique'];
                $this->ind[$row['Column_name']]['seq_in_index'] = $row['Seq_in_index'];
            }

            $result = mysql_query("SHOW COLUMNS FROM $db.".mysql_real_escape_string($table_name));
            $this->col_count = mysql_num_rows($result);
            while ($row = mysql_fetch_assoc($result)) {
                $row['name'] = $row['Field'];
                unset($row['Field']);
                $row = array_combine(array_map('strtolower', array_keys($row)), array_values($row));

                $h = static::getColHandler($row);
                $newcol = new $h($row);
                $newcol->tablename = $table_name;
                $newcol->show_alias = $this->show_aliases;
                $newcol->unique = false;
                if (isset($this->ind[$row['name']]))
                    if ($this->ind[$row['name']]['non_unique'] == 0)
                        $newcol->unique = true;
                        
                $newcol->show_in_multiview = $this->col_count < 10 || $newcol->unique || $newcol->key == 'MUL';

                $this->col->{$row['name']} = $newcol;
            }
        }

        protected function query($where, $offset = 0, $limit = 50) {
            global $db;
            if (empty($where)) throw new Exception('Не задан индекс записи');
            $result = mysql_query("SELECT COUNT(*) FROM $db.$this->tablename WHERE $where");
            if (mysql_error()) throw new Exception(mysql_error().'<br>'.$q);
            $this->num_rows = (int)mysql_result($result, 0);
            if ($this->num_rows == 0) throw new Exception("Запись WHERE $where в таблице '$this->tablename' отсутствует");
            if ($this->num_rows > 1) {
                foreach ($this->col as $col)
                    $col->enable_interface = false;

                $this->num_pages = (int)($this->num_rows / $limit);
                $this->current_page = (int)($offset / $limit);
                $this->offset = $offset;
                $this->limit = $limit;
                $this->page_link = "otzoed.php?t=$this->tablename";
                if ($_GET) $this->page_link .= '&'.http_build_query($_GET);
            }

            $cols = $this->listCols();
            $q = "SELECT ".implode(', ', $cols)." FROM $db.$this->tablename WHERE $where ".($this->num_rows > 1 ? "ORDER BY {$cols[0]} DESC" : '')." LIMIT $this->offset, $this->limit";
            $this->query = $q;
            $this->result = mysql_query($q);
            if (mysql_error()) throw new Exception(mysql_error().'<br>'.$q);
            $this->page_rows = (int)mysql_num_rows($this->result);
            if ($this->page_rows == 0) throw new Exception("Запись WHERE $where в таблице '$this->tablename' отсутствует");
            if ($this->num_rows > 1) {
                $this->buildPostQuery();
            }
        }

        protected function fetchRow() {
            $row = mysql_fetch_assoc($this->result);
            if (!$row) return false;
            $current_id = array();
            foreach ($row as $k => $v) {
                if (!property_exists($this->col, $k)) continue;
                $this->col->$k->setValue($v);
                if ($this->col->$k->unique) {
                    if (!is_numeric($v)) $v = "'".mysql_real_escape_string($v)."'";
                    $current_id[] = mysql_real_escape_string($k).'='.$v;
                }
            }
            if (count($current_id)) $this->current_id = implode(' AND ', $current_id);
            return true;
        }

        function show($where, $offset = 0, $limit = 50) {
            $this->query($where, $offset, $limit);
            if ($this->num_rows == 1) {
                $this->fetchRow();
                include_once 'common/common_view_single.php';
            } else {
                include_once 'common/common_view_multi.php';
            }
        }

        function aliases($show = null) {
            if (!isset($show)) {
                return $this->show_aliases;
            }
            $this->show_aliases = (bool)$show;
            foreach ($this->col as $col) {
                $col->show_alias = $this->show_aliases;
            }
            return $this->show_aliases;
        }

        function listCols() {
            if ($this->num_rows == 1) {
                return array_keys(get_object_vars($this->col));
            } else {
                $out = array();
                foreach ($this->col as $col) {
                    if (!$col->show_in_multiview) continue;
                    $out[] = $col->name;
                }
                return $out;
            }
        }
        
        function listVals() {
            $out = array();
            foreach ($this->col as $col) {
                $val = $col->value;
                if ($this->num_rows == 1) {
                    $out[] = $val;
                } else {
                    if (!$col->show_in_multiview) continue;
                    $out[] = $col->getMultivalue();
                }
            }
            return $out;
        }

        function buildPostQuery() {
            $query = array();
            while ($row = mysql_fetch_assoc($this->result)) {
                foreach ($row as $k => $v) {
                    if (!property_exists($this->col, $k)) continue;
                    if (!$this->col->$k->show_in_multiview) continue;
                    $class = get_class($this->col->$k);
                    $query[$class][] = $v;
                }
            }
            mysql_data_seek($this->result, 0);
            foreach ($query as $class => $vals) {
                $class::setMultivalue(array_unique($vals), $this->tablename, $k);
            }
        }
    }
?>