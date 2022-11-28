/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*browser:true*/
/*global define*/

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ], function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'terminal1',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal1-method'
            },
            {
                type: 'terminal2',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal2-method'
            },
            {
                type: 'terminal3',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal3-method'
            },
            {
                type: 'terminal4',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal4-method'
            },
            {
                type: 'terminal5',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal5-method'
            },
            {
                type: 'terminal6',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal6-method'
            },
            {
                type: 'terminal7',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal7-method'
            },
            {
                type: 'terminal8',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal8-method'
            },
            {
                type: 'terminal9',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal9-method'
            },
            {
                type: 'terminal10',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal10-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
