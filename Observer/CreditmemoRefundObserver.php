<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Observer;

use Magento\Bundle\Model\Product\Price;
use Magento\Sales\Model\Order\Payment;
use Altapay\Api\Payments\RefundCapturedReservation;
use Altapay\Exceptions\ResponseHeaderException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Logger\Logger;
use SDM\Altapay\Model\SystemConfig;
use Magento\Sales\Model\Order;
use SDM\Altapay\Helper\Data;
use SDM\Altapay\Helper\Config as storeConfig;
use SDM\Altapay\Model\Handler\OrderLinesHandler;
use Magento\Framework\Math\Random;
use SDM\Altapay\Model\ReconciliationIdentifierFactory;

/**
 * Class CreditmemoRefundObserver
 * Handle the refund functionality.
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
     * @var Order
     */
    private $order;
    /**
     * @var Helper Data
     */
    private $helper;

    /**
     * @var Helper Config
     */
    private $storeConfig;
    /**
     * @var OrderLinesHandler
     */
    private $orderLines;
    /**
     * @var ReconciliationIdentifierFactory
     */
    private $reconciliation;
    /**
     * @var Random
     */
    private $random;

    /**
     * CreditmemoRefundObserver constructor.
     *
     * @param SystemConfig                    $systemConfig
     * @param Logger                          $altapayLogger
     * @param Order                           $order
     * @param Data                            $helper
     * @param storeConfig                     $storeConfig
     * @param OrderLinesHandler               $orderLines
     * @param ReconciliationIdentifierFactory $reconciliation
     * @param Random                          $random
     */
    public function __construct(
        SystemConfig $systemConfig,
        Logger $altapayLogger,
        Order $order,
        Data $helper,
        storeConfig $storeConfig,
        OrderLinesHandler $orderLines,
        ReconciliationIdentifierFactory $reconciliation,
        Random $random
    ) {
        $this->systemConfig    = $systemConfig;
        $this->altapayLogger   = $altapayLogger;
        $this->order           = $order;
        $this->helper          = $helper;
        $this->storeConfig     = $storeConfig;
        $this->orderLines      = $orderLines;
        $this->reconciliation  = $reconciliation;
        $this->random          = $random;
    }

    /**
     * @param Observer $observer
     *
     * @throws ResponseHeaderException
     */
    public function execute(Observer $observer)
    {
        $memo = $observer['creditmemo'];
        $payment = $memo->getOrder()->getPayment();
        $paymentType = $payment->getAdditionalInformation('payment_type');
        if ($memo->getTransactionId() && ($memo->getDoTransaction() || ($paymentType && strtolower($paymentType) === "paymentandcapture"))) {
            $orderIncrementId = $memo->getOrder()->getIncrementId();
            $orderObject      = $this->order->loadByIncrementId($orderIncrementId);
            $storeCode        = $memo->getStore()->getCode();
            $payment          = $memo->getOrder()->getPayment();
            //If payment method belongs to terminal codes
            if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
                //Create orderlines from order items
                $orderLines = $this->processRefundOrderItems($memo);
                //Send request for payment refund
                $this->sendRefundRequest($memo, $orderLines, $orderObject, $payment, $storeCode);
            }
        }
    }

    /**
     * @param CreditmemoInterface $memo
     *
     * @return array
     */

    private function processRefundOrderItems($memo)
    {
        $moduleVersion    = $memo->getOrder()->getModuleVersion() ? $memo->getOrder()->getModuleVersion() : '';
        $baseCurrency     = $this->storeConfig->useBaseCurrency($moduleVersion);
        $couponCodeAmount = $baseCurrency ? $memo->getBaseDiscountAmount() : $memo->getDiscountAmount();
        //order lines for items
        $orderLines = $this->itemOrderLines($memo);
        //send the discount into separate orderline if discount applied to all items
        if (abs($couponCodeAmount) > 0) {
            $couponCode       = $memo->getDiscountDescription();
            //order lines for discounts
            $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
        }
        if ($memo->getShippingInclTax() > 0) {
            //order lines for shipping
            $orderLines[] = $this->orderLines->shippingOrderLine($memo, false);
        }
        if(!empty($this->fixedProductTax($memo, $baseCurrency))){
            //order lines for FPT
            $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($memo, $baseCurrency));
        }

        return $orderLines;
    }

    /**
     * @param CreditmemoInterface $memo
     *
     * @return array
     */
    public function itemOrderLines($memo)
    {
        $orderLines       = [];
        $moduleVersion    = $memo->getOrder()->getModuleVersion() ? $memo->getOrder()->getModuleVersion() : '';
        $baseCurrency     = $this->storeConfig->useBaseCurrency($moduleVersion);
        
        foreach ($memo->getAllItems() as $item) {
            $qty         = $item->getQty();
            $productType = $item->getOrderItem()->getProductType();
            $priceInclTax = $baseCurrency ? $item->getBasePriceInclTax() : $item->getPriceInclTax();
            if (
                ($qty > 0 && $productType != 'bundle' && $priceInclTax) ||
                ($productType === "bundle" && $item->getOrderItem()->getProduct()->getPriceType() == Price::PRICE_TYPE_FIXED)
            ) {
                if ($item->getPriceInclTax()) {
                    $orderLines[] = $this->orderLines->itemOrderLine($item, $memo->getOrder(), false);
                }
            }
        }

        return $orderLines;
    }

    /**
     * @param CreditmemoInterface   $memo
     * @param array                 $orderLines
     * @param Order                 $orderObject
     * @param Payment               $payment
     * @param StoreManagerInterface $storeCode
     *
     * @throws ResponseHeaderException
     */
    private function sendRefundRequest($memo, $orderLines, $orderObject, $payment, $storeCode)
    {
        $moduleVersion  = $memo->getOrder()->getModuleVersion() ? $memo->getOrder()->getModuleVersion() : '';
        $baseCurrency   = $this->storeConfig->useBaseCurrency($moduleVersion);
        $grandTotal = $baseCurrency ? $memo->getBaseGrandTotal() : $memo->getGrandTotal();
        $refund = new RefundCapturedReservation($this->systemConfig->getAuth($storeCode));
        $reconciliationIdentifier  = $this->random->getUniqueHash();
        if ($memo->getTransactionId()) {
            $refund->setTransaction($payment->getLastTransId());
        }
        $refund->setAmount(round($grandTotal, 2));

        $totalCompensationAmount = $this->orderLines->totalCompensationAmount($orderLines, $grandTotal);
        if ($totalCompensationAmount > 0 || $totalCompensationAmount < 0) {
            $orderLines[] = $this->orderLines->compensationOrderLine(
                "Compensation Amount",
                "comp-amount",
                $totalCompensationAmount
            );
        }

        $refund->setOrderLines($orderLines);
        $refund->setReconciliationIdentifier($reconciliationIdentifier);
        try {
            $refund->call();
        } catch (ResponseHeaderException $e) {
            $this->altapayLogger->addCriticalLog('Exception' , $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->altapayLogger->addCriticalLog('Exception: ' , $e->getMessage());
        }

        $model = $this->reconciliation->create();
        $model->addData([
            "order_id"      => $memo->getOrder()->getIncrementId(),
            "identifier"    => $reconciliationIdentifier,
            "type"          => 'refunded'
        ]);
        $model->save();

        $rawResponse = $refund->getRawResponse();
        $body        = $rawResponse->getBody();
        //add information to the altapay log
        $this->altapayLogger->addInfoLog('Info' , $body);

        //Update comments if refund fail
        $xml = simplexml_load_string($body);
        if ($xml->Body->Result == 'Error' || $xml->Body->Result == 'Failed' || $xml->Body->Result == 'Incomplete') {
            $orderObject->addStatusHistoryComment('Refund failed: ' . $xml->Body->MerchantErrorMessage)
                ->setIsCustomerNotified(false);
            $orderObject->getResource()->save($orderObject);
        }
        if (strtolower($xml->Body->Result) === 'open') {
            $msg = 'Payment refund is in progress.';
            $orderObject->addStatusHistoryComment($msg)->setIsCustomerNotified(false);
            $orderObject->getResource()->save($orderObject);

            throw new \InvalidArgumentException(__('CreditMemo creation is on hold until the gateway completes the refund.'));
        }

        //throw exception if result is not success
        if ($xml->Body->Result != 'Success') {
            throw new \InvalidArgumentException('Could not refund captured reservation');
        }
    }

    /**
     * @param $order
     * @param $baseCurrency
     *
     * @return float|int
     */
    public function fixedProductTax($memo, $baseCurrency){

        $weeTaxAmount = 0;
        foreach ($memo->getAllItems() as $item) {
            $weeTaxAmount += $baseCurrency ? $item->getBaseWeeeTaxAppliedRowAmount() : $item->getWeeeTaxAppliedRowAmount();
        }

        return $weeTaxAmount;
    }
}
