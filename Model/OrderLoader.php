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

namespace SDM\Altapay\Model;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use SDM\Altapay\Api\OrderLoaderInterface;

/**
 * Class OrderLoader
 * @package SDM\Altapay\Model
 */
class OrderLoader implements OrderLoaderInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * OrderLoader constructor.
     * @param Session      $checkoutSession
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
    }
    /**
     * getLastOrderIncrementIdFromSession
     * @return string
     */
    public function getLastOrderIncrementIdFromSession()
    {
        return (string)$this->checkoutSession->getLastRealOrder()->getIncrementId();
    }
    /**
     * getOrderByOrderIncrementId
     * @param string $orderId
     * @return Order
     */
    public function getOrderByOrderIncrementId(string $orderId)
    {
        return $this->orderFactory
            ->create()
            ->loadByIncrementId($orderId);
    }
}
