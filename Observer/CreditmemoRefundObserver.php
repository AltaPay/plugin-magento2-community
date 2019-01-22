<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */
namespace SDM\Altapay\Observer;

use SDM\Altapay\Api\Payments\RefundCapturedReservation;
use SDM\Altapay\Exceptions\ResponseHeaderException;
use SDM\Altapay\Response\RefundResponse;
use SDM\Altapay\Request\OrderLine;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Logger\Logger;
use SDM\Altapay\Model\SystemConfig;

/**
 * Class CreditmemoRefundObserver
 * @package SDM\Altapay\Observer
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
    private $altapayLogger;

    /**
     * CaptureObserver constructor.
     * @param SystemConfig $systemConfig
     * @param Logger $altapayLogger
     */
    public function __construct(SystemConfig $systemConfig, Logger $altapayLogger)
    {
        $this->systemConfig = $systemConfig;
        $this->altapayLogger = $altapayLogger;
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
