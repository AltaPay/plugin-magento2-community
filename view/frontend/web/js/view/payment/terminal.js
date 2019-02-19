/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */

/*browser:true*/
/*global define*/

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'terminal1',
                component: 'SDM_Valitor/js/view/payment/method-renderer/terminal1-method'
            },
            {
                type: 'terminal2',
                component: 'SDM_Valitor/js/view/payment/method-renderer/terminal2-method'
            },
            {
                type: 'terminal3',
                component: 'SDM_Valitor/js/view/payment/method-renderer/terminal3-method'
            },
            {
                type: 'terminal4',
                component: 'SDM_Valitor/js/view/payment/method-renderer/terminal4-method'
            },
            {
                type: 'terminal5',
                component: 'SDM_Valitor/js/view/payment/method-renderer/terminal5-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
