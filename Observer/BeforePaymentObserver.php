<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use SDM\Valitor\Model\SystemConfig;

class BeforePaymentObserver implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * BeforePaymentObserver constructor.
     *
     * @param SystemConfig $systemConfig
     */
    public function __construct(SystemConfig $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $orderState  = Order::STATE_NEW;
        $orderStatus = Order::STATE_NEW;
        $payment     = $observer['payment'];
        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            /**
             * @var \Magento\Sales\Model\Order
             */
            $order      = $payment->getOrder();
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode  = $order->getStore()->getCode();

            //Set the first order state and status (custom, if applicable)
            $customFirstOrderStatus = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);
            if ($customFirstOrderStatus) {
                $orderStatus = $customFirstOrderStatus;
            }

            $order->setState($orderState)->setStatus($orderStatus);
            // Do not send any mails until payment is complete
            $order->setCanSendNewEmailFlag(false);
            $order->setIsNotified(false);
            $order->getResource()->save($order);
        }
    }
}
