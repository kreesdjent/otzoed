<div class="plashka actions"><input class="commit-changes" type="button" value="Сохранить изменения"></div>

<div class="plashka content">
    <table class="data-table <?=$this->tablename?>-table" data-name="<?=$this->tablename?>">
        <?foreach ($this->col as $col => $v) {?>
            <tr><td><?=$v->showName()?></td><td><?=$v->showValue()?></td></tr>
        <?}?>
    </table>
</div>

<div class="plashka actions"><input class="commit-changes" type="button" value="Сохранить изменения"></div>