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
use Altapay\Request\OrderLine;
use Magento\Framework\Escaper;

/**
 * Class OrderLinesHandler
 * To create the orderlines for the order.
 */
class OrderLinesHandler
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
     * @var DiscountHandler
     */
    private $discountHandler;
    /**
     * @var PriceHandler
     */
    private $priceHandler;
    /**
     * Escaper
     *
     * @var Escaper
     */
    private $escaper;

    /**
     * OrderLinesHandler constructor.
     *
     * @param Data            $helper
     * @param storeConfig     $storeConfig
     * @param DiscountHandler $discountHandler
     * @param PriceHandler    $priceHandler
     * @param Escaper         $escaper
     */
    public function __construct(
        Data $helper,
        storeConfig $storeConfig,
        DiscountHandler $discountHandler,
        PriceHandler $priceHandler,
        Escaper $escaper
    ) {
        $this->helper          = $helper;
        $this->storeConfig     = $storeConfig;
        $this->discountHandler = $discountHandler;
        $this->priceHandler    = $priceHandler;
        $this->escaper         = $escaper;
    }

    /**
     * @param $itemId
     * @param $description
     * @param $compensationAmount
     *
     * @return OrderLine
     */
    public function compensationOrderLine($description, $itemId, $compensationAmount)
    {
        $orderLine             = new OrderLine($description, $itemId, 1, $compensationAmount);
        $orderLine->taxAmount  = 0.00;
        $orderLine->taxPercent = 0.00;
        $orderLine->unitCode   = 'unit';
        $orderLine->discount   = 0.00;
        $orderLine->setGoodsType('handling');

        return $orderLine;
    }

    /**
     * @param $couponAmount
     * @param $couponCode
     *
     * @return OrderLine
     */
    public function discountOrderLine($couponAmount, $couponCode)
    {
        if (empty($couponCode)) {
            $couponCode = 'Cart Price Rule';
        }
        // Handling price reductions
        $orderLine = new OrderLine($couponCode, 'discount', 1, $couponAmount);
        $orderLine->setGoodsType('handling');

        return $orderLine;
    }

    /**
     * @param $shippingAmount
     * @param $method
     * @param $carrier_code
     * @param $taxAmount
     * @param $taxPercent
     * @param $discount
     *
     * @return OrderLine
     */
    private function shippingOrderLine($shippingAmount, $method, $carrier_code, $taxAmount, $taxPercent, $discount)
    {
        $orderLine             = new OrderLine($method, $carrier_code, 1, $shippingAmount);
        $orderLine->taxAmount  = $taxAmount;
        $orderLine->discount   = $discount;
        $orderLine->taxPercent = $taxPercent;
        $orderLine->setGoodsType('shipment');

        return $orderLine;
    }

    /**
     * @param $item
     * @param $unitPrice
     * @param $discount
     * @param $taxAmount
     * @param $order
     * @param $newOrder
     *
     * @return OrderLine
     */
    public function itemOrderLine(
        $item,
        $unitPrice,
        $discount,
        $taxAmount,
        $order,
        $newOrder
    ) {
        $itemName = $item->getName();

        if ($newOrder) {
            $quantity     = $item->getQtyOrdered();
            $itemId       = $item->getItemId();
            $productUrl   = $item->getProduct()->getProductUrl();
            $productThumb = $item->getProduct()->getThumbnail();
            $taxPercent   = $item->getTaxPercent();
            $options      = $item->getData('product_options');
        } else {
            $quantity     = $item->getQty();
            $itemId       = $item->getOrderItem()->getItemId();
            $productUrl   = $item->getOrderItem()->getProduct()->getProductUrl();
            $productThumb = $item->getOrderItem()->getProduct()->getThumbnail();
            $taxPercent   = $item->getOrderItem()->getTaxPercent();
            $options      = $item->getOrderItem()->getData('product_options');
        }

        if (isset($options['simple_name'])) {
            $itemName = $options["simple_name"];
        }
        $itemName              = $this->escaper->escapeHtml($itemName);
        $orderLine             = new OrderLine($itemName, $itemId, $quantity, $unitPrice);
        $orderLine->discount   = $discount;
        $orderLine->taxAmount  = $taxAmount;
        $orderLine->taxPercent = $taxPercent;
        $orderLine->productUrl = $productUrl;
        if (!empty($productThumb) && $productThumb !== 'no_selection') {
            $orderLine->imageUrl = $this->storeConfig->getProductImageUrl($order, $productThumb);
        }
        if ($quantity > 1) {
            $orderLine->unitCode = "units";
        } else {
            $orderLine->unitCode = "unit";
        }
        $orderLine->setGoodsType('item');

        return $orderLine;
    }

    /**
     * @param $order
     * @param $discountOnAllItems
     * @param $newOrder
     *
     * @return OrderLine
     */
    public function handleShipping($order, $discountOnAllItems, $newOrder)
    {
        //add shipping tax amount in separate column of request
        $discount       = 0;
        $shippingTax    = $order->getShippingTaxAmount();
        $shippingAmount = $order->getShippingAmount();
        $orderId        = $order->getId();
        $shipping       = $order->getShippingMethod(true);
        if (!$newOrder) {
            $orderId  = $order->getOrder()->getId();
            $shipping = $order->getOrder()->getShippingMethod(true);
        }
        $taxPercent   = $this->helper->getOrderShippingTax($orderId);
        $method       = $shipping['method'] ?? '';
        $carrier_code = $shipping['carrier_code'] ?? '';
        if ($discountOnAllItems) {
            $discount = 0;
        } else {
            if ($shippingAmount > 0) {
                $discount = ($order->getShippingDiscountAmount() / $shippingAmount) * 100;
            }
        }

        if ($taxPercent > 0) {
            $shippingTax = $shippingAmount * ($taxPercent / 100);
            $shippingTax = round($shippingTax, 2);
        }

        return $this->shippingOrderLine($shippingAmount, $method, $carrier_code, $shippingTax, $taxPercent, $discount);
    }

    /**
     * @param $order
     *
     * @return bool
     */
    public function sendShipment($order)
    {
        foreach ($order->getAllVisibleItems() as $item) {
            $productType = $item->getProductType();
            if ($productType != 'virtual' && $productType != 'downloadable') {
                return true;
            }
        }
        return false;
    }
    /**
     * @param $fixedTaxAmount
     *
     * @return OrderLine
     */
    public function fixedProductTaxOrderLine($fixedTaxAmount)
    {
        $orderLine = new OrderLine('FPT', 'FPT', 1, $fixedTaxAmount);
        $orderLine->setGoodsType('handling');
 
        return $orderLine;
    }
}
