<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Test\Unit;

/**
 * Class ConstantTestConfig
 */
abstract class ConstantTestConfig
{
    /* block infotest */
    const CC_TRANSACTION_ID = '12345';
    const CC_PAYMENT_ID     = '12345';

    /* block callback ordersummarytest / generatortest */
    const ORDER_ID      = '00000001';
    const TERMINAL_ID   = '1';
    const STORE_CODE    = '1';
    const SHOP_ORDER_ID = '1';
    const EMAIL         = 'sar@embrace-it.com';
    const COMPANY       = 'Testcompany Ltd.';
    const ORDER_TOTAL   = '100';
    const CURRECNY      = 'USD';
    const CALL_BACK     = 'sdmaltapay/index/callbackform';
    const TAX           = '10';
    const TAX_PERCENT   = '10';
    const ITEM_NAME     = 'Test';
    const ITEM_SKU      = '001';
    const ITEM_QTY      = '1';
    const ITEM_PRICE    = '10';
    const FIRST_NAME    = 'Test';
    const LAST_NAME     = 'Test';
    const STREET        = 'Washington Cal 14';
    const CITY          = 'California';
    const ZIP           = '90001';
    const COUNTRY       = 'US';
    const STATE         = 'CA';
    const REGION_ID     = '5';
    const PRODUCT_URL   = '';
    const IMAGE_URL     = '';
    const UNIT_CODE     = 'unit';

    /* model handler restorequotetest  */
    const ID             = '123';
    const LAST_ORDER_ID  = '123';
}
