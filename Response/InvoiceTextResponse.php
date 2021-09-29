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

use SDM\Altapay\Response\Embeds\Address;
use SDM\Altapay\Response\Embeds\TextInfo;

class InvoiceTextResponse extends AbstractResponse
{
    /**
     * Childs of the response
     *
     * @var array<string, array<string, mixed>>
     */
    protected $childs = [
        'TextInfos' => [
            'class' => TextInfo::class,
            'array' => 'TextInfo'
        ],
        'Address' => [
            'class' => Address::class,
            'array' => false
        ]
    ];

    /** @var numeric */
    public $AccountOfferMinimumToPay;

    /** @var string */
    public $AccountOfferText;

    /** @var numeric */
    public $BankAccountNumber;

    /** @var string */
    public $LogonText;

    /** @var numeric */
    public $OcrNumber;

    /** @var string */
    public $MandatoryInvoiceText;

    /** @var numeric */
    public $InvoiceNumber;

    /** @var numeric */
    public $CustomerNumber;

    /**
     * @var \DateTime
     */
    public $InvoiceDate;

    /**
     * @var \DateTime
     */
    public $DueDate;

    /**
     * @var TextInfo[]
     */
    public $TextInfos;

    /**
     * @var Address
     */
    public $Address;

    /**
     * @param string $InvoiceDate
     *
     * @return $this
     */
    public function setInvoiceDate($InvoiceDate)
    {
        $this->InvoiceDate = new \DateTime($InvoiceDate);
        return $this;
    }

    /**
     * @param string $DueDate
     *
     * @return $this
     */
    public function setDueDate($DueDate)
    {
        $this->DueDate = new \DateTime($DueDate);
        return $this;
    }
}
