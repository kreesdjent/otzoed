<script src="static/jquery-1.11.0.min.js" type="text/javascript"></script>
<script src="common/common_view_single.js" type="text/javascript"></script>
<link href="common/common_view_single.css" rel="stylesheet" type="text/css"/>

<script type="text/javascript">
    var DOMAIN = '<?=DOMAIN?>';
    var tablename = '<?=$this->tablename?>';
    var tableclass = '<?=get_class($this)?>';
    var col = {};
    
    function msg(options) {
        if (options == 'hide') $('.plashka.msg').stop(true, true).fadeOut('slow', function() { $(this).removeAttr('style'); });
        if (!options.text) return;
        if (typeof(options.delay) == 'undefined') options.delay = 5000;
        if (options.css) $('.plashka.msg').css(options.css); else $('.plashka.msg').removeAttr('style');
        $('.plashka.msg').html(options.text).stop(true, true).fadeIn('fast');
        if (options.delay > 0) $('.plashka.msg').delay(options.delay).fadeOut('slow', function() { $(this).removeAttr('style'); });
    }

    /*function aliasToggle() {
        for (var i in col) {
            if (!col[i].alias) continue;
            if ($(col[i].n).text() == col[i].alias) {
                $(col[i].n).text(col[i].name);
            } else {
                $(col[i].n).text(col[i].alias);
            }
        }
    }*/

    $.fn.oneByOne = function(delay, func, callback) {
        var e = $(this);
        var n = $(e).length;
        var i = 0;
        var f = function() {
            if (i < n) {
                if (func) $.proxy(func, $(e).eq(i))();
                setTimeout(function() { i++; f(); }, delay);
            } else {
                if (callback) $.proxy(callback, e)();
            }
        }
        f();
    }

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

        $('[title][title!=""]').mouseenter(function() {
            $('.plashka.hint').html($(this).attr('title').replace(/\n/g, '<br>')).show();
        }).mouseleave(function() {
            $('.plashka.hint').empty().hide();
        });

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