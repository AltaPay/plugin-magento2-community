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
    const PAYMENT_COMPLETE = 'Payment is completed';
    const OK_CALLBACK = 'OK callback from Altapay';

    /* config url */
    const ALTAPAY_OK = 'sdmaltapay/index/ok';
    const ALTAPAY_FAIL = 'sdmaltapay/index/fail';
    const ALTAPAY_REDIRECT = 'sdmaltapay/index/redirect';
    const ALTAPAY_OPEN = 'sdmaltapay/index/open';
    const ALTAPAY_NOTIFICATION = 'sdmaltapay/index/notification';
    const ALTAPAY_CALLBACK = 'sdmaltapay/index/callbackform';
    const ALTAPAY_EXTERNAL_CALLBACK = 'sdmaltapay/external/callbackform';

    /* error and success message */
    const ERROR = 'error';
    const CANCELLED = 'cancelled';
    const SUCCESS = 'success';
    const ERROR_MESSAGE = 'error occurred';
    const UNKNOWN_PAYMENT_STATUS_MERCHANT = 'Unknown payment status. Please contact Altapay!';
}
