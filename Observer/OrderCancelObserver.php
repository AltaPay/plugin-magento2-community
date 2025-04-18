<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Observer;

use Altapay\Api\Payments\ReleaseReservation;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Response\ReleaseReservationResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Model\SystemConfig;
use Altapay\Api\Payments\RefundCapturedReservation;
use SDM\Altapay\Helper\Config as storeConfig;
use Magento\Framework\App\CacheInterface;

class OrderCancelObserver implements ObserverInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Helper Config
     */
    private $storeConfig;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * OrderCancelObserver constructor.
     *
     * @param SystemConfig $systemConfig
     * @param storeConfig $storeConfig
     * @param CacheInterface $cache
     */
    public function __construct(
        SystemConfig $systemConfig,
        storeConfig $storeConfig,
        CacheInterface $cache)
    {
        $this->systemConfig = $systemConfig;
        $this->storeConfig  = $storeConfig;
        $this->cache        = $cache;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws ResponseHeaderException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer['order'];
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        $moduleVersion  = $order->getModuleVersion() ? $order->getModuleVersion() : '';
        $baseCurrency   = $this->storeConfig->useBaseCurrency($moduleVersion);
        $grandTotal     = $baseCurrency ? $order->getBaseGrandTotal() : $order->getGrandTotal();

        $key              = 'altapay_cancel_forcefully_' . $order->getId();
        $cancelForcefully = $this->cache->load($key);

        if ($cancelForcefully) {
            return;
        }

        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes()) && $payment->getLastTransId()) {
            if ($payment->getAdditionalInformation('payment_type') === "paymentAndCapture") {
                $api = new RefundCapturedReservation($this->systemConfig->getAuth($order->getStore()->getCode()));
                $api->setAmount(round($grandTotal, 2));
            } else {
                $api = new ReleaseReservation($this->systemConfig->getAuth($order->getStore()->getCode()));
            }
            $api->setTransaction($payment->getLastTransId());
            /** @var ReleaseReservationResponse $response */
            try {
                $response = $api->call();
                if ($response->Result != 'Success') {
                    throw new \InvalidArgumentException('Could not release reservation');
                }
            } catch (\Exception $e) {
                $key = 'altapay_release_failed_' . $order->getId();
                $this->cache->save(true, $key, ['release_failed'], 60 * 60);
                throw $e;
            }
        }
    }
}