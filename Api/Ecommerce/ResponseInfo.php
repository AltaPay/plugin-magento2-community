<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Api\Ecommerce;

use SDM\Altapay\Api\Ecommerce\Callback;

class ResponseInfo extends Callback
{
    public function __construct($postedData)
    {
        parent::__construct($postedData);
    }

    /**
     * @return RegisteredAddress
     */
    public function getRegisteredAddress()
    {
        $response          = $this->call();
        $registeredAddress = '';
        if (isset($response->Transactions[0]->CustomerInfo->RegisteredAddress)) {
            $registeredAddress = $response->Transactions[0]->CustomerInfo->RegisteredAddress;
        }

        return $registeredAddress;
    }
}
