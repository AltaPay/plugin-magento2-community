<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

/**
 * Class ConstantConfig
 * Comment history constants
 */
abstract class ConstantConfig
{
    /* order history comments */
    const REDIRECT_TO_ALTAPAY = 'Redirected to Altapay - Payment request ID: ';
    const NOTIFICATION_CALLBACK = 'Notification callback from Altapay';
    const CONSUMER_CANCEL_PAYMENT = 'Altapay - Consumer has canceled the payment';
    const DECLINED_PAYMENT_FORM = 'Card declined, consumer redirected to the payment form';
    const DECLINED_PAYMENT_SECTION = 'Card declined, consumer redirected to the payment section';
    const CONSUMER_PAYMENT_FAILED = 'Altapay - Consumer has tried to pay but the payment failed';
    const PAYMENT_COMPLETE = 'Payment is completed';
    const OK_CALLBACK = 'OK callback from Altapay';
    const CARDWALLET_CALLBACK = 'CardWallet callback from Altapay';

    /* config url */
    const ALTAPAY_OK = 'sdmaltapay/index/ok';
    const ALTAPAY_FAIL = 'sdmaltapay/index/fail';
    const ALTAPAY_REDIRECT = 'sdmaltapay/index/redirect';
    const ALTAPAY_OPEN = 'sdmaltapay/index/open';
    const ALTAPAY_NOTIFICATION = 'sdmaltapay/index/notification';
    const ALTAPAY_CALLBACK = 'sdmaltapay/index/callbackform';
    const ALTAPAY_EXTERNAL_CALLBACK = 'sdmaltapay/external/callbackform';
    const VERIFY_ORDER = 'sdmaltapay/index/verifyorder';

    /* error and success message */
    const ERROR = 'error';
    const CANCELLED = 'cancelled';
    const SUCCESS = 'success';
    const AUTH_MESSAGE = 'Could not authenticate with API';
    const ERROR_MESSAGE = 'error occurred';
    const UNKNOWN_PAYMENT_STATUS_CONSUMER = 'An error has occured! Please contact the shop!';
    const UNKNOWN_PAYMENT_STATUS_MERCHANT = 'Unknown payment status. Please contact Altapay!';
}
