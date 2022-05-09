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
use SDM\Altapay\Serializer\ResponseSerializer;
use SDM\Altapay\Traits\TerminalTrait;
use SDM\Altapay\Response\PaymentRequestResponse;
use GuzzleHttp\Exception\ClientException as GuzzleHttpClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardWalletSession extends AbstractApi
{
    use TerminalTrait;

    /**
     * @param string $identifier
     *
     * @return $this
     */
    public function setValidationUrl($identifier)
    {
        $this->unresolvedOptions['validationUrl'] = $identifier;

        return $this;
    }

    /**
     *
     * @param string $identifier
     *
     * @return $this
     */
    public function setDomain($identifier)
    {
        $this->unresolvedOptions['domain'] = $identifier;

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
        $resolver->setRequired(['terminal', 'validationUrl', 'domain']);
        $resolver->addAllowedTypes('validationUrl', 'string');
        $resolver->addAllowedTypes('domain', 'string');
    }

    /**
     * Handle response
     *
     * @param Request $request
     * @param ResponseInterface $response
     *
     * @return PaymentRequestResponse
     */
    protected function handleResponse(Request $request, ResponseInterface $response)
    {
        $body = (string)$response->getBody();
        $xml = new \SimpleXMLElement($body);

        return ResponseSerializer::serialize(PaymentRequestResponse::class, $xml->Body, $xml->Header);
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
        $url = 'cardWallet/session';
        if (mb_strtolower($this->getHttpMethod()) == 'get') {
            $query = $this->buildUrl($options);
            $url = sprintf('%s/?%s', $url, $query);
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
     */
    protected function doResponse()
    {
        $this->doConfigureOptions();
        $headers = $this->getBasicHeaders();
        $requestParameters = [$this->getHttpMethod(), $this->parseUrl(), $headers];
        if (mb_strtolower($this->getHttpMethod()) == 'post') {
            $requestParameters[] = $this->getPostOptions();
        }

        $request = new Request(...$requestParameters);
        $this->request = $request;
        try {
            $response = $this->getClient()->send($request);
            $this->response = $response;
            $output = $this->handleResponse($request, $response);
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
