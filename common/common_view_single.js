var EMPTY = '[Пусто]';

var interfaceCache = {};

var Column = function(obj) {
    if ($('.'+obj.name).length == 0) return;
    var thiscol = this;
    for(var i in obj) this[i] = obj[i];
    this.n = $('.col-name.'+this.name);
    this.d = $('.col-display.'+this.name);
    this.c = this.d.find('.col-controls');
    this.v = this.d.find('.col-value');
    this.i = this.d.find('.col-interface');
    if (this.size) $(this.v).css({maxWidth: Math.max(this.size, 2)+'ch'});
    this.old = $(this.v).html();
    if (this.i.length) {
        this.i.data('val', this.old);
        if (this.reload_interface)
            this.pushInterfaceCache(this.old, {'interface': this.i.html(), 'controls': this.c.html()});
        if (this.i.find('select.customenum').length) {
            this.i.on('change', 'select.customenum', function() {
                thiscol.setVal($(this).val());
            });
        }
        this.d.on('click', '.toggle-widget', function() {
            if ($(thiscol.w).length) {$(thiscol.w).remove();thiscol.w = null;return;}
            $(this).hide().after('<img class="col-controls-loader" src="http://'+DOMAIN+'/img/main/loader16t.gif">');
            $.ajax({
                url: 'get_widget.php',
                data: {tablename: tablename, tableclass: tableclass, name: thiscol.name, val: thiscol.val},
                dataType: 'text',
                type: 'POST',
                success: function(data) {
                    $('body').append(data);
                    thiscol.w = $('body').find('.col-widget.'+thiscol.name);
                    thiscol.w.css({top:'50%',left:'50%',margin:'-'+($(thiscol.w).height() / 2)+'px 0 0 -'+($(thiscol.w).width() / 2)+'px'});
                    thiscol.d.find('.toggle-widget').show().next().remove();
                },
                error: function(error) {
                    msg({text: JSON.stringify(error), css: {backgroundColor: 'red'}});
                }
            });
        });
    }
    this.setVal(this.old);
    this.selected = false;
    if (!this.readonly) {
        $(this.n).add(this.v).click(function(e) {
            if (!thiscol.selected) thiscol.focus(e);
        });
        $(document).click(function(e) {
            if (!thiscol.selected) return;
            if ($(e.target).is($(thiscol.n))) return; // эти проверки нужны чтобы поле не блюрилось при клике по нему самому и его друзьям
            if ($(e.target).is($(thiscol.v))) return;
            if ($(e.target).closest('.col-name').is($(thiscol.n))) return;
            if ($(e.target).closest('.col-value').is($(thiscol.v))) return;
            if ($(e.target).closest('body').length == 0) return; // если кликнутый элемент исчез со страницы, значит мы его только что заменили на инпут в событии focus
            thiscol.blur();
        });
    } else {
        $(this.n).add(this.v).addClass('readonly');
    }
    $(this.n).add(this.v).mouseenter(function() {thiscol.mouseenter();})
                         .mouseleave(function() {thiscol.mouseleave();});
}
Column.prototype = {
    focus: function() {
        this.selected = true;
        $(this.n).add(this.v).add(this.d).addClass('selected');
    },
    blur: function() {
        this.selected = false;
        $(this.n).add(this.v).add(this.d).removeClass('selected').removeClass('hover');
    },
    setVal: function(val) {
        //if (this.readonly) return;
        this.val = val;
        if (this.val === '') {
            this.empty = true;
            $(this.v).addClass('empty');
            $(this.v).html(EMPTY);
        } else {
            this.empty = false;
            $(this.v).removeClass('empty');
            $(this.v).html(this.val);
        }
        if (this.val == this.old) {
            this.changed = false;
            $(this.v).removeClass('changed');
        } else {
            this.changed = true;
            $(this.v).addClass('changed');
        }
        this.updateInterface();
    },
    updateInterface: function() {
        var thiscol = this;
        if ($(this.i).length == 0) return;
        if ($(this.i).data('val') == this.val) return;
        $(this.i).data('val', this.val);
        if ($(this.i).find('.customenum').length) {
            $(this.i).find('.customenum').val(this.val);
        }
        if (!this.reload_interface) return;
        var cache = this.pullInterfaceCache();
        if (cache != false) {
            $(this.c).html(cache['controls']);
            $(this.i).html(cache['interface']);
            return;
        }
        $(this.i).add(this.c).fadeTo('fast', 0.3);
        $.ajax({
            url: 'get_interface.php',
            data: {tablename: tablename, tableclass: tableclass, name: thiscol.name, val: thiscol.val},
            dataType: 'JSON',
            type: 'POST',
            success: function(data) {
                thiscol.pushInterfaceCache(thiscol.val, data);
                $(thiscol.c).html(data['controls']).fadeTo('fast', 1);
                $(thiscol.i).html(data['interface']).fadeTo('fast', 1);
            },
            error: function(error) {
                $(thiscol.i).add(thiscol.c).empty().fadeTo('fast', 1);
                msg({text: JSON.stringify(error), css: {backgroundColor: 'red'}});
            }
        });
    },
    pushInterfaceCache: function(val, data) {
        var query = {tablename: tablename, tableclass: tableclass, name: this.name, val: val};
        interfaceCache[$.param(query)] = data;
    },
    pullInterfaceCache: function() {
        var query = {tablename: tablename, tableclass: tableclass, name: this.name, val: this.val};
        var cache = interfaceCache[$.param(query)];
        return typeof(cache) != 'undefined' ? cache : false;
    },
    mouseenter: function() {
        $(this.n).add(this.v).add(this.d).addClass('hover');
        if (typeof(this.desc) == 'undefined') return;
        $('.plashka.hint').html('<b>'+tablename+'.'+this.name+'</b><br>'+this.desc).show();
    },
    mouseleave: function() {
        $(this.n).add(this.v).add(this.d).removeClass('hover');
        if (typeof(this.desc) == 'undefined') return;
        $('.plashka.hint').empty().hide();
    }
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

var InputColumn = function(obj) {
    Column.call(this, obj);
    if (this.readonly) return;
    var thiscol = this;
    $(this.v).on('keydown', 'input', function(e) {
        if (e.which == 9) { // tab
            thiscol.blur();
            var next = $('.col-value').eq($('.col-value').index(thiscol.v)+1);
            while (next.hasClass('readonly')) next = $('.col-value').eq($('.col-value').index(next)+1);
            next.click();
            return false;
        } else if (e.which == 27) { // esc
            $(thiscol.v).find('input').val(thiscol.old);
        } else if (e.which == 13) { // enter
        } else return true;
        thiscol.blur();
        return false;
    });
}
InputColumn.prototype = Object.create(Column.prototype);
InputColumn.prototype.constructor = InputColumn;
InputColumn.prototype.focus = function() {
    Column.prototype.focus.call(this);
    var input = $('<input type="text">').val(this.val).css({width: $(this.v).width()});
    if (this.size > 0) input.attr('maxlength', Math.max(this.size, 2));
    $(this.v).html(input);
    input.focus().select();
}
InputColumn.prototype.blur = function() {
    Column.prototype.blur.call(this);
    this.setVal($(this.v).find('input').val());
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

var TextColumn = function(obj) {
    Column.call(this, obj);
    if (this.readonly) return;
    var thiscol = this;
    $(this.v).on('keydown', 'textarea', function(e) {
        if (e.which == 9) { // tab
            thiscol.blur();
            var next = $('.col-value').eq($('.col-value').index(thiscol.v)+1);
            while (next.hasClass('readonly')) next = $('.col-value').eq($('.col-value').index(next)+1);
            next.click();
            return false;
        } else if (e.which == 27) $(thiscol.v).find('textarea').val(thiscol.old);
        else return true;
        thiscol.blur();
        return false;
    });
}
TextColumn.prototype = Object.create(Column.prototype);
TextColumn.prototype.constructor = TextColumn;
TextColumn.prototype.focus = function() {
    Column.prototype.focus.call(this);
    var input = $('<textarea />').val(this.val).css({width: $(this.v).width()});
    $(this.v).html(input);
    input.height(input.prop('scrollHeight')).focus();
}
TextColumn.prototype.blur = function() {
    Column.prototype.blur.call(this);
    this.setVal($(this.v).find('textarea').val());
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

var EnumColumn = function(obj) {
    Column.call(this, obj);
    if (this.readonly) return;
    var thiscol = this;
    $(this.v).on('change', 'select', function() {
        thiscol.blur();
    }).on('keydown', 'select', function(e) {
        if (e.which == 9) { // tab
            thiscol.blur();
            var next = $('.col-value').eq($('.col-value').index(thiscol.v)+1);
            while (next.hasClass('readonly')) next = $('.col-value').eq($('.col-value').index(next)+1);
            next.click();
            return false;
        } else if (e.which == 27) $(thiscol.v).find('select').val(thiscol.old);
        else return true;
        thiscol.blur();
        return false;
    });
}
EnumColumn.prototype = Object.create(Column.prototype);
EnumColumn.prototype.constructor = EnumColumn;
EnumColumn.prototype.focus = function() {
    Column.prototype.focus.call(this);
    var input = $('<select />');
    for (var i in this.enums) {
        input.append('<option value="'+this.enums[i]+'">'+this.enums[i]+'</option>');
    }
    input.val(this.val);
    $(this.v).html(input);
    input.focus();
}
EnumColumn.prototype.blur = function() {
    Column.prototype.blur.call(this);
    this.setVal($(this.v).find('select').val());
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

var CatColumn = function(obj) {
    InputColumn.call(this, obj);
    if (this.readonly) return;
    var thiscol = this;
    $(this.i).on('click', '.cat-item', function() {
        var cid = parseInt($(this).data('cid'));
        thiscol.setVal(isNaN(cid) ? 0 : cid);
        return false;
    });
}
CatColumn.prototype = Object.create(InputColumn.prototype);
CatColumn.prototype.constructor = CatColumn;

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

var DateColumn = function(obj) {InputColumn.call(this, obj);}
DateColumn.prototype = Object.create(InputColumn.prototype);
DateColumn.prototype.constructor = DateColumn;
DateColumn.prototype.focus = function() {
    InputColumn.prototype.focus.call(this);
    var thiscol = this;
    var year = new Date().getFullYear();
    var input = $('<div class="date-input"><div class="date-input-head">Год:</div><div class="date-input-body"></div></div>').data('page', 0);
    for (var y=2010; y<=year; y++) input.find('.date-input-body').append('<b>'+y+'</b>');
    $(this.v).append(input);

    input.on('click', 'b', function(e) {
        var i = $(thiscol.v).find('input');
        var p = $(this).closest('.date-input');

        var page = $(p).data('page');
        var selected = i.val().split(/\s|-|:/);
        selected[page] = $(this).text();
        for (var j=0; j<6; j++) if (!selected[j]) selected[j] = '00';
        if  (thiscol.type != 'date') {
            i.val(selected[0]+'-'+selected[1]+'-'+selected[2]+' '+selected[3]+':'+selected[4]+':'+selected[5]);
            if (page > 4) {thiscol.blur();e.stopPropagation();return;}
        } else {
            i.val(selected[0]+'-'+selected[1]+'-'+selected[2]);
            if (page > 1) {thiscol.blur();e.stopPropagation();return;}
        }       

        var xyu = [ ['Месяц:', 1, 12], ['День:', 1, 31], ['Час:', 0, 23], ['Минута:', 0, 59], ['Секунда:', 0, 59] ];

        p.find('.date-input-head').html(xyu[page][0]);
        var b = $(this).closest('.date-input-body');
        b.empty();
        for (var l=xyu[page][1]; l<=xyu[page][2]; l++) b.append('<b>'+(l<10 ? '0'+l : l)+'</b>');

        p.data('page', page+1);
    });
}