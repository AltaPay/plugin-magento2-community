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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\SalesRule\Model\RuleFactory;
use \Magento\Sales\Model\ResourceModel\Order\Tax\Item;
use \Magento\Tax\Model\Config as taxConfig;

/**
 * Class CaptureObserver
 *
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
     * @var rule
     */
    protected $rule;
    /**
     * @var taxItem
     */
    protected $taxItem;
    /**
     * @var taxConfig
     */
    private $taxConfig;

    /**
     * CaptureObserver constructor.
     *
     * @param SystemConfig         $systemConfig
     * @param Logger               $valitorLogger
     * @param Order                $order
     * @param ScopeConfigInterface $scopeConfig
     * @param RuleFactory          $rule
     * @param Item                 $taxItem
     * @param taxConfig            $taxConfig
     */
    public function __construct(
        SystemConfig $systemConfig,
        Logger $valitorLogger,
        Order $order,
        ScopeConfigInterface $scopeConfig,
        RuleFactory $rule,
        Item $taxItem,
        taxConfig $taxConfig
    ) {
        $this->systemConfig  = $systemConfig;
        $this->valitorLogger = $valitorLogger;
        $this->order         = $order;
        $this->scopeConfig   = $scopeConfig;
        $this->rule          = $rule;
        $this->taxItem       = $taxItem;
        $this->taxConfig     = $taxConfig;
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
        $invoice          = $observer['invoice'];
        $orderIncrementId = $invoice->getOrder()->getIncrementId();
        $orderObject      = $this->order->loadByIncrementId($orderIncrementId);
        $storeScope       = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storePriceIncTax = $this->storePriceIncTax($storeScope);
        $couponCode       = $invoice->getOrder()->getDiscountDescription();
        $storeCode        = $invoice->getStore()->getCode();
        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            $orderlines         = [];
            $couponCode         = $invoice->getDiscountDescription();
            $appliedRule        = $invoice->getOrder()->getAppliedRuleIds();
            $couponCodeAmount   = $invoice->getDiscountAmount();
            $compAmount         = $invoice->getOrder()->getShippingDiscountTaxCompensationAmount();
            $discountOnAllItems = $this->allItemsHaveDiscount($invoice->getOrder()->getAllVisibleItems());
            $shippingDiscounts  = array();
            if (!empty($appliedRule)) {
                $appliedRuleArr = explode(",", $appliedRule);
                foreach ($appliedRuleArr as $ruleId) {
                    $couponCodeData  = $this->rule->create()->load($ruleId);
                    $applyToShipping = $couponCodeData->getData('apply_to_shipping');
                    if ($applyToShipping) {
                        if (!in_array($ruleId, $shippingDiscounts)) {
                            $shippingDiscounts[] = $ruleId;
                        }
                    }
                }
            }

            /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
            foreach ($invoice->getAllItems() as $item) {
                $quantity    = $item->getQty();
                $taxPercent  = $item->getOrderItem()->getTaxPercent();
                $taxRate     = (1 + $taxPercent / 100);
                $productType = $item->getOrderItem()->getProductType();
                $itemName    = $item->getName();
                if ($productType == "configurable") {
                    $options = $item->getOrderItem()->getProductOptions();
                    if (isset($options["simple_name"])) {
                        $itemName = $options["simple_name"];
                    }
                }

                if ($quantity > 0 && $productType != 'bundle') {
                    $productPrice         = $item->getPrice();
                    $priceInclTax         = $item->getPriceInclTax();
                    $discountAmount       = $item->getDiscountAmount();
                    $originalPrice        = $item->getOrderItem()->getOriginalPrice();
                    $itemDiscount         = 0;
                    $unitPriceWithoutTax  = $originalPrice / $taxRate;
                    $catalogDiscountCheck = false;

                    if (!empty($discountAmount)) {
                        $itemDiscount = ($discountAmount * 100) / ($originalPrice * $quantity);
                    }

                    if ($storePriceIncTax) {
                        $unitPrice = bcdiv($unitPriceWithoutTax, 1, 2);
                        $taxAmount = ($unitPriceWithoutTax * ($taxPercent / 100)) * $quantity;
                        $taxAmount = number_format($taxAmount, 2, '.', '');

                        if ($originalPrice > 0 && $originalPrice > $priceInclTax && empty($discountAmount)) {
                            $catalogDiscountCheck = true;
                            $discountAmount       = (($originalPrice - $priceInclTax) / $originalPrice) * 100;
                            $itemDiscount         = number_format($discountAmount, 2, '.', '');
                        }
                    } else {
                        $unitPrice           = $originalPrice;
                        $unitPriceWithoutTax = $originalPrice;

                        if ($originalPrice > 0 && $originalPrice > $productPrice && empty($discountAmount)) {
                            $catalogDiscountCheck = true;
                            $discountAmount       = (($originalPrice - $productPrice) / $originalPrice) * 100;
                            $itemDiscount         = number_format($discountAmount, 2, '.', '');
                        }

                        $dataForPrice = $this->returnDataForPriceExcTax(
                            $item,
                            $unitPrice,
                            $taxPercent,
                            $quantity,
                            $discountOnAllItems
                        );
                        $taxAmount    = number_format($dataForPrice["rawTaxAmount"], 2, '.', '');
                    }
                    if ($priceInclTax) {
                        $orderline = new OrderLine(
                            $itemName,
                            $item->getOrderItem()->getItemId(),
                            $quantity,
                            $unitPrice
                        );
                        $orderline->setGoodsType('item');

                        if ($discountOnAllItems) {
                            $discountedAmount = 0;
                        } else {
                            $discountedAmount = $itemDiscount;
                        }
                        $discountedAmount           = number_format($discountedAmount, 2, '.', '');
                        $orderline->discount        = $discountedAmount;
                        $roundingCompensationAmount = $this->compensationAmountCal(
                            $item,
                            $unitPrice,
                            $unitPriceWithoutTax,
                            $taxAmount,
                            $discountedAmount,
                            $couponCodeAmount,
                            $storePriceIncTax,
                            $catalogDiscountCheck
                        );
                        $orderline->taxAmount       = $taxAmount;
                        $orderlines[]               = $orderline;
                        if ($roundingCompensationAmount > 0 || $roundingCompensationAmount < 0) {
                            $orderline            = new OrderLine(
                                "Compensation Amount",
                                "comp",
                                1,
                                $roundingCompensationAmount
                            );
                            $orderline->taxAmount = 0.00;
                            $orderlines[]         = $orderline;
                        }
                    }
                }
            }

            if ($discountOnAllItems == true && abs($couponCodeAmount) > 0) {
                if (empty($couponCode)) {
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

                //add shipping tax amount in separate column of request
                $discountPercentage = array();
                $itemDiscount       = 0;
                $shippingTax        = $invoice->getShippingTaxAmount();
                $shippingAmount     = $invoice->getShippingAmount();
                $shippingTaxPercent = $this->getOrderShippingTax($invoice->getOrder()->getId());

                if (!empty($shippingDiscounts)) {
                    foreach ($shippingDiscounts as $ruleId) {
                        $couponCodeData = $this->rule->create()->load($ruleId);
                        $simpleAction   = $couponCodeData->getData('simple_action');
                        $discountAmount = $couponCodeData->getData('discount_amount');
                        if ($simpleAction == 'by_percent') {
                            $discountPercentage[] = ($discountAmount / 100);
                        }
                    }
                    $itemDiscount = $this->getItemDiscountByPercentage($discountPercentage);
                }

                $compAmountDiscount = 0;
                if ($compAmount > 0) {
                    /* add discount rate*/
                    $compAmountDiscount = $compAmount + ($compAmount * ($itemDiscount / 100));
                    /*Add tax percentage in compensation amount*/
                    $compAmountDiscount = $compAmountDiscount + ($compAmountDiscount * ($shippingTaxPercent / 100));
                    $compAmountDiscount = number_format($compAmountDiscount, 2, '.', '');
                }

                if ($discountOnAllItems) {
                    $totalShipAmount = $shippingAmount + $compAmount;
                } else {
                    $totalShipAmount = $shippingAmount + $compAmountDiscount;
                }

                $totalShipAmount = number_format($totalShipAmount, 2, '.', '');

                $orderline = new OrderLine(
                    'Shipping',
                    'shipping',
                    1,
                    $totalShipAmount
                );

                if ($discountOnAllItems) {
                    $orderline->discount  = 0;
                    $orderline->taxAmount = $shippingTax;
                } else {
                    $orderline->discount = $itemDiscount;
                    if ($shippingTaxPercent > 0) {
                        $shippingAmount       = $shippingAmount * ($shippingTaxPercent / 100);
                        $orderline->taxAmount = number_format($shippingAmount, 2, '.', '');
                    } else {
                        $orderline->taxAmount = 0;
                    }
                }

                $orderline->setGoodsType('shipment');
                $orderlines[] = $orderline;
            }

            $api = new CaptureReservation($this->systemConfig->getAuth($storeCode));
            if ($invoice->getTransactionId()) {
                $api->setInvoiceNumber($invoice->getTransactionId());
            }

            $api->setAmount((float)number_format($invoice->getGrandTotal(), 2, '.', ''));
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
                $orderObject->addStatusHistoryComment('Capture failed: ' . $xml->Body->MerchantErrorMessage)
                            ->setIsCustomerNotified(false);
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
     * @param $orderItems
     *
     * @return bool
     */
    private function allItemsHaveDiscount($orderItems)
    {
        $discountOnAllItems = true;
        foreach ($orderItems as $item) {
            $appliedRule = $item->getAppliedRuleIds();
            $productType = $item->getProductType();
            if (!empty($appliedRule)) {
                $appliedRuleArr = explode(",", $appliedRule);
                foreach ($appliedRuleArr as $ruleId) {
                    $couponCodeData  = $this->rule->create()->load($ruleId);
                    $applyToShipping = $couponCodeData->getData('apply_to_shipping');
                    if (!$applyToShipping && $productType != 'virtual' && $productType != 'downloadable') {
                        $discountOnAllItems = false;
                    }
                }
            } else {
                $discountOnAllItems = false;
            }
        }

        return $discountOnAllItems;
    }

    /**
     * @param $item
     * @param $unitPrice
     * @param $taxPercent
     * @param $quantity
     * @param $discountOnAllItems
     *
     * @return mixed
     */
    private function returnDataForPriceExcTax(
        $item,
        $unitPrice,
        $taxPercent,
        $quantity,
        $discountOnAllItems
    ) {
        if ($discountOnAllItems) {
            $data["rawTaxAmount"] = $item->getTaxAmount();
        } else {
            $data["rawTaxAmount"] = ($unitPrice * ($taxPercent / 100)) * $quantity;
        }

        return $data;
    }

    /**
     * @param $orderID
     *
     * @return int
     */
    private function getOrderShippingTax($orderID)
    {
        $shippingTaxPercent = 0;
        $tax_items          = $this->taxItem->getTaxItemsByOrderId($orderID);
        if (!empty($tax_items) && is_array($tax_items)) {
            foreach ($tax_items as $item) {
                if ($item['taxable_item_type'] === 'shipping') {
                    $shippingTaxPercent += $item['tax_percent'];
                }
            }
        }

        return $shippingTaxPercent;
    }

    /**
     * @param $discountPercentage
     *
     * @return float|int|mixed
     */
    private function getItemDiscountByPercentage($discountPercentage)
    {
        $itemDiscount = 0;
        if (count($discountPercentage) == 1) {
            $itemDiscount = array_shift($discountPercentage);
            $itemDiscount = $itemDiscount * 100;
        } elseif (count($discountPercentage) > 1) {
            $discountSum     = array_sum($discountPercentage);
            $discountProduct = array_product($discountPercentage);
            $itemDiscount    = ($discountSum - $discountProduct) * 100;
        }

        return $itemDiscount;
    }

    /**
     * @param $store
     *
     * @return bool
     */
    private function checkSettingsTaxAfterDiscount($store = null)
    {
        return $this->taxConfig->applyTaxAfterDiscount($store);
    }

    /**
     * @param $storeScope
     *
     * @return bool
     */
    private function storePriceIncTax($storeScope)
    {
        if ((int)$this->scopeConfig->getValue('tax/calculation/price_includes_tax', $storeScope) === 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $item
     * @param $unitPrice
     * @param $unitPriceWithoutTax
     * @param $taxAmount
     * @param $discountedAmount
     * @param $couponCodeAmount
     * @param $storePriceIncTax
     * @param $catalogDiscountCheck
     *
     * @return float|int
     */
    private function compensationAmountCal(
        $item,
        $unitPrice,
        $unitPriceWithoutTax,
        $taxAmount,
        $discountedAmount,
        $couponCodeAmount,
        $storePriceIncTax,
        $catalogDiscountCheck
    ) {
        $taxPercent   = $item->getOrderItem()->getTaxPercent();
        $quantity     = $item->getQty();
        $itemRowTotal = $item->getOrderItem()->getBaseRowTotal();
        $compensation = 0;
        //Discount compensation calculation - Gateway calculation pattern
        $gatewaySubTotal = ($unitPrice * $quantity) + $taxAmount;
        $gatewaySubTotal = $gatewaySubTotal - ($gatewaySubTotal * ($discountedAmount / 100));
        // Magento calculation pattern
        if (abs($couponCodeAmount) > 0 && $storePriceIncTax) {
            $cmsPriceCal  = $unitPriceWithoutTax * $quantity;
            $cmsTaxCal    = $cmsPriceCal * ($taxPercent / 100);
            $cmsSubTotal  = $cmsPriceCal + $cmsTaxCal;
            $cmsSubTotal  = $cmsSubTotal - ($cmsSubTotal * ($discountedAmount / 100));
            $compensation = $cmsSubTotal - $gatewaySubTotal;
        } elseif ($catalogDiscountCheck || empty($couponCodeAmount)) {
            $cmsTaxCal    = $itemRowTotal * ($taxPercent / 100);
            $cmsSubTotal  = $itemRowTotal + $cmsTaxCal;
            $compensation = $cmsSubTotal - $gatewaySubTotal;
        }

        return $compensation;
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
