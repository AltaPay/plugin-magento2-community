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
use Magento\Sales\Model\Order;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Logger
     */
    private $valitorLogger;
    /**
     * @var Order
    */
    private $order;
    /**
     * @var productFactory
    */
    private $productFactory;
    /**
     * CaptureObserver constructor.
     * @param SystemConfig $systemConfig
     * @param Logger $valitorLogger
     */
    public function __construct(SystemConfig $systemConfig, Logger $valitorLogger, Order $order, ProductFactory $productFactory
    ,ScopeConfigInterface $scopeConfig)
    {
        $this->systemConfig = $systemConfig;
        $this->valitorLogger = $valitorLogger;
        $this->order = $order;
        $this->productFactory = $productFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
      * @param $id
      * @return productPrice
      */
    public function getProductPrice($id)
    {
        $product = $this->productFactory->create();
        $productPrice = $product->load($id)->getPrice();
        return $productPrice;
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
        $orderIncrementId = $invoice->getOrder()->getIncrementId();
        $orderObject = $this->order->loadByIncrementId($orderIncrementId);
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode = $invoice->getStore()->getCode();
        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            $orderlines = [];
            $couponCode = $invoice->getDiscountDescription();
            $couponCodeAmount = $invoice->getDiscountAmount();
            $compAmount = $invoice->getShippingDiscountTaxCompensationAmount();
            /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
            foreach ($invoice->getItems() as $item) {
                $id = $item->getProductId();
                $quantity = $item->getQty();
                if($quantity > 0){
                    $priceExcTax = $item->getPrice();
                    if ((int) $this->scopeConfig->getValue('tax/calculation/price_includes_tax', $storeScope) === 1) {
                        //Handle only if we have coupon Code
                        $taxPercent = $item->getOrderItem()->getTaxPercent();
                        $taxCalculatedAmount = $priceExcTax *  ($taxPercent/100);
                        $taxAmount = (number_format($taxCalculatedAmount, 2, '.', '') * $quantity);
                    }else{
                        $taxAmount = $item->getTaxAmount();
                    }
                    if ($item->getPriceInclTax()) {
                        $orderline = new OrderLine(
                            $item->getName(),
                            $item->getSku(),
                            $quantity,
                            $item->getPrice()
                        );
                        $orderline->setGoodsType('item');
                        $orderline->taxAmount = $taxAmount;
                        $orderlines[] = $orderline;
                    }
                }
            }
            
            if (abs($couponCodeAmount) > 0) {
                if(empty($couponCode)){
                    $couponCode = 'Cart Price Rule';
                }
                // Handling price reductions
                $orderline = new OrderLine(
                    $couponCode,
                    'discount',
                    1,
                    $couponCodeAmount
                );
                $orderline->setGoodsType('handling');
                $orderlines[] = $orderline;
            }

            if ($invoice->getShippingInclTax() > 0) {
                $orderline = new OrderLine(
                    'Shipping',
                    'shipping',
                    1,
                    $invoice->getShippingAmount() + $compAmount
                );
                $orderline->setGoodsType('shipment');
                $orderline->taxAmount = $invoice->getShippingTaxAmount();
                $orderlines[] = $orderline;
            }

            $api = new CaptureReservation($this->systemConfig->getAuth($storeCode));
            if ($invoice->getTransactionId()) {
                $api->setInvoiceNumber($invoice->getTransactionId());
            }

            $api->setAmount((float) number_format($invoice->getGrandTotal(), 2, '.', ''));
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
            }

            $rawresponse = $api->getRawResponse();
            if (!empty($rawresponse)) {
                $body = $rawresponse->getBody();
                $this->valitorLogger->addInfo('Response body: ' . $body);
            }

          
            //Update comments if capture fail
            $xml = simplexml_load_string($body);    
            if ($xml->Body->Result == 'Error' || $xml->Body->Result == 'Failed') {
                $orderObject->addStatusHistoryComment('Refund failed: '. $xml->Body->MerchantErrorMessage)->setIsCustomerNotified(false);
                $orderObject->getResource()->save($orderObject);
            }

            $headdata = [];
            foreach ($rawresponse->getHeaders() as $k => $v) {
                $headdata[] = $k . ': ' . json_encode($v);
            }
            $this->valitorLogger->addInfoLog('Response headers', implode(", ", $headdata));

            if (!isset($response->Result) || $response->Result != 'Success') {
                throw new \InvalidArgumentException('Could not capture reservation');
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice\Item $item
     */
    protected function logItem($item)
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
    protected function logPayment($payment, $invoice)
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
