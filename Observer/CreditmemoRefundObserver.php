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
namespace SDM\Valitor\Observer;

use SDM\Valitor\Api\Payments\RefundCapturedReservation;
use SDM\Valitor\Exceptions\ResponseHeaderException;
use SDM\Valitor\Response\RefundResponse;
use SDM\Valitor\Request\OrderLine;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Valitor\Logger\Logger;
use SDM\Valitor\Model\SystemConfig;

/**
 * Class CreditmemoRefundObserver
 * @package SDM\Valitor\Observer
 */
class CreditmemoRefundObserver implements ObserverInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Logger
     */
    private $valitorLogger;

    /**
     * CaptureObserver constructor.
     * @param SystemConfig $systemConfig
     * @param Logger $valitorLogger
     */
    public function __construct(SystemConfig $systemConfig, Logger $valitorLogger)
    {
        $this->systemConfig = $systemConfig;
        $this->valitorLogger = $valitorLogger;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws ResponseHeaderException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Api\Data\CreditmemoInterface $memo */
        $memo = $observer['creditmemo'];
        $orderlines = [];
        $creditOnline = $memo->getDoTransaction();
        if ($creditOnline) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $memo->getOrder();

            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();
            if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
                foreach ($memo->getItems() as $item) {
                    if ($item->getPriceInclTax()) {
                        $orderline = new OrderLine(
                            $item->getName(),
                            $item->getSku(),
                            $item->getQty(),
                            $item->getPriceInclTax()
                        );
                        $orderline->setGoodsType('item');
                        $orderline->taxAmount = $item->getTaxAmount();
                        $orderlines[] = $orderline;
                    }
                }

                if ($memo->getShippingInclTax()) {
                    $orderline = new OrderLine(
                        'Shipping',
                        'shipping',
                        1,
                        $memo->getShippingInclTax()
                    );
                    $orderline->setGoodsType('shipment');
                    $orderline->taxAmount = $memo->getShippingTaxAmount();
                    $orderlines[] = $orderline;
                }
                $refund = new RefundCapturedReservation($this->systemConfig->getAuth($order->getStore()->getCode()));
                $refund->setTransaction($payment->getLastTransId());
                $refund->setAmount((float) $memo->getGrandTotal());
                $refund->setOrderLines($orderlines);
                /** @var RefundResponse $response */
                try {
                    $response = $refund->call();
                    if ($response->Result != 'Success') {
                        throw new \InvalidArgumentException('Could not refund captured reservation');
                    }
                } catch (ResponseHeaderException $e) {
                    $this->monolog->addCritical('Response header exception: ' . $e->getMessage());
                    throw $e;
                }
            }
        }
    }
}
