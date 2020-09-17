/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require(['jquery'], function ($) {
    var url = $(".table-wrapper").data('url');
    $('.token-delete').on('click', function () {
        var token_id = $(this).data('token-id');
        var data = {token_id: token_id, action: "delete"};
        var current = $(this);
        $.ajax({
            type: 'POST',
            url: url,
            data: data,
            beforeSend: function () {
                $("#token-custom-name-status-" + token_id).addClass('updating');
                var tr = $(this).closest('tr');
                $('button.token-delete', tr).attr('disabled', true);
                $('input.token-custom-name', tr).attr('disabled', true);
            }
        }).done(function (data) {
            if (data.status.status == "deleted") {
                var whichtr = $(current).closest("tr");
                whichtr.hide('slow', function () {
                    whichtr.remove();
                });
            }
        });
    });

    $('.token-primay-selection').on('change', function () {
        var token_id = $(this).data('token-id');
        var data = {token_id: token_id, action: "primary"};
        $.ajax({
            type: 'POST',
            url: url,
            data: data,
            beforeSend: function () {
                $("#token-custom-name-status-" + token_id).addClass('updating');
                var tr = $(this).closest('tr');
                $('button.token-delete', tr).attr('disabled', true);
                $('input.token-custom-name', tr).attr('disabled', true);
                $('#my-tokens-table input.radio[primary-token]').attr('disabled', true);
            }
        }).done(function (data) {
            $(this).prop("disabled", false);
            if (data.status.status == 'ok' || data.status.status == 'updated') {
                $("#token-custom-name-status-" + token_id).addClass('updated');
                setInterval(function () {
                    $("#token-custom-name-status-" + token_id).removeClass('updated');
                }, 2000);
            } else if (data.status.status == 'error') {
                $("#token-custom-name-status-" + token_id).addClass('error');
                setInterval(function () {
                    $("#token-custom-name-status-" + token_id).removeClass('error');
                }, 2000);
            }
        }).always(function () {
            $("#token-custom-name-status-" + token_id).removeClass('updating');
            var tr = $(this).closest('tr');
            $('button.token-delete', tr).attr('disabled', false);
            $('input.token-custom-name', tr).attr('disabled', false);
            $('#my-tokens-table input.radio[primary-token]').attr('disabled', false);
        }).error(function () {
            $("#token-custom-name-status-" + token_id).addClass('error');
            setInterval(function () {
                $("#token-custom-name-status-" + token_id).removeClass('error');
            }, 2000);
        });
    });
});