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
use SDM\Altapay\Exceptions;
use SDM\Altapay\Response\RefundResponse;
use SDM\Altapay\Serializer\ResponseSerializer;
use SDM\Altapay\Traits;
use GuzzleHttp\Exception\ClientException as GuzzleHttpClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Sometimes after delivering the goods/services and capturing the funds you want to repay/refund the customer.
 * Either you want to make a full refund or you only want to make a partial refund.
 *
 * Note that not all payments can be refunded, and some can be refunded multiple times,
 * depending on the payment nature (CreditCard, E-Payment, BankPayment and iDEAL) and on the acquirer used.
 *
 * For refunds in regard to Klarna captures, we do not currently support refunds spanning multiple captures.
 * These must be refunded individually.
 */
class RefundCapturedReservation extends AbstractApi
{
    use Traits\TransactionsTrait;
    use Traits\OrderlinesTrait;
    use Traits\AmountTrait;

    /**
     * If you wish to define the reconciliation identifier used in the reconciliation csv files
     *
     * @param string $identifier
     *
     * @return $this
     */
    public function setReconciliationIdentifier($identifier)
    {
        $this->unresolvedOptions['reconciliation_identifier'] = $identifier;

        return $this;
    }

    /**
     * If you wish to decide what the invoice number is on a Arvato invoice, set it here.
     * Note that the invoice number is used as an OCR Number in regard to Klarna captures.
     *
     * @param string $number
     *
     * @return $this
     */
    public function setInvoiceNumber($number)
    {
        $this->unresolvedOptions['invoice_number'] = $number;

        return $this;
    }

    /**
     * If you wish to refund more than the amount which was captured this flag needs to be set to "1".
     * Some acquirers or payments may not allow this in which case you will receive an error response.
     *
     * @param bool $allowOverRefund
     *
     * @return $this
     */
    public function setAllowOverRefund($allowOverRefund)
    {
        $this->unresolvedOptions['allow_over_refund'] = (bool)$allowOverRefund;

        return $this;
    }

    /**
     * Configure options
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('transaction_id');
        $resolver->setDefined([
            'amount',
            'reconciliation_identifier',
            'invoice_number',
            'allow_over_refund',
            'orderLines'
        ]);
        $resolver->addAllowedTypes('reconciliation_identifier', 'string');
        $resolver->addAllowedTypes('invoice_number', ['string', 'int']);
        $resolver->addAllowedTypes('allow_over_refund', 'bool');
    }

    /**
     * Handle response
     *
     * @param Request           $request
     * @param ResponseInterface $response
     *
     * @return \Altapay\Response\AbstractResponse
     *
     * @throws \Exception
     */
    protected function handleResponse(Request $request, ResponseInterface $response)
    {
        $body = (string)$response->getBody();
        $xml  = new \SimpleXMLElement($body);
        if ($xml->Body->Result == 'Error' || $xml->Body->Result == 'Failed') {
            throw new \Exception($xml->Body->MerchantErrorMessage);
        }
        try {
            $data = ResponseSerializer::serialize(RefundResponse::class, $xml->Body, $xml->Header);

            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return array<string, string>
     */
    protected function getBasicHeaders()
    {
        $headers = parent::getBasicHeaders();
        if (mb_strtolower($this->getHttpMethod()) == 'post') {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        return $headers;
    }

    /**
     * Url to api call
     *
     * @param array<string, mixed> $options Resolved options
     *
     * @return string
     */
    protected function getUrl(array $options)
    {
        $url = 'refundCapturedReservation';
        if (mb_strtolower($this->getHttpMethod()) == 'get') {
            $query = $this->buildUrl($options);
            $url   = sprintf('%s/?%s', $url, $query);
        }

        return $url;
    }

    /**
     * @return string
     */
    protected function getHttpMethod()
    {
        return 'POST';
    }

    /**
     * Generate the response
     *
     * @return \Altapay\Response\AbstractResponse
     */
    protected function doResponse()
    {
        $this->doConfigureOptions();
        $headers           = $this->getBasicHeaders();
        $requestParameters = [$this->getHttpMethod(), $this->parseUrl(), $headers];
        if (mb_strtolower($this->getHttpMethod()) == 'post') {
            $requestParameters[] = $this->getPostOptions();
        }

        $request       = new Request(...$requestParameters);
        $this->request = $request;
        try {
            $response       = $this->getClient()->send($request);
            $this->response = $response;
            $output         = $this->handleResponse($request, $response);
            $this->validateResponse($output);

            return $output;
        } catch (GuzzleHttpClientException $e) {
            throw new Exceptions\ClientException($e->getMessage(), $e->getRequest(), $e->getResponse(), $e);
        }
    }

    /**
     * @return string
     */
    protected function getPostOptions()
    {
        $options = $this->options;

        return http_build_query($options, '', '&');
    }
}
