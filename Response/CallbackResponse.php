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

namespace SDM\Altapay\Response;

use SDM\Altapay\Response\Embeds\Transaction;

class CallbackResponse extends AbstractResponse
{
    /**
     * Childs of the response
     *
     * @var array<string, array<string, mixed>>
     */
    protected $childs = [
        'Transactions' => [
            'class' => Transaction::class,
            'array' => 'Transaction'
        ],
    ];

    /**
     * @var string
     */
    public $shopOrderId;

    /**
     * @var int
     */
    public $currency;

    /**
     * @var string
     */
    public $type;

    /**
     * @var bool
     */
    public $embeddedWindow;

    /**
     * @var float
     */
    public $amount;

    /**
     * @var string
     */
    public $transactionId;

    /**
     * @var string
     */
    public $paymentId;

    /**
     * @var string
     */
    public $nature;

    /**
     * @var bool
     */
    public $requireCapture;

    /**
     * @var string
     */
    public $paymentStatus;

    /**
     * @var string
     */
    public $maskedCreditCard;

    /**
     * @var string
     */
    public $blacklistToken;

    /**
     * @var string
     */
    public $creditCardToken;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $Result;

    /**
     * @var string
     */
    public $MerchantErrorMessage;

    /**
     * @var string
     */
    public $CardHolderErrorMessage;

    /**
     * @var string
     */
    public $CardHolderMessageMustBeShown;

    /**
     * @var Transaction[]
     */
    public $Transactions;
}
