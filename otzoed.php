<?php
    ini_set('display_errors', 1);
    error_reporting(E_WARNING);

    require_once '/2/admin/otzovik/begin_admin.php';

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

    $t = $_GET['t'];
    $off = (int)$_GET['_off_'];
    if (empty($t)) { echo 'Не задано имя таблицы'; exit; }

    unset($_GET['t'], $_GET['_off_']);
    
    if ($_GET['where']) {
        $where = $_GET['where'];
    } else {
        $where = array();
        foreach ($_GET as $k => $v) {
            $where[] = mysql_real_escape_string($k)."='".mysql_real_escape_string($v)."'";
        }
        $where = implode(' AND ', $where);
    }
    if (empty($where)) $where = '1';

    try {
        require_once 'default/table.php';
        if (in_array($t, array('reviews', 'reviews_edit'))) {
            require_once 'reviews/table.php';
            $table = new ReviewsTable($t);
        } else {
            $table = new Table($t);
        }
        $table->show($where, $off);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
?>
<style type="text/css">
    .karma1 { color: green; }
    .karma1:before { content: '+'; }
    .karma-1 { color: red; }

    .people { color: gray; }
    .adam { color: orange; }
    .maver { color: red; }
    .lena { color: fuchsia; }
    .anna { color: deepskyblue; }
    .krees { color: purple; }
    .tanya { color: darkgreen; }
    .pavel { color: teal; }

    .status-101 { background-color: pink; border-color: red; color: red; }
    .status-1 { background-color: pink; border-color: red; color: red; }
    .status0 { background-color: lemonchiffon; border-color: gray; color: gray; }
    .status1 { background-color: lemonchiffon; border-color: black; color: black; }
    .status2 { background-color: khaki; border-color: black; color: black; }
    .status3 { background-color: khaki; border-color: green; color: green; }
    .status4 { background-color: white; border-color: green; color: green; }
    .status5 { background-color: white; border-color: green; color: green; }
    .status10 { background-color: white; border-color: green; color: green; }
    .status11 { background-color: #aff; border-color: teal; color: teal; }
    .status13 { background-color: plum; border-color: indigo; color: indigo; }

    .mr-icon { display: inline-block; vertical-align: middle; width: 22px; height: 22px; margin: 1px 1px 0 0; cursor: pointer; background-image: url(http://otzovik.com/img/main/my_reviews_icons.png); }
    .mr-icon:last-child { margin-right: 0; }
    .mr-icon:hover { box-shadow: 0px 0px 3px rgba(0, 0, 0, 0.3); }
    .mr-icon.pen { background-position: 0; }
    .mr-icon.inf { background-position: -22px; }
    .mr-icon.pho { background-position: -44px; }
    .mr-icon.gps { background-position: -66px; }
    .mr-icon.fil { background-position: -88px; }
    .mr-icon.del { background-position: -110px; }
    .mr-icon.cat { background-position: -132px; }
    .mr-icon.dup { background-position: -154px; }
    .mr-icon.pls { background-position: -176px; }
    .mr-icon.mns { background-position: -198px; }
    .mr-icon.clr { background-position: -220px; }
    
    .cat-icon { display: inline-block; width: 26px; height: 26px; vertical-align: top; margin-right: 8px; background-image: url(http://otzovik.com/img/main/icons_catalog_26px.png); }
    .cat-icon.img1   { background-position: 0 0; }
    .cat-icon.img860 { background-position: -26px 0; }
    .cat-icon.img47  { background-position: -52px 0; }
    .cat-icon.img509 { background-position: -78px 0; }
    .cat-icon.img50  { background-position: -104px 0; }
    .cat-icon.img288 { background-position: -130px 0; }
    .cat-icon.img56  { background-position: -156px 0; }
    .cat-icon.img492 { background-position: -182px 0; }
    .cat-icon.img2   { background-position: -208px 0; }
    .cat-icon.img14  { background-position: -234px 0; }
    .cat-icon.img54  { background-position: -260px 0; }
    .cat-icon.img601 { background-position: -286px 0; }
    .cat-icon.img57  { background-position: -312px 0; }
    .cat-icon.img55  { background-position: -338px 0; }
    .cat-icon.img51  { background-position: -364px 0; }
    .cat-icon.img53  { background-position: -390px 0; }
    .cat-icon.img123 { background-position: -416px 0; }
    .cat-icon.img5   { background-position: -442px 0; }
    .cat-icon.img1200{ background-image: none; }
    .cat-icon.img11  { background-image: none; }
</style>