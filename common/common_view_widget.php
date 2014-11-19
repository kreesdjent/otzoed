<? $this->fetchRow(); ?>

<script type="text/javascript">
    var DOMAIN = '<?=DOMAIN?>';
    var widget_tablename = '<?=$this->tablename?>';
    var widget_tableclass = '<?=get_class($this)?>';
    var widget_col = {};
    

    $(function() {
        <?foreach ($this->col as $col) $col->exportJS();?>

        var unique = false;
        for (var u in col) {
            if (col[u].unique) { unique = true; break; }
        }
        if (!unique) {
            $('.plashka.actions').hide();
            msg({text: 'Невозможно редактировать запись.<br>В таблице "'+tablename+'" отсутствует уникальный индекс.', delay: 10000, css: {backgroundColor: 'darkorange'}});
            return;
        }

        $('.commit-changes').click(function() {
            $(this).prop('disabled', true);
            msg({text:'<img src="http://otzovik.com/img/main/loader16t.gif"> Обработка...', delay: 0});
            var where = {};
            var changes = {};
            var has_changes = false;
            for (var c in col) {
                if (col[c].unique) where[c] = col[c].old;
                if (col[c].selected) col[c].blur();
                if (!col[c].changed) continue;
                changes[col[c].name] = col[c].val;
                has_changes = true;
            }
            if (!has_changes) {
                msg({text:'Нет изменений...'});
                $(this).prop('disabled', false);
                return;
            }
            changes.tablename = tablename;
            changes.where = where;
            $.ajax({
                url: 'update.php',
                data: changes,
                dataType: 'JSON',
                type: 'POST',
                success: function(data) {
                    if (!data.error) {
                        for (var e in col) {
                            col[e].changed = false;
                            col[e].old = col[e].val;
                        }
                        $('.col-value.changed').oneByOne(50, function() { $(this).fadeOut('fast', function() { $(this).removeClass('changed'); }).fadeIn('fast'); });
                    }
                    msg(data);
                    $('.commit-changes').prop('disabled', false);
                },
                error: function(error) {
                    msg({text: JSON.stringify(error), delay: 0, css: { backgroundColor: 'red' }});
                    $('.commit-changes').prop('disabled', false);
                }
            });
        });
    });
</script>

<? include_once static::$view_single; ?>

<div style="position: fixed; bottom: 0; overflow: hidden;">
    <div class="plashka hint"></div>
    <div class="plashka msg"></div>
</div>