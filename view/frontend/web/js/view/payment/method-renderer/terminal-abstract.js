/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */

/*browser:true*/
/*global define*/

define(
    [

        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/customer-data',
        'SDM_Altapay/js/action/set-payment'
    ],
    function (Component, storage, Action) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SDM_Altapay/payment/terminal',
                terminal: '1'
            },

            redirectAfterPlaceOrder: false,

            placeOrder: function () {
                var self = this;
                if (self.validate()) {
                    self.selectPaymentMethod();
                    Action(
                        this.messageContainer,
                        this.terminal
                    );
                }
            }

        });
    }
);
