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
use Magento\Sales\Model\Order;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
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
    public function getProductPrice($id)
    {
    $product = $this->productFactory->create();
    $productPriceById = $product->load($id)->getPrice();
    return $productPriceById;
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
        $creditOnline = $memo->getDoTransaction();
        if ($creditOnline) {
            /** @var \Magento\Sales\Model\Order $order */
            $orderIncrementId = $memo->getOrder()->getIncrementId();
            $orderObject = $this->order->loadByIncrementId($orderIncrementId);
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $memo->getStore()->getCode();
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $memo->getOrder()->getPayment();
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
                $orderlines = [];
                $appliedRule = $memo->getAppliedRuleIds();
                $couponCode = $memo->getDiscountDescription();
                $couponCodeAmount = $memo->getDiscountAmount();
                $compAmount = $memo->getOrder()->getShippingDiscountTaxCompensationAmount();
                foreach ($memo->getItems() as $item) {
                    $quantity = $item->getQty();
                    if($quantity > 0){
                        $id = $item->getProductId();                
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


                if ($memo->getShippingInclTax()) {
                    $orderline = new OrderLine(
                        'Shipping',
                        'shipping',
                        1,
                        $memo->getShippingAmount() + $compAmount
                    );
                    $orderline->setGoodsType('shipment');
                    $orderline->taxAmount = $memo->getShippingTaxAmount();
                    $orderlines[] = $orderline;
                }
                $refund = new RefundCapturedReservation($this->systemConfig->getAuth($storeCode));
            if ($memo->getTransactionId()) {
                $refund->setTransaction($payment->getLastTransId());
            }
                $refund->setAmount((float) number_format($memo->getGrandTotal(), 2, '.', ''));
                $refund->setOrderLines($orderlines);
                /** @var RefundResponse $response */
                try {
                    $response = $refund->call();
                } catch (ResponseHeaderException $e) {
                    $this->valitorLogger->addCritical('Response header exception: ' . $e->getMessage());
                    throw $e;
                } catch (\Exception $e) {
                    $this->valitorLogger->addCritical('Exception: ' . $e->getMessage());
                }
                
                $rawresponse = $refund->getRawResponse();
                $body = $rawresponse->getBody();
                $this->valitorLogger->addInfo('Response body: ' . $body);
                
                //Update comments if refund fail
                $xml = simplexml_load_string($body);
                if ($xml->Body->Result == 'Error' || $xml->Body->Result == 'Failed') {
                    $orderObject->addStatusHistoryComment('Refund failed: '. $xml->Body->MerchantErrorMessage)->setIsCustomerNotified(false);
                    $orderObject->getResource()->save($orderObject);
                }
          
                if ($xml->Body->Result != 'Success') {
                    throw new \InvalidArgumentException('Could not refund captured reservation');
                }
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
