<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Handler;

use SDM\Altapay\Helper\Config as storeConfig;

/**
 * Class PriceHandler
 * Handler class for the price related calculations.
 */
class PriceHandler
{

    /**
     * @var Helper Config
     */
    private $storeConfig;
    /**
     * @var DiscountHandler
     */
    private $discountHandler;

    /**
     * CaptureObserver constructor.
     *
     * @param storeConfig     $storeConfig
     * @param DiscountHandler $discountHandler
     */
    public function __construct(
        storeConfig $storeConfig,
        DiscountHandler $discountHandler
    ) {
        $this->storeConfig     = $storeConfig;
        $this->discountHandler = $discountHandler;
    }

    /**
     * @param $item
     * @param $unitPrice
     * @param $couponCode
     * @param $itemDiscount
     *
     * @return mixed
     */
    public function dataForPrice($item, $unitPrice, $couponCode, $itemDiscount)
    {
        $data["catalogDiscount"] = false;
        $taxPercent              = $item->getTaxPercent();
        $quantity                = $item->getQtyOrdered();
        $originalPrice           = $item->getBaseOriginalPrice();
        $data["taxAmount"]       = $this->calculateTaxAmount($unitPrice, $taxPercent, $quantity);
        $rowTotal = ($item->getRowTotal()-$item->getDiscountAmount()+$item->getTaxAmount()+$item->getDiscountTaxCompensationAmount());
        if ($this->storeConfig->storePriceIncTax()) {
            $price = $item->getPriceInclTax();
        } else {
            $price = $item->getPrice();
        }
        if ($originalPrice > $price && empty($couponCode)) {
            $data["catalogDiscount"] = true;
            $data["discount"]        = $this->discountHandler->catalogDiscount($originalPrice, $price);
        } 
        elseif ($originalPrice > $price && !empty($couponCode)) {
            $originalPrice = $originalPrice * $quantity;
            $data["catalogDiscount"] = true;
            $data["discount"]        = $this->discountHandler->combinationDiscount($originalPrice, $rowTotal);
        } else {
            $data["discount"] = $itemDiscount;
        }

        return $data;
    }

    /**
     * @param $unitPrice
     * @param $taxPercent
     * @param $quantity
     *
     * @return string
     */
    public function calculateTaxAmount($unitPrice, $taxPercent, $quantity)
    {
        $taxAmount = ($unitPrice * ($taxPercent / 100)) * $quantity;

        return number_format($taxAmount, 2, '.', '');
    }

    /**
     * @param $price
     * @param $taxPercentage
     *
     * @return float|int
     */
    public function getPriceWithoutTax($price, $taxPercentage)
    {

        return ($price / (1 + $taxPercentage / 100));
    }

    /**
     * @param $item
     * @param $unitPrice
     * @param $unitPriceWithoutTax
     * @param $taxAmount
     * @param $discountedAmount
     * @param $couponCodeAmount
     * @param $catalogDiscountCheck
     * @param $storePriceIncTax
     * @param $newOrder
     *
     * @return float|int
     */
    public function compensationAmountCal(
        $item,
        $unitPrice,
        $unitPriceWithoutTax,
        $taxAmount,
        $discountedAmount,
        $couponCodeAmount,
        $catalogDiscountCheck,
        $storePriceIncTax,
        $newOrder
    ) {
        if ($newOrder) {
            $quantity   = $item->getQtyOrdered();
            $taxPercent = $item->getTaxPercent();
        } else {
            $quantity   = $item->getQty();
            $taxPercent = $item->getOrderItem()->getTaxPercent();
        }

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
        } elseif ($catalogDiscountCheck || empty($couponCodeAmount) || $couponCodeAmount == 0) {
            $cmsSubTotal  = $item->getBaseRowTotal() + $item->getBaseTaxAmount();
            $compensation = $cmsSubTotal - $gatewaySubTotal;
        }

        return $compensation;
    }
}
