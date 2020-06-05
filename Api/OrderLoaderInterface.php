<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Api;

use Magento\Sales\Model\Order;

interface OrderLoaderInterface
{
    /**
     * getLastOrderIncrementIdFromSession
     *
     * @return string
     */
    public function getLastOrderIncrementIdFromSession();

    /**
     * getOrderByOrderIncrementId
     *
     * @param $orderId
     *
     * @return Order
     */
    public function getOrderByOrderIncrementId($orderId);
}
