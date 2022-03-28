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

namespace SDM\Altapay;

use SDM\Altapay\Exceptions;
use SDM\Altapay\Response\AbstractResponse;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException as GuzzleHttpClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractApi
 */
abstract class AbstractApi
{

    /**
     * Test gateway url
     */
    const TESTBASEURL = 'https://testgateway.altapaysecure.com';

    /**
     * Api version
     */
    const VERSION = 'API';

    /**
     * Event dispatcher
     *
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * Not resolved options
     *
     * @var array<string, mixed>
     */
    public $unresolvedOptions = [];

    /**
     * Resolved options
     *
     * @var array<string, mixed>
     */
    protected $options;

    /**
     * Request of the call
     *
     * @var Request
     */
    protected $request;

    /**
     * Response of the call
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Base url
     *
     * @var string|null
     */
    protected $baseUrl;

    /**
     * Authentication
     *
     * @var Authentication
     */
    protected $authentication;

    /**
     * HTTP client to use
     *
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * Configure options
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    abstract protected function configureOptions(OptionsResolver $resolver);

    /**
     * Handle response
     *
     * @param Request           $request
     * @param ResponseInterface $response
     *
     * @return AbstractResponse|string|array<Transaction>
     */
    abstract protected function handleResponse(Request $request, ResponseInterface $response);

    /**
     * Url to api call
     *
     * @param array<string, mixed> $options Resolved options
     *
     * @return string
     */
    abstract protected function getUrl(array $options);

    /**
     * AbstractApi constructor.
     *
     * @param Authentication $authentication
     */
    public function __construct(Authentication $authentication)
    {
        $this->dispatcher     = new EventDispatcher();
        $this->httpClient     = new Client();
        $this->authentication = $authentication;
        $this->baseUrl        = $authentication->getBaseurl();
    }

    /**
     * Generate the response
     *
     * @return AbstractResponse|string|array<Transaction>
     */
    public function call()
    {
        return $this->doResponse();
    }

    /**
     * Set HTTP client
     *
     * @param ClientInterface $client
     *
     * @return $this
     */
    public function setClient(ClientInterface $client)
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * Get the raw request
     * It is made after call() method has been called
     *
     * @return Request
     */
    public function getRawRequest()
    {
        return $this->request;
    }

    /**
     * Get the raw response
     * It is made after call() method has been called
     *
     * @return ResponseInterface
     */
    public function getRawResponse()
    {
        return $this->response;
    }

    /**
     * HTTP method in use
     *
     * @return string
     */
    protected function getHttpMethod()
    {
        return 'GET';
    }

