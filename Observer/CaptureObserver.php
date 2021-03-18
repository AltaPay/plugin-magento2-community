<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Observer;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use SDM\Altapay\Api\Payments\CaptureReservation;
use SDM\Altapay\Exceptions\ResponseHeaderException;
use SDM\Altapay\Response\CaptureReservationResponse;
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
use SDM\Altapay\Api\Subscription\ChargeSubscription;
/**
 * Class CaptureObserver
 * Handle the invoice capture functionality.
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
     * CaptureObserver constructor.
     *
     * @param SystemConfig      $systemConfig
     * @param Logger            $altapayLogger
     * @param Order             $order
     * @param Data              $helper
     * @param storeConfig       $storeConfig
     * @param OrderLinesHandler $orderLines
     * @param PriceHandler      $priceHandler
     * @param DiscountHandler   $discountHandler
     */
    public function __construct(
        SystemConfig $systemConfig,
        Logger $altapayLogger,
        Order $order,
        Data $helper,
        storeConfig $storeConfig,
        OrderLinesHandler $orderLines,
        PriceHandler $priceHandler,
        DiscountHandler $discountHandler
    ) {
        $this->systemConfig    = $systemConfig;
        $this->altapayLogger   = $altapayLogger;
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
     * @return void
     * @throws ResponseHeaderException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment          = $observer['payment'];
        $invoice          = $observer['invoice'];
        $orderIncrementId = $invoice->getOrder()->getIncrementId();
        $paymentType      = $payment->getAdditionalInformation('payment_type');
        $orderObject      = $this->order->loadByIncrementId($orderIncrementId);
        $storeCode        = $invoice->getStore()->getCode();
        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            //Create orderlines from order items
            $orderLines = $this->processInvoiceOrderLines($invoice);
            //Send request for payment refund
            $this->sendInvoiceRequest($paymentType, $invoice, $orderLines, $orderObject, $payment, $storeCode);
        }
    }

    /**
     * @param $invoice
     *
     * @return array
     */
    private function processInvoiceOrderLines($invoice)
    {
        $couponCode       = $invoice->getDiscountDescription();
        $couponCodeAmount = $invoice->getDiscountAmount();
        //Return true if discount enabled on all items
        $discountAllItems = $this->discountHandler->allItemsHaveDiscount($invoice->getOrder()->getAllVisibleItems());
        //order lines for items
        $orderLines = $this->itemOrderLines($couponCodeAmount, $invoice, $discountAllItems);
        //send the discount into separate orderline if discount applied to all items
        if ($discountAllItems && abs($couponCodeAmount) > 0) {
            //order lines for discounts
            $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
        }
        if ($invoice->getShippingInclTax() > 0) {
            //order lines for shipping
            $orderLines[] = $this->orderLines->handleShipping($invoice, $discountAllItems, false);
            //Shipping Discount Tax Compensation Amount
            $compAmount = $this->discountHandler->hiddenTaxDiscountCompensation($invoice, $discountAllItems, false);
            if ($compAmount > 0 && $discountAllItems == false) {
                $orderLines[] = $this->orderLines->compensationOrderLine(
                    "Shipping compensation",
                    "comp-ship",
                    $compAmount
                );
            }
        }
        if(!empty($this->fixedProductTax($invoice))){
            //order lines for FPT
            $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($invoice));
        }

        return $orderLines;
    }

    /**
     * @param $couponCodeAmount
     * @param $invoice
     * @param $discountAllItems
     *
     * @return array
     */
    private function itemOrderLines($couponCodeAmount, $invoice, $discountAllItems)
    {
        $orderLines       = [];
        $storePriceIncTax = $this->storeConfig->storePriceIncTax($invoice->getOrder());
        foreach ($invoice->getAllItems() as $item) {
            $qty         = $item->getQty();
            $taxPercent  = $item->getOrderItem()->getTaxPercent();
            $productType = $item->getOrderItem()->getProductType();
            if ($qty > 0 && $productType != 'bundle' && $item->getPriceInclTax()) {
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
                $discountedAmount        = $itemDiscountInformation['discount'];
                $catalogDiscountCheck    = $itemDiscountInformation['catalogDiscount'];
                $orderLines[]            = $this->orderLines->itemOrderLine(
                    $item,
                    $unitPrice,
                    $discountedAmount,
                    $taxAmount,
                    $invoice->getOrder(),
                    false
                );
                $roundingCompensation    = $this->priceHandler->compensationAmountCal(
                    $item,
                    $unitPrice,
                    $priceWithoutTax,
                    $taxAmount,
                    $discountedAmount,
                    $couponCodeAmount,
                    $catalogDiscountCheck,
                    $storePriceIncTax,
                    false
                );
                // check if rounding compensation amount, send in the separate orderline
                if ($roundingCompensation > 0 || $roundingCompensation < 0) {
                    $orderLines[] = $this->orderLines->compensationOrderLine(
                        "Compensation Amount",
                        "comp-" . $item->getOrderItem()->getItemId(),
                        $roundingCompensation
                    );
                }
            }
        }

        return $orderLines;
    }

    /**
     * @param $invoice
     *
     * @return array
     */
    private function shippingTrackingInfo($invoice)
    {
        $trackingInfo     = [];
        $tracksCollection = $invoice->getOrder()->getTracksCollection();
        $trackItems       = $tracksCollection->getItems();

        if ($trackItems && is_array($trackItems)) {
            foreach ($trackItems as $track) {
                $trackingInfo[] = [
                    'shippingCompany' => $track->getTitle(),
                    'trackingNumber'  => $track->getTrackNumber()
                ];
            }
        }

        return $trackingInfo;
    }

    /**
     * @param $invoice
     * @param $orderLines
     * @param $orderObject
     * @param $payment
     * @param $storeCode
     * @param $paymentType
     *
     * @throws ResponseHeaderException
     */
    private function sendInvoiceRequest($paymentType, $invoice, $orderLines, $orderObject, $payment, $storeCode)
    {
        if ($paymentType === 'subscription') {
            $api = new ChargeSubscription($this->systemConfig->getAuth($storeCode));
            $api->setTransaction($payment->getLastTransId());
            $api->setAmount(round($invoice->getGrandTotal()));
        } else {
            $api = new CaptureReservation($this->systemConfig->getAuth($storeCode));
            if ($invoice->getTransactionId()) {
                $api->setInvoiceNumber($invoice->getTransactionId());
            }
            $api->setAmount(round($invoice->getGrandTotal()));
            $api->setOrderLines($orderLines);
            $shippingTrackingInfo = $this->shippingTrackingInfo($invoice);
            // Send shipping tracking info
            $api->setTrackingInfo($shippingTrackingInfo);
            $api->setTransaction($payment->getLastTransId());
        }
        /** @var CaptureReservationResponse $response */
        try {
            $response = $api->call();
        } catch (ResponseHeaderException $e) {
            $this->altapayLogger->addInfoLog('Info', $e->getHeader());
            $this->altapayLogger->addCriticalLog('Response header exception', $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->altapayLogger->addCriticalLog('Exception', $e->getMessage());
        }

        $rawResponse = $api->getRawResponse();
        if (!empty($rawResponse)) {
            $body = $rawResponse->getBody();
            $this->altapayLogger->addInfo('Response body: ' . $body);
            //Update comments if capture fail
            $xml = simplexml_load_string($body);
            if ($xml->Body->Result == 'Error' || $xml->Body->Result == 'Failed') {
                $orderObject->addStatusHistoryComment('Capture failed: ' . $xml->Body->MerchantErrorMessage)
                            ->setIsCustomerNotified(false);
                $orderObject->getResource()->save($orderObject);
            }

            $headData = [];
            foreach ($rawResponse->getHeaders() as $k => $v) {
                $headData[] = $k . ': ' . json_encode($v);
            }
            $this->altapayLogger->addInfoLog('Response headers', implode(", ", $headData));
        }
        if (!isset($response->Result) || $response->Result != 'Success') {
            throw new \InvalidArgumentException('Could not capture reservation');
        }
    }

    /**
     * @param $order
     *
     * @return float|int
     */
    public function fixedProductTax($invoice){

        $weeTaxAmount = 0;
        foreach ($invoice->getAllItems() as $item) {
           $weeTaxAmount +=  $item->getWeeeTaxAppliedRowAmount();
        }

       return $weeTaxAmount;
    }
}
