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

use SDM\Valitor\Api\Payments\CaptureReservation;
use SDM\Valitor\Exceptions\ResponseHeaderException;
use SDM\Valitor\Request\OrderLine;
use SDM\Valitor\Response\CaptureReservationResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Valitor\Logger\Logger;
use SDM\Valitor\Model\SystemConfig;

/**
 * Class CaptureObserver
 * @package SDM\Valitor\Observer
 */
class CaptureObserver implements ObserverInterface
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
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer['payment'];

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer['invoice'];

        $storeCode = $invoice->getStore()->getCode();
        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            $this->logPayment($payment, $invoice);

            $orderlines = [];
            /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
            foreach ($invoice->getItems() as $item) {
                if ($item->getPriceInclTax()) {
                    $this->logItem($item);

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

            if ($invoice->getShippingInclTax()) {
                $orderline = new OrderLine(
                    'Shipping',
                    'shipping',
                    1,
                    $invoice->getShippingInclTax()
                );
                $orderline->setGoodsType('shipment');
                $orderline->taxAmount = $invoice->getShippingTaxAmount();
                $orderlines[] = $orderline;
            }

            $api = new CaptureReservation($this->systemConfig->getAuth($storeCode));
            if ($invoice->getTransactionId()) {
                $api->setInvoiceNumber($invoice->getTransactionId());
            }

            $api->setAmount((float) $invoice->getGrandTotal());
            $api->setOrderLines($orderlines);
            $api->setTransaction($payment->getLastTransId());
            /** @var CaptureReservationResponse $response */
            try {
                $response = $api->call();
            } catch (ResponseHeaderException $e) {
                $this->valitorLogger->addInfoLog('Info', $e->getHeader());
                $this->valitorLogger->addCriticalLog('Response header exception', $e->getMessage());
                throw $e;
            } catch (\Exception $e) {
                $this->valitorLogger->addCriticalLog('Exception', $e->getMessage());
                throw $e;
            }

            $rawresponse = $api->getRawResponse();
            $body = $rawresponse->getBody();
            $this->valitorLogger->addInfoLog('Response body', $body);

            $headdata = [];
            foreach ($rawresponse->getHeaders() as $k => $v) {
                $headdata[] = $k . ': ' . json_encode($v);
            }
            $this->valitorLogger->addInfoLog('Response headers', implode(", ", $headdata));

            if ($response->Result != 'Success') {
                throw new \InvalidArgumentException('Could not capture reservation');
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice\Item $item
     */
    private function logItem($item)
    {
        $this->valitorLogger->addInfoLog(
            'Log Item',
            sprintf(
                implode(' - ', [
                    'getSku: %s',
                    'getQty: %s',
                    'getDescription: %s',
                    'getPrice(): %s',
                    'getDiscountAmount(): %s',
                    'getPrice() - getDiscountAmount(): %s',
                    'getRowTotalInclTax: %s',
                    'getRowTotal: %s'
                ]),
                $item->getSku(),
                $item->getQty(),
                $item->getDescription(),
                $item->getPrice(),
                $item->getDiscountAmount(),
                $item->getPrice() - $item->getDiscountAmount(),
                $item->getRowTotalInclTax(),
                $item->getRowTotal()
            )
        );
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     */
    private function logPayment($payment, $invoice)
    {
        $logs = [
            'invoice.getTransactionId: %s',
            'invoice->getOrder()->getIncrementId: %s',
            '$invoice->getGrandTotal(): %s',
            'getLastTransId: %s',
            'getAmountAuthorized: %s',
            'getAmountCanceled: %s',
            'getAmountOrdered: %s',
            'getAmountPaid: %s',
            'getAmountRefunded: %s',
        ];

        $this->valitorLogger->addInfoLog(
            'Log Transaction',
            sprintf(
                implode(' - ', $logs),
                $invoice->getTransactionId(),
                $invoice->getOrder()->getIncrementId(),
                $invoice->getGrandTotal(),
                $payment->getLastTransId(),
                $payment->getAmountAuthorized(),
                $payment->getAmountCanceled(),
                $payment->getAmountOrdered(),
                $payment->getAmountPaid(),
                $payment->getAmountRefunded()
            )
        );
    }
}