    /**
     * Resolve options
     *
     * @return void
     */
    protected function doConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->setTransactionResolver($resolver);
        $this->setOrderLinesResolver($resolver);
        $this->setAmountResolver($resolver);
        $this->setTerminalResolver($resolver);
        $this->setValidationUrlResolver($resolver);
        $this->setAppleDomainResolver($resolver);
        $this->setCurrencyResolver($resolver);
        $this->setShopOrderIdResolver($resolver);
        $this->setTransactionInfoResolver($resolver);
        $this->setCustomerInfoResolver($resolver);
        $this->options = $resolver->resolve($this->unresolvedOptions);
    }

    /**
     * Validate response
     *
     * @param AbstractResponse $response
     *
     * @return void
     *
     * @throws Exceptions\ResponseHeaderException
     * @throws Exceptions\ResponseMessageException
     */
    protected function validateResponse($response)
    {
        if ($response->Header->ErrorCode != 0) {
            throw new Exceptions\ResponseHeaderException($response->Header);
        }

        if (property_exists($response, 'MerchantErrorMessage')) {
            if ($response->MerchantErrorMessage) {
                throw new Exceptions\ResponseMessageException($response->MerchantErrorMessage);
            }
        }

        if (property_exists($response, 'CardHolderErrorMessage') && property_exists($response, 'CardHolderMessageMustBeShown')) {
            if ($response->CardHolderMessageMustBeShown) {
                throw new Exceptions\ResponseMessageException($response->CardHolderErrorMessage);
            }
        }
    }

    /**
     * Generate the response
     *
     * @return AbstractResponse|string|array<Transaction>
     */
    protected function doResponse()
    {
        $this->doConfigureOptions();
        $headers = $this->getBasicHeaders();
        $request = new Request(
            $this->getHttpMethod(),
            $this->parseUrl(),
            $headers
        );

        $this->request = $request;

        try {
            $response       = $this->getClient()->send($request);
            $this->response = $response;
            $output         = $this->handleResponse($request, $response);
            if ($output instanceof AbstractResponse) {
                $this->validateResponse($output);
            }

            return $output;
        } catch (GuzzleHttpClientException $e) {
            throw new Exceptions\ClientException($e->getMessage(), $e->getRequest(), $e->getResponse(), $e);
        }
    }

    /**
     * Parse the URL
     *
     * @return string
     */
    protected function parseUrl()
    {
        return sprintf(
            '%s/merchant/%s/%s',
            rtrim($this->baseUrl ?: self::TESTBASEURL, '/'),
            self::VERSION,
            $this->getUrl($this->options)
        );
    }

    /**
     * Get User Agent details
     *
     * @return string
     */
    protected function getUserAgent()
    {
        static $userAgent = '';

        if (!$userAgent) {
            $userAgent = 'api-php/3.1.1';
            if (extension_loaded('curl') && function_exists('curl_version')) {
                $curlInfo = \curl_version();
                if (is_array($curlInfo) && array_key_exists("version", $curlInfo)) {
                    $userAgent .= ' curl/' . $curlInfo["version"];
                }
            }
            $userAgent .= ' PHP/' . PHP_VERSION;
        }

        return $userAgent;
    }

    /**
     * Build url
     *
     * @param array<string, string> $options
     *
     * @return bool|string
     */
    protected function buildUrl(array $options)
    {
        if (!$options) {
            return false;
        }

        return http_build_query($options);
    }

    /**
     * Is authentication required for this
     *
     * @return bool
     */
    protected function authRequired()
    {
        return true;
    }

    /**
     * Set the headers to the API call
     *
     * @return array<string, string>
     */
    protected function getBasicHeaders()
    {
        $headers = [];

        if ($this->authRequired()) {
            $headers['Authorization'] = sprintf(
                'Basic %s',
                base64_encode($this->authentication->getUsername() . ':' . $this->authentication->getPassword())
            );
        }

        $headers['User-Agent'] = $this->getUserAgent();

        return $headers;
    }

    /**
     * Get the HTTP client
     *
     * @return ClientInterface
     */
    protected function getClient()
    {
        return $this->httpClient;
    }

    /**
     * Resolve transaction
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setTransactionResolver(OptionsResolver $resolver)
    {
    }

    /**
     * Resolve orderlines
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setOrderLinesResolver(OptionsResolver $resolver)
    {
    }

    /**
     * Resolve amount option
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setAmountResolver(OptionsResolver $resolver)
    {
    }

    /**
     * Resolve terminal option
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setTerminalResolver(OptionsResolver $resolver)
    {
    }
    
    /**
     * Resolve validationUrl option
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setValidationUrlResolver(OptionsResolver $resolver)
    {
    }
    /**
     * Resolve applepaydomain option
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setAppleDomainResolver(OptionsResolver $resolver)
    {
    }
    /**
     * Resolve currency option
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setCurrencyResolver(OptionsResolver $resolver)
    {
    }

    /**
     * Resolve shop order id
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setShopOrderIdResolver(OptionsResolver $resolver)
    {
    }

    /**
     * Resolve transaction info option
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setTransactionInfoResolver(OptionsResolver $resolver)
    {
    }

    /**
     * Resolve amount option
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setCustomerInfoResolver(OptionsResolver $resolver)
    {
    }
}
