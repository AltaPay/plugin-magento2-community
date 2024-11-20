<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Handler;

use SDM\Altapay\Model\SystemConfig;
use Magento\Sales\Model\Order;

/**
 * Class CreatePaymentHandler
 * To handle the functionality related to create payment
 * request at altapay.
 */
class CreatePaymentHandler
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var Order
     */
    private $order;

    /**
     * Gateway constructor.
     *
     * @param SystemConfig $systemConfig
     * @param Order        $order
     */
    public function __construct(
        SystemConfig $systemConfig,
        Order $order
    ) {
        $this->systemConfig = $systemConfig;
        $this->order        = $order;
    }

    /**
     * @param Order  $order
     * @param string $state
     * @param string $statusKey
     *
     * @throws AlreadyExistsException
     */
    public function setCustomOrderStatus(Order $order, string $state, string $statusKey)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode  = $order->getStore()->getCode();
        $status     = $this->systemConfig->getStatusConfig($statusKey, $storeScope, $storeCode);
        $saveOrder  = false;

        if ($order->getState() !== $state) {
            $order->setState($state);
            $saveOrder = true;
        }

        if ($status && $order->getStatus() !== $status) {
            $order->setStatus($status);
            $saveOrder = true;
        }


        if ($saveOrder) {
            $order->getResource()->save($order);
        }
    }
}
