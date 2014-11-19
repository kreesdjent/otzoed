<?php
    $c = $this->col;

    $fields = array('id', 'status', 'mshow', 'unik', 'ip', 'postdate', 'chk_date', 'datamoder', 'status_m', 'frommoder', 'moder_id', 'yes', 'no', 'hits', 'money', 'rcost', 'video', 'youtube', 'chars');

    if ($this->tablename != 'reviews_edit')
        $remoder = (int)mysql_result(mysql_query("SELECT COUNT(*) FROM $db.reviews_edit WHERE id={$c->id->value} LIMIT 1"), 0);

    if ($c->unik->value[1]) {
        $result = mysql_query("SELECT url FROM $db.unik_log WHERE review_id='{$c->id->value}' LIMIT 6");
        $ulinks = array();
        while($row = mysql_fetch_assoc($result)) {
            $ulinks[] = "<a href='http://yandex.ru/yandsearch?text=\"".preg_replace("/\s+/", "+", $row['url'])."\"' target='_blank'>{$row['url']}</a>";
        }
        $ulinks = '<ol><li>'.implode('<li>', $ulinks).'</ol>';
    }
?>

<script type="text/javascript">
    var UnikColumn = function(obj) { InputColumn.call(this, obj); }
    UnikColumn.prototype = Object.create(InputColumn.prototype);
    UnikColumn.prototype.updateInterface = function() {
        InputColumn.prototype.updateInterface.call(this);
        $(this.i).find('.customenum').html('<option value="0'+this.val[1]+'">Да</option><option value="1'+this.val[1]+'">Нет</option>').val(this.val);
    }

    var StatusColumn = function(obj) {
        InputColumn.call(this, obj);
        this.desc = '<table>';
        for (var i in this.statuses) {
            if (i == 'x') continue;
            var sc = i;
            if (i == '<-100') sc = '-101';
            else if (i == '<0') sc = '-1';
            this.desc = this.desc + '<tr class="status'+sc+'"><td align="right"><b>'+i+'</b>: </td><td>'+this.statuses[i]+'</td></tr>';
        }
        this.desc = this.desc+'</table>';
    }
    StatusColumn.prototype = Object.create(InputColumn.prototype);
    StatusColumn.prototype.constructor = StatusColumn;
    StatusColumn.prototype.updateInterface = function() {
        $(this.i).find('.statuslegend')
            .attr('class', 'statuslegend '+this.style)
            .html(this.reason ? this.info+'<br><span style="font-size: small">'+this.reason+'</span>' : this.info);
    }
    StatusColumn.prototype.setVal = function(val) {
        if (val < -100) { this.style = 'status-101'; this.info = this.statuses['<-100']; this.reason = this.disaprove_reason[parseInt(val)+100]; }
        else if (val < 0) { this.style = 'status-1'; this.info = this.statuses['<0']; this.reason = this.disaprove_reason[val]; }
        else { this.style = 'status'+val; this.info = this.statuses[val]; this.reason = null; }
        if (typeof(this.info) == 'undefined') this.info = this.statuses.x;
        InputColumn.prototype.setVal.call(this, val);
    }

    $(function() {
        var html = col.status.info;
        if (col.status.reason) html += '<br>'+col.status.reason;
        $('.plashka.stts').addClass(col.status.style).html(html);
    });
</script>

<style type="text/css">
    .statuslegend { padding: 4px; border: 1px dotted silver; }
    .plashka.mshow1 { background-color: #afa; border-color: green; color: green; }
    .plashka.unik10 { background-color: yellow; border-color: brown; color: brown; margin-bottom: 0; }
    .plashka.unik01 { margin-bottom: 0; border-color: brown; color: brown; }
    .plashka.unik01+.ulinks { font-size: small; border-color: brown; color: gray; }
    .plashka.unik01+.ulinks a { color: gray; }
    .plashka.ulinks { border-top: 0; border-color: brown; color: brown; }
    .plashka.ulinks ol { padding-left: 20px; margin: 0; }
    .plashka.remoder { background-color: burlywood; border-color: brown; color: brown; }
</style>

<div class="plashka stts"></div>

<?if ($c->status->value != 11 && $c->frommoder->value = 3) {?>
<div class="plashka status11">Опубликован как экспертный</div>
<?}?>

<?if ($c->mshow->value == 1) {?>
<div class="plashka mshow1">На главной</div>
<?}?>

<?if ((int)$c->unik->value > 9) {?>
<div class="plashka unik10">Не уникальный</div><div class="plashka ulinks"><?=$ulinks?></div>
<?} elseif ((int)$c->unik->value > 0) {?>
<div class="plashka unik01">Не уникальный</div><div class="plashka ulinks"><?=$ulinks?></div>
<?}?>

<?if ($remoder) {?>
<div class="plashka remoder">Есть отредактированная версия</div>
<?}?>
<?if ($this->tablename == 'reviews_edit') {?>
<div class="plashka remoder">Отредактированная версия</div>
<?}?>
<?if ($this->tablename == 'reviews_drafts') {?>
<div class="plashka remoder">Черновик</div>
<?}?>

<div class="plashka actions"><input class="commit-changes" type="button" value="Сохранить изменения"></div>

<div class="plashka content">
    <table class="data-table <?=$this->tablename?>-table" data-name="<?=$this->tablename?>" style="float: left;">
        <tr><td><?=$c->cat_id->showName()?></td><td><?=$c->cat_id->showValue()?></td></tr>
        <tr><td><?=$c->product_id->showName()?></td><td><?=$c->product_id->showValue()?></td></tr>
        <tr><td><?=$c->user_id->showName()?></td><td><?=$c->user_id->showValue()?></td></tr>
        <tr><td><?=$c->ua_r->showName()?></td><td><?=$c->ua_r->showValue()?></td></tr>
        <tr><td><?=$c->title->showName()?></td><td><?=$c->title->showValue()?></td></tr>
        <tr><td><?=$c->plus->showName()?></td><td><?=$c->plus->showValue()?></td></tr>
        <tr><td><?=$c->minus->showName()?></td><td><?=$c->minus->showValue()?></td></tr>
        <tr><td><?=$c->body->showName()?></td><td style="width: 656px; font-size: 15px;"><?=$c->body->showValue()?></td></tr>
    </table>

    <table class="data-table <?=$this->tablename?>-table" data-name="<?=$this->tablename?>" style="float: left; width: 400px;">
        <col width="40%">
        <?foreach ($fields as $f) {?>
            <tr><td><?=$c->$f->showName()?></td><td><?=$c->$f->showValue()?></td></tr>
        <?}?>

        <?foreach ($c as $col => $v) { if (in_array($col, $fields)) continue; if (in_array($col, array('body', 'title', 'product', 'plus', 'minus', 'ua_r', 'cat_id', 'product_id', 'user_id'))) continue;?>
            <tr><td><?=$v->showName()?></td><td><?=$v->showValue()?></td></tr>
        <?}?>
    </table>
    
    <div style="clear: left; height: 10px;"></div>
</div>

<div class="plashka actions"><input class="commit-changes" type="button" value="Сохранить изменения"></div>