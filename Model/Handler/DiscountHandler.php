<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Handler;

use SDM\Altapay\Helper\Data;
use SDM\Altapay\Helper\Config as storeConfig;

/**
 * Class DiscountHandler
 * Handle discounts related calculations.
 */
class DiscountHandler
{

    /**
     * @var Helper Data
     */
    private $helper;
    /**
     * @var Helper Config
     */
    private $storeConfig;

    /**
     * Gateway constructor.
     *
     * @param Data        $helper
     * @param storeConfig $storeConfig
     */
    public function __construct(
        Data $helper,
        storeConfig $storeConfig
    ) {
        $this->helper      = $helper;
        $this->storeConfig = $storeConfig;
    }

    /**
     * @param $discountAmount
     * @param $productOriginalPrice
     * @param $quantity
     *
     * @return float|int
     */
    public function getItemDiscount($discountAmount, $productOriginalPrice, $quantity)
    {
        if ($discountAmount > 0) {
            $discountPercent = ($discountAmount * 100) / ($productOriginalPrice * $quantity);
        } else {
            $discountPercent = 0;
        }

        return $discountPercent;
    }

    /**
     * @param $order
     * @param $discountAllItems
     * @param $newOrder
     *
     * @return float
     */
    public function hiddenTaxDiscountCompensation($order, $discountAllItems, $newOrder)
    {
        $orderId = $order->getId();
        if (!$newOrder) {
            $orderId = $order->getOrder()->getId();
        }
        $compAmount         = $order->getShippingDiscountTaxCompensationAmount();
        $shippingTaxPercent = $this->helper->getOrderShippingTax($orderId);
        if (!empty($compAmount) && $compAmount > 0) {
            if (!$discountAllItems) {
                $compAmount = $compAmount + ($compAmount * ($shippingTaxPercent / 100));
            }
        }

        return round($compAmount, 3);
    }

    /**
     * Get the applied discount information.
     *
     * @param $item
     *
     * @return mixed
     */
    public function getAppliedDiscounts($item)
    {
        $appliedRule = $item->getAppliedRuleIds();
        $parentItem  = $item->getParentItem();
        // in case of bundle products get the discount information from the parent item
        if ($parentItem) {
            $parentItemType = $parentItem->getProductType();
            if ($parentItemType == "bundle") {
                $appliedRule = $parentItem->getAppliedRuleIds();
            }
        }

        return $appliedRule;
    }

    /**
     * Get discount amount if not applied to all items.
     *
     * @param $discountOnAllItems
     * @param $discount
     * @param $catalogDiscount
     *
     * @return int|string
     */
    public function orderLineDiscount($discountOnAllItems, $discount, $catalogDiscount)
    {
        if ($discountOnAllItems && !$catalogDiscount) {
            $discount = 0;
        }

        return number_format($discount, 2, '.', '');
    }

    /**
     * Get discount applied to shipping.
     *
     * @param $appliedRule
     *
     * @return array
     */
    public function getShippingDiscounts($appliedRule)
    {
        $shippingDiscounts = [];
        if (!empty($appliedRule)) {
            $appliedRuleArr = explode(",", $appliedRule);
            foreach ($appliedRuleArr as $ruleId) {
                //get rule discount information
                $couponCodeData = $this->storeConfig->getRuleInformationByID($ruleId);
                //check if coupon applied to shipping
                if ($couponCodeData['apply_to_shipping']) {
                    if (!in_array($ruleId, $shippingDiscounts)) {
                        $shippingDiscounts[] = $ruleId;
                    }
                }
            }
        }

        return $shippingDiscounts;
    }

    /**
     * Calculate catalog discount.
     *
     * @param $originalPrice
     * @param $discountedPrice
     *
     * @return float|int
     */
    public function catalogDiscount($originalPrice, $discountedPrice)
    {
        $discountAmount = (($originalPrice - $discountedPrice) / $originalPrice) * 100;

        return number_format($discountAmount, 2, '.', '');
    }

