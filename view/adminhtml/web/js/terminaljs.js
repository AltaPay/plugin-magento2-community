/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require(['jquery'], function ($) {
    $(document).on('change', '.altapay-terminal-name', function () {
        var terminal = this.value;
        var element = document.getElementById('terminal_data_obj');
        if (element != null) {
            var str = JSON.parse(element.value);
            var disableField = true;
            str.forEach(function (data, index) {
                if (data.title == terminal) {
                    if (data.creditCard == true) {
                        disableField = false;
                    }
                }
            });

            if (disableField == true) {
                $(this).closest('.section-config').find('.altapay-terminal-token-control').attr('readonly', true).css('pointer-events', 'none').val(0);
            } else {
                $(this).closest('.section-config').find('.altapay-terminal-token-control').attr('readonly', false).css('pointer-events', 'all');
            }
        }
    });

    $(document).on('click', '#payment_other_sdm_altapay_config_terminal1-head,#payment_other_sdm_altapay_config_terminal2-head,#payment_other_sdm_altapay_config_terminal3-head,#payment_other_sdm_altapay_config_terminal4-head,#payment_other_sdm_altapay_config_terminal5-head', function () {
        var terminalSelector = jQuery(this).closest('.section-config').find('.altapay-terminal-name');
        var terminal = $(terminalSelector).val();
        var element = document.getElementById('terminal_data_obj');
        if (element != null) {
            var str = JSON.parse(element.value);
            var disableField = true;
            str.forEach(function (data, index) {
                if (data.title == terminal) {
                    if (data.creditCard == true) {
                        disableField = false;
                    }
                }
            });

            if (disableField == true) {
                $(terminalSelector).closest('.section-config').find('.altapay-terminal-token-control').attr('readonly', true).css('pointer-events', 'none').val(0);
            } else {
                $(terminalSelector).closest('.section-config').find('.altapay-terminal-token-control').attr('readonly', false).css('pointer-events', 'all');
            }
        }
    });
});