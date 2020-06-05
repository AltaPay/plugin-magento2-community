<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Observer;

use Magento\Sales\Model\Order\Payment;
use SDM\Valitor\Api\Payments\RefundCapturedReservation;
use SDM\Valitor\Exceptions\ResponseHeaderException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Valitor\Logger\Logger;
use SDM\Valitor\Model\SystemConfig;
use Magento\Sales\Model\Order;
use SDM\Valitor\Helper\Data;
use SDM\Valitor\Helper\Config as storeConfig;
use SDM\Valitor\Model\Handler\OrderLinesHandler;
use SDM\Valitor\Model\Handler\PriceHandler;
use SDM\Valitor\Model\Handler\DiscountHandler;

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
    private $valitorLogger;
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
     * CreditmemoRefundObserver constructor.
     *
     * @param SystemConfig      $systemConfig
     * @param Logger            $valitorLogger
     * @param Order             $order
     * @param Data              $helper
     * @param storeConfig       $storeConfig
     * @param OrderLinesHandler $orderLines
     * @param PriceHandler      $priceHandler
     * @param DiscountHandler   $discountHandler
     */
    public function __construct(
        SystemConfig $systemConfig,
        Logger $valitorLogger,
        Order $order,
        Data $helper,
        storeConfig $storeConfig,
        OrderLinesHandler $orderLines,
        PriceHandler $priceHandler,
        DiscountHandler $discountHandler
    ) {
        $this->systemConfig    = $systemConfig;
        $this->valitorLogger   = $valitorLogger;
        $this->order           = $order;
        $this->helper          = $helper;
        $this->storeConfig     = $storeConfig;
        $this->orderLines      = $orderLines;
        $this->priceHandler    = $priceHandler;
        $this->discountHandler = $discountHandler;
    }

    /**
     * @param Observer $observer
     *
     * @throws ResponseHeaderException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $memo = $observer['creditmemo'];
        $storePriceIncTax = $this->storeConfig->storePriceIncTax($memo->getOrder());
        //proceed if online refund
        if ($memo->getDoTransaction()) {
            $orderIncrementId = $memo->getOrder()->getIncrementId();
            $orderObject      = $this->order->loadByIncrementId($orderIncrementId);
            $storeCode        = $memo->getStore()->getCode();
            $payment          = $memo->getOrder()->getPayment();
            //If payment method belongs to terminal codes
            if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
                //Create orderlines from order items
                $orderLines = $this->processRefundOrderItems($storePriceIncTax, $memo);
                //Send request for payment refund
                $this->sendRefundRequest($memo, $orderLines, $orderObject, $payment, $storeCode);
            }
        }
    }

    /**
     * @param $storePriceIncTax
     * @param $memo
     * @return array
     */

    private function processRefundOrderItems($storePriceIncTax, $memo)
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
            $orderLines[] = $this->orderLines->handleShipping($storePriceIncTax, $memo, $discountAllItems, false);
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

        return $orderLines;
    }

    /**
     * @param $couponCodeAmount
     * @param $discountAllItems
     * @param $memo
     *
     * @return array
     */
    private function itemOrderLines($couponCodeAmount, $discountAllItems, $memo)
    {
        $orderLines       = [];
        $storePriceIncTax = $this->storeConfig->storePriceIncTax($memo->getOrder());
        foreach ($memo->getAllItems() as $item) {
            $qty         = $item->getQty();
            $taxPercent  = $item->getOrderItem()->getTaxPercent();
            $productType = $item->getOrderItem()->getProductType();
            if ($qty > 0 && $productType != 'bundle') {
                $discountAmount = $item->getDiscountAmount();
                $originalPrice  = $item->getOrderItem()->getOriginalPrice();

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
                    $priceWithoutTax = $originalPrice;
                    $taxAmount       = $this->priceHandler->calculateTaxAmount($unitPrice, $taxPercent, $qty);
                }
                $itemDiscountInformation = $this->discountHandler->getItemDiscountInformation(
                    $originalPrice,
                    $price,
                    $discountAmount,
                    $qty,
                    $discountAllItems
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
                        $priceWithoutTax,
                        $taxAmount,
                        $discountedAmount,
                        $couponCodeAmount,
                        $catalogDiscount,
                        $storePriceIncTax,
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
     * @param $memo
     * @param $orderLines
     * @param $orderObject
     * @param $payment
     * @param $storeCode
     *
     * @throws ResponseHeaderException
     */
    private function sendRefundRequest($memo, $orderLines, $orderObject, $payment, $storeCode)
    {
        $refund = new RefundCapturedReservation($this->systemConfig->getAuth($storeCode));
        if ($memo->getTransactionId()) {
            $refund->setTransaction($payment->getLastTransId());
        }
        $refund->setAmount((float)number_format($memo->getGrandTotal(), 2, '.', ''));
        $refund->setOrderLines($orderLines);
        try {
            $refund->call();
        } catch (ResponseHeaderException $e) {
            $this->valitorLogger->addCritical('Response header exception: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->valitorLogger->addCritical('Exception: ' . $e->getMessage());
        }

        $rawResponse = $refund->getRawResponse();
        $body        = $rawResponse->getBody();
        //add information to the valitor log
        $this->valitorLogger->addInfo('Response body: ' . $body);

        //Update comments if refund fail
        $xml = simplexml_load_string($body);
        if ($xml->Body->Result == 'Error' || $xml->Body->Result == 'Failed') {
            $orderObject->addStatusHistoryComment('Refund failed: ' . $xml->Body->MerchantErrorMessage)
                        ->setIsCustomerNotified(false);
            $orderObject->getResource()->save($orderObject);
        }
        //throw exception if result is not success
        if ($xml->Body->Result != 'Success') {
            throw new \InvalidArgumentException('Could not refund captured reservation');
        }
    }
}
