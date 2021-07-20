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
     * @return float|int
     */
    public function hiddenTaxDiscountCompensation($order, $discountAllItems, $newOrder)
    {
        $orderId = $order->getId();
        if ($newOrder == false) {
            $orderId = $order->getOrder()->getId();
        }
        $compAmount         = $order->getShippingDiscountTaxCompensationAmount();
        $shippingTaxPercent = $this->helper->getOrderShippingTax($orderId);
        if (!empty($compAmount) && $compAmount > 0) {
            if (!$discountAllItems) {
                $compAmount = $compAmount + ($compAmount * ($shippingTaxPercent / 100));
            }
        }

        return $compAmount;
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
     * @param $discountedPrice
     * @param $discountAmount
     * @param $quantity
     * @param $discountOnAllItems
     *
     * @return array
     */
    public function getItemDiscountInformation(
        $originalPrice,
        $discountedPrice,
        $discountAmount,
        $quantity,
        $discountOnAllItems
    ) {
        $discount = ['discount' => 0, 'catalogDiscount' => false];
        if (!empty($discountAmount)) {
            $discountAmount = ($discountAmount * 100) / ($originalPrice * $quantity);
        } elseif ($originalPrice > 0 && $originalPrice > $discountedPrice) {
            $discount['catalogDiscount'] = true;
            $discountAmount      = $this->catalogDiscount($originalPrice, $discountedPrice);
        } elseif ($originalPrice > 0 && $originalPrice > $discountedPrice && !empty($discountAmount)) {
            $discount['catalogDiscount'] = true;
            $discountAmount      = $this->combinationDiscount($originalPrice, $discountedPrice);
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
        foreach ($orderItems as $item) {
            $appliedRule    = $item->getAppliedRuleIds();
            $productType    = $item->getProductType();
            $originalPrice  = $item->getBaseOriginalPrice();
            
            if ($this->storeConfig->storePriceIncTax()) {
                $price = $item->getPriceInclTax();
            } else {
                $price = $item->getPrice();
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
