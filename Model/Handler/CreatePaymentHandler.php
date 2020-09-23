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
     * @param Order $order
     * @param       $state
     * @param       $statusKey
     *
     * @throws AlreadyExistsException
     */
    public function setCustomOrderStatus(Order $order, $state, $statusKey)
    {
        $order->setState($state);
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode  = $order->getStore()->getCode();
        if ($status = $this->systemConfig->getStatusConfig($statusKey, $storeScope, $storeCode)) {
            $order->setStatus($status);
        }
        $order->getResource()->save($order);
    }
}
