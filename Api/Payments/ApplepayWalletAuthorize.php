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
use SDM\Altapay\Traits\AmountTrait;
use SDM\Altapay\Traits\TerminalTrait;
use SDM\Altapay\Traits\ShopOrderIdTrait;
use SDM\Altapay\Request\Config;
use SDM\Altapay\Traits;
use SDM\Altapay\Types;
use SDM\Altapay\Response\PaymentRequestResponse;
use SDM\Altapay\Traits\CurrencyTrait;
use GuzzleHttp\Exception\ClientException as GuzzleHttpClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplePayWalletAuthorize extends AbstractApi
{
    use Traits\TerminalTrait;
    use Traits\AmountTrait;
    use Traits\CurrencyTrait;
    use Traits\ShopOrderIdTrait;
    use Traits\TransactionInfoTrait;
    use Traits\CustomerInfoTrait;
    use Traits\OrderlinesTrait;

    /**
     * The language of the payment form
     *
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->unresolvedOptions['language'] = $language;

        return $this;
    }

    /**
     * The type of the authorization
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->unresolvedOptions['type'] = $type;

        return $this;
    }

    /**
     * Use the credit_card_token from a previous payment to allow your customer to buy with the same credit card again
     *
     * @param string $token
     *
     * @return $this
     */
    public function setCcToken($token)
    {
        $this->unresolvedOptions['ccToken'] = $token;

        return $this;
    }

    /**
     * If you wish to define the reconciliation identifier used in the reconciliation csv files
     *
     * @param string $identifier
     *
     * @return $this
     */
    public function setSaleReconciliationIdentifier($identifier)
    {
        $this->unresolvedOptions['sale_reconciliation_identifier'] = $identifier;

        return $this;
    }

    /**
     * This sets the invoice number to be used on capture
     *
     * @param string $number
     *
     * @return $this
     */
    public function setSaleInvoiceNumber($number)
    {
        $this->unresolvedOptions['sale_invoice_number'] = $number;

        return $this;
    }

    /**
     * This sets the sales tax amount that will be used on capture
     *
     * @param float $tax
     *
     * @return $this
     */
    public function setSalesTax($tax)
    {
        $this->unresolvedOptions['sales_tax'] = $tax;

        return $this;
    }

    /**
     * The cookie to be sent to your callback urls
     *
     * @param string $cookie
     *
     * @return $this
     */
    public function setCookie($cookie)
    {
        $this->unresolvedOptions['cookie'] = $cookie;

        return $this;
    }

    /**
     * The source of the payment
     *
     * @param string $source
     *
     * @return $this
     */
    public function setPaymentSource($source)
    {
        $this->unresolvedOptions['payment_source'] = $source;

        return $this;
    }

    /**
     * Set config
     *
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->unresolvedOptions['config'] = $config;

        return $this;
    }

    /**
     * If you wish to decide pr. Payment wich fraud detection service to use
     *
     * @param string $service
     *
     * @return $this
     */
    public function setFraudService($service)
    {
        $this->unresolvedOptions['fraud_service'] = $service;

        return $this;
    }

    /**
     * Fraud detection services can use this parameter in the fraud detection calculations
     *
     * @param string $shippingMethod
     *
     * @return $this
     */
    public function setShippingMethod($shippingMethod)
    {
        $this->unresolvedOptions['shipping_method'] = $shippingMethod;

        return $this;
    }

    /**
     * If the this is given the organisation number field in the invoice payment form is prepopulated
     *
     * @param string $number
     *
     * @return $this
     */
    public function setOrganisationNumber($number)
    {
        $this->unresolvedOptions['organisation_number'] = $number;

        return $this;
    }

    /**
     * To require having account enabled for an invoice payment for this specific customer, set this to true
     * To disable account for this specific customer, set to false
     *
     * @param bool $offer
     *
     * @return $this
     */
    public function setAccountOffer($offer)
    {
        $this->unresolvedOptions['account_offer'] = $offer;

        return $this;
    }

    /**
     * This is the date when the customer account was first created in system.
     *
     * @param string $customerCreatedDate
     *
     * @return $this
     */
    public function setCustomerCreatedDate($customerCreatedDate)
    {
        $this->unresolvedOptions['customer_created_date'] = $customerCreatedDate;

        return $this;
    }

    /**
     * @param string $identifier
     *
     * @return $this
     */
    public function setProviderData($identifier)
    {
        $this->unresolvedOptions['provider_data'] = $identifier;

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
        $resolver->setRequired(['provider_data', 'terminal', 'shop_orderid', 'amount', 'currency']);
        $resolver->setDefined([
            'customer_info',
            'config',
            'transaction_info',
            'sales_tax',
            'cookie',
            'ccToken',
            'fraud_service',
            'language',
            'type',
            'orderLines',
            'sale_reconciliation_identifier',
            'sale_invoice_number',
            'payment_source',
            'customer_created_date',
            'shipping_method',
            'organisation_number',
            'account_offer'
        ]);
        $resolver->addAllowedTypes('provider_data', 'string');
        $resolver->setAllowedValues('language', Types\LanguageTypes::getAllowed());
        $resolver->setDefault('type', 'payment');
        $resolver->setAllowedValues('type', Types\PaymentTypes::getAllowed());
        $resolver->setAllowedValues('sale_reconciliation_identifier', function ($value) {
            return mb_strlen($value) <= 100;
        });
        $resolver->setAllowedValues('sale_invoice_number', function ($value) {
            return mb_strlen($value) <= 100;
        });
        $resolver->setAllowedTypes('sales_tax', ['int', 'float']);
        $resolver->setDefault('payment_source', 'eCommerce');
        $resolver->setAllowedValues('payment_source', Types\PaymentSources::getAllowed());
        $resolver->setAllowedTypes('config', Config::class);
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer('config', function (Options $options, Config $value) {
            return $value->serialize();
        });
        $resolver->setAllowedValues('organisation_number', function ($value) {
            return mb_strlen($value) <= 20;
        });
        $resolver->setAllowedTypes('account_offer', 'bool');
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer('account_offer', function (Options $options, $value) {
            return $value ? 'required' : 'disabled';
        });
    }

    /**
     * Handle response
     *
     * @param Request           $request
     * @param ResponseInterface $response
     *
     * @return PaymentRequestResponse
     */
    protected function handleResponse(Request $request, ResponseInterface $response)
    {
        $body = (string)$response->getBody();
        $xml  = new \SimpleXMLElement($body);

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
        $url = 'cardWallet/authorize';
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
