<?php
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

namespace SDM\Valitor\Model;

/**
 * Class ConstantConfig
 * @package SDM\Valitor\Model
 * Comment history constants
 */
abstract class ConstantConfig
{
    /* order history comments */
    const REDIRECT_TO_ALTAPAY        = 'Redirected to Valitor - Payment request ID: ';
    const NOTIFICATION_CALLBACK      = 'Notification callback from Valitor';
    const CONSUMER_CANCEL_PAYMENT    = 'Valitor - Consumer has canceled the payment';
    const DECLINED_PAYMENT_FORM      = 'Card declined, consumer redirected to the payment form';
    const DECLINED_PAYMENT_SECTION   = 'Card declined, consumer redirected to the payment section';
    const CONSUMER_PAYMENT_FAILED    = 'Valitor - Consumer has tried to pay but the payment failed';
    const PAYMENT_COMPLETE           = 'Payment is completed';
    const OK_CALLBACK                = 'OK callback from Valitor';
    const BROWSER_BK_BUTTON_COMMENT  = 'Payment failed! Consumer has pressed the back button from the payment page.';

    /* config url */
    const ALTAPAY_OK                 = 'sdmvalitor/index/ok';
    const ALTAPAY_FAIL               = 'sdmvalitor/index/fail';
    const ALTAPAY_REDIRECT           = 'sdmvalitor/index/redirect';
    const ALTAPAY_OPEN               = 'sdmvalitor/index/open';
    const ALTAPAY_NOTIFICATION       = 'sdmvalitor/index/notification';
    const ALTAPAY_CALLBACK           = 'sdmvalitor/index/callbackform';
    const VERIFY_ORDER               = 'sdmvalitor/index/verifyorder';

    /* error and success message */
    const ERROR                      = 'error';
    const SUCCESS                    = 'success';
    const AUTH_MESSAGE               = 'Could not authenticate with API';
    const ERROR_MESSAGE              = 'error occured';
    const BROWSER_BK_BUTTON_MSG      = 'Payment failed due to the browser back button usage!';
}
