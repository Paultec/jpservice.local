$(function() {
    'use strict';

    $('form').on('submit', function(e) {
        e.preventDefault();

        $('.text-content').add('.image-content').empty();

        var url      = $('#uri').val()
            , target = $('.target').val();

        if (target == 'parse') {
            sendAjax('/', url, function(data) {
                var content = data.data;

                $(content).each(function(index, value) {
                    $('.product-image').find('img').attr('src', value.image);
                    $('.product-description').find('a').text(value.title).end().find('p').text(value.description);
                    $('.product-price').find('span').text(value.price);
                    $('.source').find('a').attr('href', value.source);

                    $('.result').removeClass('hide');
                });
            });
        } else if (target == 'pre-parse') {
            sendAjax('/app/pre-parse', url, function(data) {
                var textContent = data.textContent;
                var imgContent  = data.imgContent;

                $(textContent).each(function(index, value) {
                    $('.text-content').append('<li>' + value.text + '</li>');
                });

                $(imgContent).each(function(index, value) {
                    if (value.indexOf('http') != -1) {
                        $('.image-content').append('<li>' + value + '</li>');
                    }
                });
            });
        } else {
            return false;
        }
    });

    (function() {
        $('#uri').on('focus', function() {
            $(this).select();
        });
    })();

    function sendAjax(to, url, callback) {
        var   btn    = $('.submit-btn')
            , svg    = $('#container-svg');

        $.ajax({
            type: 'POST',
            url: to,
            data: { url: url },

            beforeSend: function() {
                btn.prop('disabled', true);
                svg.fadeIn();
            }
        })
            .done(callback)
            .fail(function() {})
            .always(function() {
                btn.prop('disabled', false);
                svg.fadeOut();
            });
    }

    $.material.init()
});