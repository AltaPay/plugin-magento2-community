<?php
/**
 * Copyright (c) 2016 Martin Aarhof
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SDM\Altapay\Request;

class OrderLine extends AbstractSerializer
{
    private static $goodsTypes = [
        'shipment', 'handling', 'item'
    ];

    /**
     * Description of item
     *
     * @var string
     */
    public $description;

    /**
     * Item number
     *
     * @var string
     */
    public $itemId;

    /**
     * Quantity
     *
     * @var float
     */
    public $quantity;

    /**
     * Unit price excluding sales tax
     *
     * @var float
     */
    public $unitPrice;

    /**
     * Tax percent
     *
     * @var float
     */
    public $taxPercent;

    /**
     * Tax amount should be the total tax amount for order line
     *
     * @var float
     */
    public $taxAmount;

    /**
     * Measurement unit
     *
     * @var string
     */
    public $unitCode;

    /**
     * The discount in percent
     *
     * @var float
     */
    public $discount;

    /**
     * The type of order line it is
     *
     * @var string
     */
    private $goodsType;

    /**
     * Full url for icon of the item
     *
     * @var string
     */
    public $imageUrl;

    /**
     * OrderLine constructor.
     * @param string $description
     * @param string $itemId
     * @param float $quantity
     * @param float $unitPrice
     */
    public function __construct($description = null, $itemId = null, $quantity = null, $unitPrice = null)
    {
        $this->description = $description;
        $this->itemId = $itemId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }

    /**
     * Set goods type
     *
     * @param string $goodsType
     * @return OrderLine
     */
    public function setGoodsType($goodsType)
    {
        if (! in_array($goodsType, self::$goodsTypes)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'goodsType should be one of "%s" you have selected "%s"',
                    implode('|', self::$goodsTypes),
                    $goodsType
                )
            );
        }

        $this->goodsType = $goodsType;
        return $this;
    }

    /**
     * Get goods type
     *
     * @return string
     */
    public function getGoodsType()
    {
        return $this->goodsType;
    }

    /**
     * Serialize a object
     *
     * @return array
     */
    public function serialize()
    {
        $output = [
            'description' => $this->get($this, 'description'),
            'itemId' => $this->get($this, 'itemId'),
            'quantity' => $this->get($this, 'quantity'),
            'unitPrice' => $this->get($this, 'unitPrice')
        ];

        if ($this->get($this, 'taxPercent') && $this->get($this, 'taxAmount')) {
            throw new \InvalidArgumentException('Only one of "taxPercent" and "taxAmount" should be used');
        }

        $fields = ['taxPercent', 'taxAmount', 'unitCode', 'discount', 'goodsType', 'imageUrl'];
        foreach ($fields as $field) {
            if (($value = $this->get($this, $field)) !== null) {
                $output[$field] = $value;
            }
        }

        return $output;
    }
}
