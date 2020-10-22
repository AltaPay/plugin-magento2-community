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

class Config extends AbstractSerializer
{
    private $callbackForm;

    private $callbackOk;

    private $callbackFail;

    private $callbackRedirect;

    private $callbackOpen;

    private $callbackNotification;

    private $callbackVerifyOrder;

    /**
     * @param string $callbackForm
     * @return Config
     */
    public function setCallbackForm($callbackForm)
    {
        $this->callbackForm = $callbackForm;
        return $this;
    }

    /**
     * @param string $callbackOk
     * @return Config
     */
    public function setCallbackOk($callbackOk)
    {
        $this->callbackOk = $callbackOk;
        return $this;
    }

    /**
     * @param string $callbackFail
     * @return Config
     */
    public function setCallbackFail($callbackFail)
    {
        $this->callbackFail = $callbackFail;
        return $this;
    }

    /**
     * @param string $callbackRedirect
     * @return Config
     */
    public function setCallbackRedirect($callbackRedirect)
    {
        $this->callbackRedirect = $callbackRedirect;
        return $this;
    }

    /**
     * @param string $callbackOpen
     * @return Config
     */
    public function setCallbackOpen($callbackOpen)
    {
        $this->callbackOpen = $callbackOpen;
        return $this;
    }

    /**
     * @param string $callbackNotification
     * @return Config
     */
    public function setCallbackNotification($callbackNotification)
    {
        $this->callbackNotification = $callbackNotification;
        return $this;
    }

    /**
     * @param string $callbackVerifyOrder
     * @return Config
     */
    public function setCallbackVerifyOrder($callbackVerifyOrder)
    {
        $this->callbackVerifyOrder = $callbackVerifyOrder;
        return $this;
    }

    /**
     * Serialize a object
     *
     * @return array
     */
    public function serialize()
    {
        return [
            'callback_form' => $this->callbackForm,
            'callback_ok' => $this->callbackOk,
            'callback_fail' => $this->callbackFail,
            'callback_redirect' => $this->callbackRedirect,
            'callback_open' => $this->callbackOpen,
            'callback_notification' => $this->callbackNotification,
            'callback_verify_order' => $this->callbackVerifyOrder,
        ];
    }
}
