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

namespace SDM\Altapay\Response\Embeds;

use SDM\Altapay\Response\AbstractResponse;

class PaymentNatureService extends AbstractResponse
{
    public $name;

    /**
     * @var bool
     */
    public $SupportsRefunds;

    /**
     * @var bool
     */
    public $SupportsRelease;

    /**
     * @var bool
     */
    public $SupportsMultipleCaptures;

    /**
     * @var bool
     */
    public $SupportsMultipleRefunds;

    /**
     * @param bool $SupportsRefunds
     * @return PaymentNatureService
     */
    public function setSupportsRefunds($SupportsRefunds)
    {
        $this->SupportsRefunds = filter_var($SupportsRefunds, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * @param bool $SupportsRelease
     * @return PaymentNatureService
     */
    public function setSupportsRelease($SupportsRelease)
    {
        $this->SupportsRelease = filter_var($SupportsRelease, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * @param bool $SupportsMultipleCaptures
     * @return PaymentNatureService
     */
    public function setSupportsMultipleCaptures($SupportsMultipleCaptures)
    {
        $this->SupportsMultipleCaptures = filter_var($SupportsMultipleCaptures, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * @param bool $SupportsMultipleRefunds
     * @return PaymentNatureService
     */
    public function setSupportsMultipleRefunds($SupportsMultipleRefunds)
    {
        $this->SupportsMultipleRefunds = filter_var($SupportsMultipleRefunds, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }
}
