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
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Altapay\Api\Payments\CaptureReservation;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Response\CaptureReservationResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Logger\Logger;
use SDM\Altapay\Model\SystemConfig;
use Magento\Sales\Model\Order;
use SDM\Altapay\Helper\Data;
use SDM\Altapay\Helper\Config as storeConfig;
use SDM\Altapay\Model\Handler\OrderLinesHandler;
use Altapay\Api\Subscription\ChargeSubscription;
use Magento\Framework\Math\Random;
use SDM\Altapay\Model\ReconciliationIdentifierFactory;
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
     * @var ReconciliationIdentifierFactory
     */
    private $reconciliation;
    /**
     * @var Random
     */
    private $random;

    /**
     * CaptureObserver constructor.
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
        $this->systemConfig     = $systemConfig;
        $this->altapayLogger    = $altapayLogger;
        $this->order            = $order;
        $this->helper           = $helper;
        $this->storeConfig      = $storeConfig;
        $this->orderLines       = $orderLines;
        $this->reconciliation   = $reconciliation;
        $this->random           = $random;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws ResponseHeaderException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment     = $observer['payment'];
        $invoice     = $observer['invoice'];
        $incrementId = $invoice->getOrder()->getIncrementId();
        $paymentType = $payment->getAdditionalInformation('payment_type');
        $capture     = $payment->getAdditionalInformation('require_capture');
        $orderObject = $this->order->loadByIncrementId($incrementId);
        $storeCode   = $invoice->getStore()->getCode();
        
        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())
            && ((strtolower($paymentType) !== "paymentandcapture")
                || (filter_var($capture, FILTER_VALIDATE_BOOLEAN) === true))
        ) {
            //Create orderlines from order items
            $orderLines = $this->processInvoiceOrderLines($invoice);
            //Send request for payment refund
            $this->sendInvoiceRequest($paymentType, $invoice, $orderLines,
                $orderObject, $payment, $storeCode);
        }
    }

    /**
     * @param Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return array
     */
    private function processInvoiceOrderLines($invoice)
    {
        $couponCode       = $invoice->getDiscountDescription();
        $moduleVersion    = $invoice->getOrder()->getModuleVersion() ? $invoice->getOrder()->getModuleVersion() : '';
        $baseCurrency     = $this->storeConfig->useBaseCurrency($moduleVersion);
        $couponCodeAmount = $baseCurrency ? $invoice->getBaseDiscountAmount() : $invoice->getDiscountAmount();
        //order lines for items
        $orderLines = $this->itemOrderLines($invoice);
        //send the discount into separate orderline if discount applied to all items
        if (abs($couponCodeAmount) > 0) {
            //order lines for discounts
            $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
        }
        if ($invoice->getShippingInclTax() > 0) {
            //order lines for shipping
            $orderLines[] = $this->orderLines->shippingOrderLine($invoice, false);
        }
        if (!empty($this->fixedProductTax($invoice, $baseCurrency))) {
            //order lines for FPT
            $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($invoice, $baseCurrency));
        }

        return $orderLines;
    }

    /**
     * @param Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return array
     */
    public function itemOrderLines($invoice)
    {
        $orderLines       = [];
        $moduleVersion    = $invoice->getOrder()->getModuleVersion() ? $invoice->getOrder()->getModuleVersion() : '';
        $baseCurrency     = $this->storeConfig->useBaseCurrency($moduleVersion);

        foreach ($invoice->getAllItems() as $item) {
            $qty         = $item->getQty();
            $productType = $item->getOrderItem()->getProductType();
            $priceInclTax = $baseCurrency ? $item->getBasePriceInclTax() : $item->getPriceInclTax();
            if (
                ($qty > 0 && $productType != 'bundle' && $priceInclTax) ||
                ($productType === "bundle" && $item->getOrderItem()->getProduct()->getPriceType() == Price::PRICE_TYPE_FIXED)
            ) {
                $orderLines[] = $this->orderLines->itemOrderLine($item, $invoice->getOrder(), false);

            }
        }

        return $orderLines;
    }

    /**
     * @param Magento\Sales\Model\Order\Invoice $invoice
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
     * @param Magento\Sales\Model\Order\Invoice $invoice
     * @param array $orderLines
     * @param Magento\Sales\Model\Order $orderObject
     * @param array $payment
     * @param int|string $storeCode
     * @param string $paymentType
     *
     * @throws ResponseHeaderException
     */
    private function sendInvoiceRequest($paymentType, $invoice, $orderLines, $orderObject, $payment, $storeCode)
    {
        $moduleVersion  = $invoice->getOrder()->getModuleVersion() ? $invoice->getOrder()->getModuleVersion() : '';
        $baseCurrency   = $this->storeConfig->useBaseCurrency($moduleVersion);
        $grandTotal = $baseCurrency ? (float)$invoice->getBaseGrandTotal() : (float)$invoice->getGrandTotal();
        $payment    = $invoice->getOrder()->getPayment();
        $reconciliationIdentifier  = $this->random->getUniqueHash();
        $agreementDetail = $payment->getAdditionalInformation('agreement_detail');
        if ($paymentType === 'subscription' || $paymentType === 'subscriptionAndCharge') {
            $api = new ChargeSubscription($this->systemConfig->getAuth($storeCode));
            if(!empty($agreementDetail)){
                if($agreementDetail['type'] === "unscheduled") {
                    $api->setAgreement( 
                        [
                            "agreement_type" =>  $agreementDetail['type'],
                            "unscheduled_type" =>  $agreementDetail['unscheduled_type']
                        ]);
                } else {
                    $api->setAgreement(["agreement_type" =>  $agreementDetail['type']]);
                }
            }
        } else {
            $api = new CaptureReservation($this->systemConfig->getAuth($storeCode));
            if ($invoice->getTransactionId()) {
                $api->setInvoiceNumber($invoice->getTransactionId());
            }

            $totalCompensationAmount = $this->orderLines->totalCompensationAmount($orderLines, $grandTotal);
            if ($totalCompensationAmount > 0 || $totalCompensationAmount < 0) {
                $orderLines[] = $this->orderLines->compensationOrderLine(
                    "Compensation Amount",
                    "comp-amount",
                    $totalCompensationAmount
                );
            }

            $api->setOrderLines($orderLines);
            $shippingTrackingInfo = $this->shippingTrackingInfo($invoice);
            // Send shipping tracking info
            $api->setTrackingInfo($shippingTrackingInfo);
        }

        $api->setTransaction($payment->getLastTransId());
        $api->setAmount(round($grandTotal, 2));
        $api->setReconciliationIdentifier($reconciliationIdentifier);

        /** @var CaptureReservationResponse $response */
        try {
            $response = $api->call();

            $latestTransKey = $this->helper->getLatestTransaction($response->Transactions, 'subscription_payment');
    
            if (isset($response->Transactions[$latestTransKey])) {
                $transaction = $response->Transactions[$latestTransKey];
                $payment->setLastTransId($transaction->TransactionId);
                $payment->save();
            }
        } catch (ResponseHeaderException $e) {
            $this->altapayLogger->addInfoLog('Info', $e->getHeader());
            $this->altapayLogger->addCriticalLog('Exception', $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->altapayLogger->addCriticalLog('Exception', $e->getMessage());
        }

        $model = $this->reconciliation->create();
        $model->addData([
            "order_id"      => $invoice->getOrder()->getIncrementId(),
            "identifier"    => $reconciliationIdentifier,
            "type"          => 'captured'
        ]);
        $model->save();

        $rawResponse = $api->getRawResponse();
        if (!empty($rawResponse)) {
            $body = $rawResponse->getBody();
            $this->altapayLogger->addInfoLog('Info' , $body);
            //Update comments if capture fail
            $xml = simplexml_load_string($body);
            if ($xml->Body->Result == 'Error' || $xml->Body->Result == 'Failed' || $xml->Body->Result == 'Incomplete') {
                $orderObject->addStatusHistoryComment('Capture failed: ' . $xml->Body->MerchantErrorMessage)
                    ->setIsCustomerNotified(false);
                $orderObject->getResource()->save($orderObject);
            }

            $headData = [];
            foreach ($rawResponse->getHeaders() as $k => $v) {
                $headData[] = $k . ': ' . json_encode($v);
            }
            $this->altapayLogger->addInfoLog('Info', implode(", ", $headData));
        }
        if (!isset($response->Result) || $response->Result != 'Success') {
            throw new \InvalidArgumentException('Could not capture reservation');
        }
    }

    /**
     * @param Magento\Sales\Model\Order\Invoice $invoice
     * @param $baseCurrency
     * @return float
     */
    public function fixedProductTax($invoice, $baseCurrency)
    {
        $weeTaxAmount = 0.0;
        foreach ($invoice->getAllItems() as $item) {
            $weeTaxAmount += $baseCurrency ? $item->getBaseWeeeTaxAppliedRowAmount() : $item->getWeeeTaxAppliedRowAmount();
        }

        return $weeTaxAmount;
    }
}
