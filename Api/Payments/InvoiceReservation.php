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

namespace SDM\Altapay\Api\Payments;

use SDM\Altapay\AbstractApi;
use SDM\Altapay\Response\InvoiceReservationResponse;
use SDM\Altapay\Serializer\ResponseSerializer;
use SDM\Altapay\Traits;
use SDM\Altapay\Types;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceReservation extends AbstractApi
{
    use Traits\TerminalTrait;
    use Traits\ShopOrderIdTrait;
    use Traits\AmountTrait;
    use Traits\CurrencyTrait;
    use Traits\TransactionInfoTrait;
    use Traits\CustomerInfoTrait;
    use Traits\OrderlinesTrait;

    /**
     * The type of payment.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->unresolvedOptions['type'] = $type;
    }

    /**
     * For Arvato germany an account number and bank code (BLZ) can be passed in, to pay via a secure elv bank transfer.
     *
     * @param string $accountnumber
     */
    public function setAccountNumber($accountnumber)
    {
        $this->unresolvedOptions['accountNumber'] = $accountnumber;
    }

    /**
     * The source of the payment
     *
     * @param string $paymentsource
     */
    public function setPaymentSource($paymentsource)
    {
        $this->unresolvedOptions['payment_source'] = $paymentsource;
    }

    /**
     * For Arvato germany an account number and bank code (BLZ) can be passed in, to pay via a secure elv bank transfer
     *
     * @param string $bankcode
     */
    public function setBankCode($bankcode)
    {
        $this->unresolvedOptions['bankCode'] = $bankcode;
    }

    /**
     * If you wish to decide pr. Payment wich fraud detection service to use
     *
     * @param string $fraudservice
     */
    public function setFraudService($fraudservice)
    {
        $this->unresolvedOptions['fraud_service'] = $fraudservice;
    }

    /**
     * Configure options
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'terminal', 'shop_orderid', 'amount',
            'currency', 'type', 'payment_source',
        ]);

        $resolver->setAllowedValues('type', Types\PaymentTypes::getAllowed());
        $resolver->setDefault('type', 'payment');
        $resolver->setAllowedValues('payment_source', Types\PaymentSources::getAllowed());
        $resolver->setDefault('payment_source', 'eCommerce');

        $resolver->setDefined([
            'accountNumber', 'bankCode', 'fraud_service', 'customer_info', 'orderLines',
            'transaction_info'
        ]);
        $resolver->setAllowedTypes('accountNumber', 'string');
        $resolver->setAllowedTypes('bankCode', 'string');
        $resolver->setAllowedValues('fraud_service', Types\FraudServices::getAllowed());
    }

    /**
     * Handle response
     *
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    protected function handleResponse(Request $request, Response $response)
    {
        $body = (string) $response->getBody();
        $xml = simplexml_load_string($body);
        return ResponseSerializer::serialize(InvoiceReservationResponse::class, $xml->Body, false, $xml->Header);
    }

    /**
     * Url to api call
     *
     * @param array $options Resolved options
     * @return string
     */
    protected function getUrl(array $options)
    {
        $query = $this->buildUrl($options);
        return sprintf('createInvoiceReservation/?%s', $query);
    }
}