    /**
     * Calculate combination of cart and catalog price rule.
     *
     * @param $originalPrice
     * @param $rowTotal
     *
     * @return float|int
     */
    public function combinationDiscount($originalPrice, $rowTotal)
    {
        $discountAmount = $originalPrice - $rowTotal;
        $discountPercentage = ($discountAmount / $originalPrice) * 100;

        return number_format($discountPercentage, 2, '.', '');
    }

    /**
     * @param $originalPrice
     * @param $priceInclTax
     * @param $discountAmount
     * @param $quantity
     * @param $discountOnAllItems
     * @param $item
     * @param $taxAmount
     * @return array
     */
    public function getItemDiscountInformation(
        $originalPrice,
        $priceInclTax,
        $discountAmount,
        $quantity,
        $discountOnAllItems,
        $item,
        $taxAmount
    ) {
        $discount = ['discount' => 0, 'catalogDiscount' => false];
        $originalPriceWithTax = $originalPrice + $taxAmount;

        if ($originalPrice != 0 && $discountAmount && $originalPrice == $priceInclTax) {
            $discountAmount = ($discountAmount * 100) / ($originalPrice * $quantity);
        } elseif ($originalPrice > 0 && $originalPrice > $priceInclTax && empty($discountAmount)) {
            $discount['catalogDiscount'] = true;
            $discountAmount = $this->catalogDiscount($originalPrice, $priceInclTax);
        } elseif ($originalPrice > 0 && $originalPrice > $priceInclTax && $discountAmount) {
            $discount['catalogDiscount'] = true;
            $baseCurrency = $this->storeConfig->useBaseCurrency();
            $rowTotal = $item->getRowTotal() -
                $item->getDiscountAmount() +
                $item->getTaxAmount() +
                $item->getDiscountTaxCompensationAmount();

            if ($baseCurrency) {
                $rowTotal = $item->getBaseRowTotal() -
                    $item->getBaseDiscountAmount() +
                    $item->getBaseTaxAmount() +
                    $item->getBaseDiscountTaxCompensationAmount();
            }
            if (!$this->storeConfig->storePriceIncTax()) {
                $discountAmount = (($originalPriceWithTax - $rowTotal) * 100) / $originalPriceWithTax;
            } else {
                $discountAmount = $this->combinationDiscount($originalPrice, $rowTotal);
            }
        }
        $discount['discount'] = $this->orderLineDiscount($discountOnAllItems, $discountAmount, $discount['catalogDiscount']);

        return $discount;
    }

    /**
     * Check whether all items have discount.
     *
     * @param $orderItems
     *
     * @return bool
     */
    public function allItemsHaveDiscount($orderItems)
    {
        $discountOnAllItems = true;
        $baseCurrency = $this->storeConfig->useBaseCurrency();
        foreach ($orderItems as $item) {
            $appliedRule    = $item->getAppliedRuleIds();
            $productType    = $item->getProductType();
            $originalPrice  = $baseCurrency ? $item->getBaseOriginalPrice() : $item->getOriginalPrice();
            
            if ($this->storeConfig->storePriceIncTax()) {
                $price = $baseCurrency ? $item->getBasePriceInclTax() : $item->getPriceInclTax();
            } else {
                $price = $baseCurrency ? $item->getBasePrice() : $item->getPrice();
            }        
            if ($originalPrice > $price) {
                $discountOnAllItems = false;
            } elseif (!empty($appliedRule)) {
                $appliedRuleArr = explode(",", $appliedRule);
                foreach ($appliedRuleArr as $ruleId) {
                    $coupon = $this->storeConfig->getRuleInformationByID($ruleId);
                    if (!$coupon['apply_to_shipping'] && $productType != 'virtual' && $productType != 'downloadable') {
                        $discountOnAllItems = false;
                    }
                }
            } else {
                $discountOnAllItems = false;
            }
        }

        return $discountOnAllItems;
    }
}
