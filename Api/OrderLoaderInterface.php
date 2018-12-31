<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */

namespace SDM\Altapay\Api;

use Magento\Sales\Model\Order;

/**
 * Interface OrderLoaderInterface
 * @package SDM\Altapay\Api
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
    public function getOrderByOrderIncrementId(string $orderId);
}
