<?php
    require_once '/2/www/begin2.php';

    $tablename = $_POST['tablename'];
    $where = array();
    foreach ($_POST['where'] as $k => $v) {
        $where[] = mysql_real_escape_string($k)."='".mysql_real_escape_string($v)."'";
    }
    $where = implode(' AND ', $where);

    if (empty($tablename))
        getOut('Не задано имя таблицы', true);

    if (empty($where))
        getOut("Невозможно отредактировать запись.<br>Не задан идентификатор записи, либо в таблице '$tablename' отсутствует уникальный индекс.", true);
    
    unset($_POST['tablename'], $_POST['where']);

    foreach ($_POST as $k => $v) {
        $val[] = mysql_real_escape_string($k)."='".mysql_real_escape_string($v)."'";
    }

    $q = "UPDATE $db.$tablename SET ".implode(', ', $val)." WHERE $where LIMIT 1";
    mysql_query($q);

    if (mysql_error()) {
        getOut(mysql_error()."<br>$q", true);
    } elseif (mysql_affected_rows() == 0) {
        getOut('Изменения не сохранены.<br>Скорее всего было изменено ключевое поле и требуется обновить страницу.', true);
    } else {
        getOut('Изменения сохранены: '.implode(', ', array_keys($_POST)), false);
    }



    //--------------------------------------------------------------------------
    //--------------------------------------------------------------------------

    function getOut($text, $error) {
        $out['text'] = $text;
        $out['error'] = $error;
        if ($error) {
            $out['delay'] = 0;
            $out['css']['backgroundColor'] = 'red';
        } else {
            $out['css']['backgroundColor'] = 'forestgreen';
        }
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        exit;
    }
?>