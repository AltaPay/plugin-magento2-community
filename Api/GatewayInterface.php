<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Api;

interface GatewayInterface
{
    /**
     * Createrequest to valitor in order to generate for url
     *
     * @param int    $terminalId
     * @param string $orderId
     *
     * @return array
     */
    public function createRequest($terminalId, $orderId);
}
