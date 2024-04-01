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
     * @param $couponAmount
     * @param $itemDiscount
     * @param $discountAllItems
     * @return array
     */
    public function dataForPrice($item, $unitPrice, $couponAmount, $itemDiscount, $discountAllItems)
    {
        $data["catalogDiscount"] = false;
        $taxPercent              = $item->getTaxPercent();
        $quantity                = $item->getQtyOrdered();
        $baseCurrency            = $this->storeConfig->useBaseCurrency();
        $originalPrice           = $baseCurrency ? $item->getBaseOriginalPrice() : $item->getOriginalPrice();
        $data["taxAmount"]       = $this->calculateTaxAmount($unitPrice, $taxPercent, $quantity);
        $rowTotal                = ($item->getRowTotal()-$item->getDiscountAmount()+$item->getTaxAmount()+$item->getDiscountTaxCompensationAmount());

        if($baseCurrency) {
            $rowTotal = ($item->getBaseRowTotal()-$item->getBaseDiscountAmount()+$item->getBaseTaxAmount()+$item->getBaseDiscountTaxCompensationAmount());
        }
        if ($this->storeConfig->storePriceIncTax()) {
            $price = $baseCurrency ? $item->getBasePriceInclTax() : $item->getPriceInclTax();
        } else {
            $price = $baseCurrency ? $item->getBasePrice() : $item->getPrice();
        }
        if ($originalPrice > $price && abs((float)$couponAmount) > 0 && !$discountAllItems) {
            $originalPrice = $originalPrice * $quantity;
            $originalPriceWithTax = $originalPrice + $data["taxAmount"];
            $data["catalogDiscount"] = true;
            if (!$this->storeConfig->storePriceIncTax()) {
                $discountAmount = $originalPriceWithTax - $rowTotal;
                $discountPercentage = ($discountAmount * 100) / $originalPriceWithTax;
                $data["discount"] = $discountPercentage;
            } else {
                $data["discount"] = $this->discountHandler->combinationDiscount($originalPrice, $rowTotal);
            }
        } else if ($originalPrice > $price && !(float)$couponAmount) {
            $data["catalogDiscount"] = true;
            $data["discount"] = $this->discountHandler->catalogDiscount($originalPrice, $price);
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
     * @param $taxAmount
     * @param $discountedAmount
     * @param $newOrder
     *
     * @return float
     */
    public function compensationAmountCal(
        $item,
        $unitPrice,
        $taxAmount,
        $discountedAmount,
        $newOrder
    )
    {
        if ($newOrder) {
            $quantity = $item->getQtyOrdered();
        } else {
            $quantity = $item->getQty();
        }
        //Discount compensation calculation - Gateway calculation pattern
        $gatewaySubTotal = ($unitPrice * $quantity) + $taxAmount;
        $gatewaySubTotal = $gatewaySubTotal - ($gatewaySubTotal * ($discountedAmount / 100));
        $baseCurrency = $this->storeConfig->useBaseCurrency();
        $cmsSubTotal = $item->getRowTotal() - $item->getDiscountAmount() + $item->getTaxAmount() + $item->getDiscountTaxCompensationAmount();

        if ($baseCurrency) {
            $cmsSubTotal = $item->getBaseRowTotal() - $item->getBaseDiscountAmount() + $item->getBaseTaxAmount() + $item->getBaseDiscountTaxCompensationAmount();
        }

        return round(($cmsSubTotal - $gatewaySubTotal), 3);
    }
}
