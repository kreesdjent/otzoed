<?
    $cols = $this->listCols();
    if (!in_array('id', $cols)) array_unshift($cols, 'edit');
?>

<table class="data-table">
    <thead><tr><th><?=implode('</th><th>', $cols)?></th></tr></thead>
    <tbody>
    <?$odd = true; while ($this->fetchRow()) {?>
        <tr<?=$odd=!$odd?' class="odd"':''?>>
            <?if ($cols[0] == 'edit') {?><td><?if ($this->current_id) {?><a href="otzoed.php?t=<?=$this->tablename.'&'.str_replace(' AND ', '&', $this->current_id)?>">open</a><?} else echo 'no key';?></td><?}?>
            <?foreach ($cols as $c) { if ($c == 'edit') continue; $this->col->$c->showMultivalue(); }?>
        </tr>
    <?}?>
    </tbody>
</table>