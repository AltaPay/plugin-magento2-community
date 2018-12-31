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

namespace SDM\Altapay\Api\Ecommerce;

use SDM\Altapay\Response\CallbackResponse;
use SDM\Altapay\Serializer\ResponseSerializer;

class Callback
{
    private $postedData;

    public function __construct($postedData)
    {
        $this->postedData = $postedData;
    }

    /**
     * @return CallbackResponse
     */
    public function call()
    {
        $xml = simplexml_load_string($this->postedData['xml']);
        /** @var CallbackResponse $response */
        $response = ResponseSerializer::serialize(CallbackResponse::class, $xml->Body, false, $xml->Header);
        if (isset($this->postedData['shop_orderid'])) {
            $response->shopOrderId = $this->postedData['shop_orderid'];
        }

        if (isset($this->postedData['currency'])) {
            $response->currency = $this->postedData['currency'];
        }

        if (isset($this->postedData['type'])) {
            $response->type = $this->postedData['type'];
        }

        if (isset($this->postedData['embedded_window'])) {
            $response->embeddedWindow = (bool)$this->postedData['embedded_window'];
        }

        if (isset($this->postedData['amount'])) {
            $response->amount = (float)$this->postedData['amount'];
        }

        if (isset($this->postedData['transaction_id'])) {
            $response->transactionId = $this->postedData['transaction_id'];
        }

        if (isset($this->postedData['payment_id'])) {
            $response->paymentId = $this->postedData['payment_id'];
        }

        if (isset($this->postedData['shop_orderid'])) {
            $response->shopOrderId = $this->postedData['shop_orderid'];
        }

        if (isset($this->postedData['nature'])) {
            $response->nature = $this->postedData['nature'];
        }

        if (isset($this->postedData['require_capture'])) {
            $response->requireCapture = $this->postedData['require_capture'];
        }

        if (isset($this->postedData['payment_status'])) {
            $response->paymentStatus = $this->postedData['payment_status'];
        }

        if (isset($this->postedData['masked_credit_card'])) {
            $response->maskedCreditCard = $this->postedData['masked_credit_card'];
        }

        if (isset($this->postedData['credit_card_masked_pan'])) {
            $response->maskedCreditCard = $this->postedData['credit_card_masked_pan'];
        }

        if (isset($this->postedData['blacklist_token'])) {
            $response->blacklistToken = $this->postedData['blacklist_token'];
        }

        if (isset($this->postedData['credit_card_token'])) {
            $response->creditCardToken = $this->postedData['credit_card_token'];
        }

        if (isset($this->postedData['status'])) {
            $response->status = $this->postedData['status'];
        }

        return $response;
    }
}
