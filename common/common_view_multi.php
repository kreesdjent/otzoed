<script src="static/jquery-1.11.0.min.js" type="text/javascript"></script>
<script src="common/common_view_multi.js" type="text/javascript"></script>
<link href="common/common_view_multi.css" rel="stylesheet" type="text/css"/>

<script type="text/javascript">
    var DOMAIN = '<?=DOMAIN?>';
    var tablename = '<?=$this->tablename?>';
</script>

<?
if ($this->num_rows > $this->limit) {
    $page_limit = 15;
    $half_limit = floor($page_limit/2);
    $b = max(0, min($this->current_page-$half_limit, $this->num_pages-$page_limit+1));
    $e = min($this->num_pages, $b+$page_limit-1);
    if ($b > 0) $pages_html .= "<a class='page' href='{$this->page_link}'>0</a> ... ";
    for ($i=$b; $i<=$e; $i++) {
        if ($i == $this->current_page) $pages_html .= "<font class='page current'>$i</font>";
        else {
            $next = $i*$this->limit;
            $next = $next ? '&_off_='.$next : '';
            $pages_html .= "<a class='page' href='{$this->page_link}{$next}'>$i</a>";
        }
    }
    if ($e < $this->num_pages) $pages_html .= " ... <a class='page' href='{$this->page_link}&_off_=".($this->num_pages*$this->limit)."'>$this->num_pages</a>";
?>

    <div class="plashka query"><?=$this->query?></div>
    <div class="plashka numrows">Найдено записей: <b><?=$this->num_rows?></b> | Показано: <b><?=$this->page_rows?></b></div>

    <div class="plashka paging">
        Страницы: <?=$pages_html?>
    </div>

    <? include_once static::$view_multi; ?>

    <div class="plashka paging">
        Страницы: <?=$pages_html?>
    </div>

<?} else {?>

    <div class="plashka query"><?=$this->query?></div>
    <div class="plashka numrows">Найдено записей: <b><?=$this->num_rows?></b></div>
    <? include_once static::$view_multi; ?>

<?}?>

<div style="position: fixed; bottom: 0; overflow: hidden;">
    <div class="plashka hint"></div>
</div>