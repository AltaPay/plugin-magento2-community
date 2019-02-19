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
        'SDM_Valitor/js/view/payment/method-renderer/terminal-abstract'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                terminal: '1'
            }
        });
    }
);
