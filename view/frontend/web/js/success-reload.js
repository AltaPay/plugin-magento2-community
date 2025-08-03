/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require(['Magento_Customer/js/customer-data'], function (customerData) {
    'use strict';
    setTimeout(function () {
        customerData.reload(['cart', 'checkout-data'], true);
    }, 1000);
});
