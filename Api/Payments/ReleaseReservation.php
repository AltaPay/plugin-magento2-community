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
use SDM\Altapay\Response\ReleaseReservationResponse;
use SDM\Altapay\Serializer\ResponseSerializer;
use SDM\Altapay\Traits\TransactionsTrait;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Every now and then you for some reason do not want to capture a payment.
 * In these cases you must cancel it to release the reservation of the funds.
 *
 * Calling releaseReservation on a payment created with the auth type 'payment',
 * but completed with the auth type 'paymentAndCapture' (due to missing acquirer support for 'payment),
 * will result in that payment being refunded.
 */
class ReleaseReservation extends AbstractApi
{
    use TransactionsTrait;

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
    }

    /**
     * Handle response
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return ReleaseReservationResponse
     */
    protected function handleResponse(Request $request, Response $response)
    {
        $body = (string)$response->getBody();
        $xml  = simplexml_load_string($body);

        return ResponseSerializer::serialize(ReleaseReservationResponse::class, $xml->Body, false, $xml->Header);
    }

    /**
     * @return array
     */
    protected function getBasicHeaders()
    {
        $headers = parent::getBasicHeaders();
        if (strtolower($this->getHttpMethod()) == 'post') {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        return $headers;
    }

    /**
     * Url to api call
     *
     * @param array $options Resolved options
     *
     * @return string
     */
    protected function getUrl(array $options)
    {
        $url = 'releaseReservation';
        if (strtolower($this->getHttpMethod()) == 'get') {
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
     * @return \Altapay\Response\AbstractResponse|PaymentRequestResponse|bool|void
     * @throws \Altapay\Exceptions\ResponseHeaderException
     * @throws \Altapay\Exceptions\ResponseMessageException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function doResponse()
    {
        $this->doConfigureOptions();
        $headers           = $this->getBasicHeaders();
        $requestParameters = [$this->getHttpMethod(), $this->parseUrl(), $headers];
        if (strtolower($this->getHttpMethod()) == 'post') {
            $requestParameters[] = $this->getPostOptions();
        }
        $request       = new Request(...$requestParameters);
        $this->request = $request;
        try {
            $response       = $this->getClient()->send($request);
            $this->response = $response;

            $output = $this->handleResponse($request, $response);
            $this->validateResponse($output);

            return $output;
        } catch (GuzzleHttpClientException $e) {
            throw new Exceptions\ClientException($e->getMessage(), $e->getRequest(), $e->getResponse());
        }
    }

    /**
     * @return string
     */
    protected function getPostOptions()
    {
        $options = $this->options;

        return http_build_query($options, null, '&');
    }
}
