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

/**
 * Credit card class
 */
class Card
{

    /**
     * Card number
     *
     * @var string
     */
    private $cardNumber;

    /**
     * Card expiry month
     *
     * @var string
     */
    private $expiryMonth;

    /**
     * Card expiry year
     *
     * @var string
     */
    private $expiryYear;

    /**
     * Card security code
     *
     * @var string|null
     */
    private $cvc;

    /**
     * Card constructor.
     *
     * @param string $cardNumber
     * @param string $expiryMonth
     * @param string $expiryYear
     * @param string $cvc
     */
    public function __construct($cardNumber, $expiryMonth, $expiryYear, $cvc = null)
    {
        $this->cardNumber = $cardNumber;
        $this->expiryMonth = $expiryMonth;
        $this->expiryYear = $expiryYear;
        $this->cvc = $cvc;
    }

    /**
     * Get card number
     *
     * @return string
     */
    public function getCardNumber()
    {
        return $this->cardNumber;
    }

    /**
     * Get expiry month
     *
     * @return string
     */
    public function getExpiryMonth()
    {
        return $this->expiryMonth;
    }

    /**
     * Get expiry year
     *
     * @return string
     */
    public function getExpiryYear()
    {
        return $this->expiryYear;
    }

    /**
     * Get cvc
     *
     * @return string|null
     */
    public function getCvc()
    {
        return $this->cvc;
    }
}
