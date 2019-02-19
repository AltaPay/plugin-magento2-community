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

namespace SDM\Valitor\Request;

class Giftcard
{
    /**
     * The card number of the gift card.
     *
     * @var string
     */
    private $account;

    /**
     * The gift card provider that this gift card is for. Currently the supported values are: test, PPS
     *
     * @var string
     */
    private $provider;

    /**
     * A previously returned gift card token can be used in place of the account identifier and provider.
     *
     * @var string
     */
    private $token;

    /**
     * Giftcard constructor.
     * @param string $account
     * @param string $provider
     * @param string $token
     */
    public function __construct($account, $provider, $token)
    {
        $this->account = $account;
        $this->provider = $provider;
        $this->token = $token;
    }

    /**
     * The card number of the gift card.
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * The gift card provider that this gift card is for. Currently the supported values are: test, PPS
     *
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * A previously returned gift card token can be used in place of the account identifier and provider.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}
