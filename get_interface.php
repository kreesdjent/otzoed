<?php
    require_once '/2/www/begin2.php';

    //!admin_id
    $admins['none'] = 0;
    $admins['people'] = 1;
    $admins['adam'] = 2;
    $admins['maver'] = 3;
    $admins['krees'] = 4;
    $admins['lena'] = 5;
    $admins['anna'] = 6;
    $admins['pavel'] = 7;
    $admins['tanya'] = 8;
    $admin_names = array_combine(array_values($admins), array_keys($admins));

    $tableclass = $_POST['tableclass'];
    $colname = $_POST['name'];

    try {
        require_once 'default/table.php';
        $custom = str_replace('table', '', strtolower($tableclass)).'/table.php';
        if (file_exists($custom)) require_once $custom;
        $table = new $tableclass($_POST['tablename']);
        $table->col->$colname->setValue($_POST['val']);
        $out['controls'] = $table->col->$colname->controls;
        $out['interface'] = $table->col->$colname->interface;
    } catch (Exception $e) {
        $out['interface'] = $e->getMessage();
        $out['controls'] = '';
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
?>