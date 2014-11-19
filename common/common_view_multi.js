$(function() {
    $('.data-table td').each(function() {
        if (!isNaN($(this).text())) $(this).attr('align', 'right');
    });

    $('[title][title!=""]').mouseenter(function() {
        $('.plashka.hint').html($(this).attr('title').replace(/\n/g, '<br>')).show();
    }).mouseleave(function() {
        $('.plashka.hint').empty().hide();
    });
});