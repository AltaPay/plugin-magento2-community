<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Observer;

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
use SDM\Altapay\Model\Handler\PriceHandler;
use SDM\Altapay\Model\Handler\DiscountHandler;
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
     * @var PriceHandler
     */
    private $priceHandler;
    /**
     * @var DiscountHandler
     */
    private $discountHandler;
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
     * @param PriceHandler                    $priceHandler
     * @param DiscountHandler                 $discountHandler
     * @param ReconciliationIdentifierFactory $reconciliation
     * @param Random                          $random
     */
    public function __construct(
        SystemConfig                    $systemConfig,
        Logger                          $altapayLogger,
        Order                           $order,
        Data                            $helper,
        storeConfig                     $storeConfig,
        OrderLinesHandler               $orderLines,
        PriceHandler                    $priceHandler,
        DiscountHandler                 $discountHandler,
        ReconciliationIdentifierFactory $reconciliation,
        Random                          $random
    ) {
        $this->systemConfig    = $systemConfig;
        $this->altapayLogger   = $altapayLogger;
        $this->order           = $order;
        $this->helper          = $helper;
        $this->storeConfig     = $storeConfig;
        $this->orderLines      = $orderLines;
        $this->priceHandler    = $priceHandler;
        $this->discountHandler = $discountHandler;
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
        if ($memo->getDoTransaction()) {
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
        $couponCode       = $memo->getDiscountDescription();
        $couponCodeAmount = $memo->getDiscountAmount();
        //Return true if discount enabled on all items
        $discountAllItems = $this->discountHandler->allItemsHaveDiscount($memo->getOrder()->getAllVisibleItems());
        //order lines for items
        $orderLines = $this->itemOrderLines($couponCodeAmount, $discountAllItems, $memo);
        //send the discount into separate orderline if discount applied to all items
        if ($discountAllItems == true && abs($couponCodeAmount) > 0) {
            //order lines for discounts
            $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
        }
        if ($memo->getShippingInclTax() > 0) {
            //order lines for shipping
            $orderLines[] = $this->orderLines->handleShipping($memo, $discountAllItems, false);
            //Shipping Discount Tax Compensation Amount
            $compAmount = $this->discountHandler->hiddenTaxDiscountCompensation($memo, $discountAllItems, false);
            if ($compAmount > 0 && $discountAllItems == false) {
                $orderLines[] = $this->orderLines->compensationOrderLine(
                    "Shipping compensation",
                    "comp-ship",
                    $compAmount
                );
            }
        }
        if(!empty($this->fixedProductTax($memo))){
            //order lines for FPT
            $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($memo));
        }

        return $orderLines;
    }

    /**
     * @param float               $couponCodeAmount
     * @param bool                $discountAllItems
     * @param CreditmemoInterface $memo
     *
     * @return array
     */
    private function itemOrderLines($couponCodeAmount, $discountAllItems, $memo)
    {
        $orderLines       = [];
        $discountAmount   = 0;
        $storePriceIncTax = $this->storeConfig->storePriceIncTax($memo->getOrder());
        foreach ($memo->getAllItems() as $item) {
            $qty         = $item->getQty();
            $taxPercent  = $item->getOrderItem()->getTaxPercent();
            $productType = $item->getOrderItem()->getProductType();
            if ($qty > 0 && $productType != 'bundle') {
                if($item->getDiscountAmount()) {
                    $discountAmount = $item->getDiscountAmount();
                }
                $originalPrice  = $item->getOrderItem()->getOriginalPrice();
                $totalPrice     = $originalPrice * $qty;

                if ($originalPrice == 0) {
                    $originalPrice = $item->getPriceInclTax();
                }

                if ($storePriceIncTax) {
                    $priceWithoutTax = $this->priceHandler->getPriceWithoutTax($originalPrice, $taxPercent);
                    $price           = $item->getPriceInclTax();
                    $unitPrice       = bcdiv($priceWithoutTax, 1, 2);
                    $taxAmount       = $this->priceHandler->calculateTaxAmount($priceWithoutTax, $taxPercent, $qty);
                } else {
                    $price           = $item->getPrice();
                    $unitPrice       = $originalPrice;
                    $taxAmount       = $this->priceHandler->calculateTaxAmount($unitPrice, $taxPercent, $qty);
                }
                $itemDiscountInformation = $this->discountHandler->getItemDiscountInformation(
                    $totalPrice,
                    $price,
                    $discountAmount,
                    $qty,
                    $discountAllItems,
                    $item,
                    $taxAmount
                );
                if ($item->getPriceInclTax()) {
                    $discountedAmount = $itemDiscountInformation['discount'];
                    $catalogDiscount  = $itemDiscountInformation['catalogDiscount'];
                    $orderLines[]     = $this->orderLines->itemOrderLine(
                        $item,
                        $unitPrice,
                        $discountedAmount,
                        $taxAmount,
                        $memo->getOrder(),
                        false
                    );
                    // Gateway and cms rounding amount
                    $roundingCompensation = $this->priceHandler->compensationAmountCal(
                        $item,
                        $unitPrice,
                        $taxAmount,
                        $discountedAmount,
                        false
                    );
                    //send the rounding mismatch value into separate orderline if any
                    if ($roundingCompensation > 0 || $roundingCompensation < 0) {
                        $orderLines[] = $this->orderLines->compensationOrderLine(
                            "Compensation Amount",
                            "comp-" . $item->getOrderItem()->getItemId(),
                            $roundingCompensation
                        );
                    }
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
        $refund = new RefundCapturedReservation($this->systemConfig->getAuth($storeCode));
        $reconciliationIdentifier  = $this->random->getUniqueHash();
        if ($memo->getTransactionId()) {
            $refund->setTransaction($payment->getLastTransId());
        }
        $refund->setAmount((float)number_format($memo->getGrandTotal(), 2, '.', ''));
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
        //throw exception if result is not success
        if ($xml->Body->Result != 'Success') {
            throw new \InvalidArgumentException('Could not refund captured reservation');
        }
    }

    /**
     * @param $order
     *
     * @return float|int
     */
    public function fixedProductTax($memo){

        $weeTaxAmount = 0;
        foreach ($memo->getAllItems() as $item) {
            $weeTaxAmount +=  $item->getWeeeTaxAppliedRowAmount();
        }

        return $weeTaxAmount;
    }
}
