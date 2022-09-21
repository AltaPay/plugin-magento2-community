<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Api;

interface GatewayInterface
{
    /**
     * Createrequest to altapay in order to generate for url
     *
     * @param int    $terminalId
     * @param string $orderId
     *
     * @return string[]
     */
    public function createRequest($terminalId, $orderId);
}
