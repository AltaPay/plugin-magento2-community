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

namespace SDM\Valitor\Api;

use Magento\Sales\Model\Order;

/**
 * Interface OrderLoaderInterface
 * @package SDM\Valitor\Api
 */
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
     * @param string $orderIncrementId
     *
     * @return Order
     */
    public function getOrderByOrderIncrementId($orderId);
}
