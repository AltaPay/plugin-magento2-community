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
     * @return Address|null
     */
    public function getRegisteredAddress()
    {
        $response          = $this->call();
        $max_date = '';
        $latestTransKey = '';
        foreach ($response->Transactions as $key=>$value) {
            if ($value->CreatedDate > $max_date) {
                $max_date = $value->CreatedDate;
                $latestTransKey = $key;
            }
        }
        $registeredAddress = null;
        if (isset($response->Transactions[$latestTransKey]->CustomerInfo->RegisteredAddress)) {
            $registeredAddress = $response->Transactions[$latestTransKey]->CustomerInfo->RegisteredAddress;
        }

        return $registeredAddress;
    }
}
