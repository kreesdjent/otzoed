<?php
    class StatusColumn extends Column {
        public $alias = 'Статус';
        public $jsclass = 'StatusColumn';
        public $reload_interface = false;
        public $interface = '<div class="statuslegend"></div>';
        public $statuses = array(
            '<-100' => 'Заблокирован и удалён из Мои Отзывы',
              '<0' => 'Заблокирован',
                 0 => 'Ожидает обработки вотермарком',
                 1 => 'Ожидает проверки на уник',
                 2 => 'Висит в Предмодере',
                 3 => 'Висит в Предмодере, либо не индексируется',
                 4 => 'Одобрен',
                 5 => 'Одобрен + что-то неизвестное',
                10 => 'Одобрен и проверен вручную на плагиат',
                11 => 'Одобрен как экспертный',
                13 => 'Одобрен, но не показывать рекламу адсенс',
               'x' => 'Неизвестный статус отзыва'
        );
        public $disaprove_reason;
        function  __construct($props) {
            parent::__construct($props);
            global $db;
            $result = mysql_query("SELECT id, message FROM $db.disaprove_reason");
            while ($r = mysql_fetch_assoc($result)) {
                $this->disaprove_reason[$r['id']] = $r['message'];
            }
        }
        protected function prepareExport() {
            parent::prepareExport();
            $this->exportedObj['statuses'] = $this->statuses;
            $this->exportedObj['disaprove_reason'] = $this->disaprove_reason;
        }
        function showMultivalue() {
            $st = $sc = $this->value;
            if ($st < -100) { $st = '<-100'; $sc = '-101'; $sr = ":\n".$this->disaprove_reason[$this->value+100]; }
            elseif ($st < 0) { $st = '<0'; $sc = '-1'; $sr = ":\n".$this->disaprove_reason[$this->value]; }
            else $sr = '';
            $class = strtolower(get_called_class());
            echo "<td class='$this->name $class status$sc' title='{$this->statuses[$st]}$sr'><b>$this->value</b></td>";
        }
    }

    class ReviewsTable extends Table {

        public static $view_single = 'reviews/view_single.php';

        function __construct($t) {
            parent::__construct($t);

            $c = $this->col;

            $c->mshow->enum = array(1 => 'Да', 0 => 'Нет', -2 => 'Нет и отключить комменты');
            $c->unik->jsclass = 'UnikColumn';
            $c->unik->desc = 'Если первая цифра <b>0</b>, то отзыв оплачивается, если <b>1</b>, то нет.<br>Вторая цифра означает количество найденного плагиата.';

            $c->video->show_in_multiview = false;
            $c->frommoder->show_in_multiview = false;
            $c->yes->show_in_multiview = true;
            $c->no->show_in_multiview = true;
            $c->postdate->show_in_multiview = true;
            $c->hits->show_in_multiview = true;
            $c->rcost->show_in_multiview = true;
            $c->ip->show_in_multiview = true;
            $c->chars->show_in_multiview = true;

            $c->status_m->readonly = true;
            $c->hits->readonly = true;
            $c->money->readonly = true;
            $c->ua_r->readonly = true;
            $c->chk_date->readonly = true;

            $c->status_m->desc = 'Кто последний модерировал отзыв?';
            $c->datamoder->desc = 'Дата последней модерации отзыва';
            $c->postdate->desc = 'Дата публикации отзыва';
            $c->chk_date->desc = 'Дата проверки отзыва на уник';
            $c->yes->desc = 'Сколько людей лайкнули отзыв';
            $c->no->desc = 'Зависит от репутации автора и используется при сортировке';
            $c->frommoder->desc = 'Определяет экспертный отзыв, либо отправляет отзыв в Предмодер';
            $c->moder_id->desc = 'Отправляет отзыв в предмодер';

            $c->user_id->alias = 'Автор';
            $c->product_id->alias = 'Объект';
            $c->cat_id->alias = 'Категория';
            $c->title->alias = 'Название';
            $c->body->alias = 'Текст отзыва';
            $c->plus->alias = 'Плюсы';
            $c->minus->alias = 'Минусы';
            $c->unik->alias = 'Оплачивать';
            $c->price->alias = 'Стоимость';
            $c->year->alias = 'Год покупки';
            $c->used->alias = 'Время исп.';
            $c->rating0->alias = 'Общий рейтинг';
            $c->recommend->alias = 'Рекомендую?';
            $c->mshow->alias = 'На главной';
            $c->status_m->alias = 'Модератор';
            $c->hits->alias = 'Показов';
            $c->money->alias = 'Прибыль';
            $c->rcost->alias = 'Бонус';
            $c->chars->alias = 'Символов';
            $c->video->alias = 'Картинки';
            $c->ua_r->alias = 'ua';
        }
        
        function fetchRow() {
            $r = parent::fetchRow();
            if ($this->num_rows > 1) return $r;
            $c = $this->col;

            $c->unik->enum = array('0'.$c->unik->value[1] => 'Да', '1'.$c->unik->value[1] => 'Нет');
            $c->unik->setInterface();

            if (!$this->show_aliases) return $r;
            global $db;
            $result = mysql_query("SELECT rating1, rating2, rating3, rating4, rating5 FROM $db.cats WHERE id={$c->cat_id->value} LIMIT 1");
            if (mysql_num_rows($result) == 0) return $r;
            $ratings = mysql_fetch_assoc($result);
            foreach ($ratings as $k => $v) {
                if (empty($v)) break;
                $c->$k->alias = $v;
            }
            return $r;
        }

        public static function getColHandler($props) {
            $handler = parent::getColHandler($props);
            if ($props['name'] == 'status') $handler = 'StatusColumn';
            return $handler;
        }

    }
?>