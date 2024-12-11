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
     * Escaper
     *
     * @var Escaper
     */
    private $escaper;

    /**
     * OrderLinesHandler constructor.
     *
     * @param Data        $helper
     * @param storeConfig $storeConfig
     * @param Escaper     $escaper
     */
    public function __construct(
        Data $helper,
        storeConfig $storeConfig,
        Escaper $escaper
    ) {
        $this->helper          = $helper;
        $this->storeConfig     = $storeConfig;
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
        $orderLine = new OrderLine($couponCode, 'discount', 1, round($couponAmount, 3));
        $orderLine->taxAmount = 0;
        $orderLine->setGoodsType('handling');

        return $orderLine;
    }

    /**
     * @param $item
     * @param $order
     * @param $newOrder
     * @param $unitPrice
     *
     * @return OrderLine
     */
    public function itemOrderLine(
        $item,
        $order,
        $newOrder
    ) {
        $itemName = $item->getName();
        $taxAmount = $item->getTaxAmount();
        $baseCurrency = $this->storeConfig->useBaseCurrency();
        $unitPrice = $baseCurrency ? ($item->getBasePrice() ?? 0) : ($item->getPrice() ?? 0);

        if ($newOrder) {
            $quantity     = $item->getQtyOrdered();
            $itemId       = $item->getItemId();
            $productUrl   = $item->getProduct()->getProductUrl();
            $productThumb = $item->getProduct()->getThumbnail();
            $options      = $item->getData('product_options');
        } else {
            $quantity     = $item->getQty();
            $itemId       = $item->getOrderItem()->getItemId();
            $productUrl   = $item->getOrderItem()->getProduct()->getProductUrl();
            $productThumb = $item->getOrderItem()->getProduct()->getThumbnail();
            $options      = $item->getOrderItem()->getData('product_options');
        }

        if (isset($options['simple_name'])) {
            $itemName = $options["simple_name"];
        }
        $itemName              = $this->escaper->escapeHtml($itemName);
        $orderLine             = new OrderLine($itemName, $itemId, $quantity, $unitPrice);
        $orderLine->discount   = 0;
        $orderLine->taxAmount  = $taxAmount;
        $orderLine->productUrl = $productUrl;
        if (!empty($productThumb) && $productThumb !== 'no_selection') {
            $orderLine->imageUrl = $this->storeConfig->getProductImageUrl($order, $productThumb);
        }
        $orderLine->unitCode = $quantity > 1 ? 'units' : 'unit';
        $orderLine->setGoodsType('item');

        return $orderLine;
    }

    /**
     * @param $order
     * @param $newOrder
     *
     * @return OrderLine
     */
    public function shippingOrderLine($order, $newOrder)
    {
        $baseCurrency = $this->storeConfig->useBaseCurrency();
        $shippingTax    = $baseCurrency ? $order->getBaseShippingTaxAmount() : $order->getShippingTaxAmount();
        $shippingAmount = $baseCurrency ? $order->getBaseShippingAmount() : $order->getShippingAmount();
        $shipping       = $order->getShippingMethod(true);
        if (!$newOrder) {
            $shipping = $order->getOrder()->getShippingMethod(true);
        }
        $method       = $shipping['method'] ?? '';
        $carrier_code = $shipping['carrier_code'] ?? '';

        $shippingAmount = round($shippingAmount, 3);

        $orderLine            = new OrderLine($method, $carrier_code, 1, $shippingAmount);
        $orderLine->taxAmount = $shippingTax;
        $orderLine->discount  = 0;
        $orderLine->setGoodsType('shipment');

        return $orderLine;
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
        $orderLine = new OrderLine('FPT', 'FPT', 1, round($fixedTaxAmount, 3));
        $orderLine->setGoodsType('handling');
 
        return $orderLine;
    }

    /**
     * @param $orderLines
     * @param $total
     * @return float
     */
    public function totalCompensationAmount($orderLines, $total)
    {
        $orderLinesTotal = 0;
        foreach ($orderLines as $orderLine) {
            $orderLinePriceWithTax = ($orderLine->unitPrice * $orderLine->quantity) + $orderLine->taxAmount;
            $orderLinesTotal += $orderLinePriceWithTax - ($orderLinePriceWithTax * ($orderLine->discount / 100));
        }

        return round(($total - $orderLinesTotal), 3);
    }
}
