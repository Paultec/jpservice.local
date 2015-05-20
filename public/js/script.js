$(function() {
    'use strict';

    $('form').on('submit', function(e) {
        e.preventDefault();

        $('.text-content').add('.image-content').empty();

        var url   = $('#uri').val()
            , btn = $('.submit-btn')
            , svg = $('#container-svg');

        $.ajax({
            type: 'POST',
            url: '/',
            data: { url: url },

            beforeSend: function() {
                btn.prop('disabled', true);
                svg.fadeIn();
            }
        })
            .done(function(data) {
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
            })
            .fail(function() {})
            .always(function() {
                btn.prop('disabled', false);
                svg.fadeOut();
            });
    });

    (function() {
        $('#uri').on('focus', function() {
            $(this).select();
        });
    })();
});