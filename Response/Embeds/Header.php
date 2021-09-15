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

class Header extends AbstractResponse
{

    /**
     * Date for the response
     *
     * @var \DateTime
     */
    public $Date;

    /** @var string */
    public $Path;

    /** @var numeric-string */
    public $ErrorCode;

    /** @var string */
    public $ErrorMessage;

    /**
     * Set date
     *
     * @param string $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->Date = new \DateTime($date);

        return $this;
    }

    /**
     * Set Path
     *
     * @param string $Path
     *
     * @return $this
     */
    public function setPath($Path)
    {
        $this->Path = $Path;

        return $this;
    }

    /**
     * Set ErrorCode
     *
     * @param numeric-string $ErrorCode
     *
     * @return $this
     */
    public function setErrorCode($ErrorCode)
    {
        $this->ErrorCode = $ErrorCode;

        return $this;
    }

    /**
     * Set ErrorMessage
     *
     * @param string $ErrorMessage
     *
     * @return $this
     */
    public function setErrorMessage($ErrorMessage)
    {
        $this->ErrorMessage = $ErrorMessage;

        return $this;
    }
}
