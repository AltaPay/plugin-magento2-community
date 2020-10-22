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
use SDM\Altapay\Request\Card;
use SDM\Altapay\Response\ReservationOfFixedAmountResponse;
use SDM\Altapay\Serializer\ResponseSerializer;
use SDM\Altapay\Traits;
use SDM\Altapay\Types;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This will create a MO/TO payment. The payment can be made with a credit card, or a credit card token and the CVV.
 */
class ReservationOfFixedAmount extends AbstractApi
{
    use Traits\AmountTrait;
    use Traits\TerminalTrait;
    use Traits\CurrencyTrait;
    use Traits\ShopOrderIdTrait;
    use Traits\TransactionInfoTrait;
    use Traits\CustomerInfoTrait;

    /**
     * The id of the order in your web shop
     *
     * @param string $shopOrderId
     *
     * @return $this
     */
    public function setShopOrderId($shopOrderId)
    {
        $this->unresolvedOptions['shop_orderid'] = $shopOrderId;

        return $this;
    }

    /**
     * Set the credit card
     *
     * @param Card $card
     *
     * @return $this
     */
    public function setCard(Card $card)
    {
        $this->unresolvedOptions['cardnum'] = $card->getCardNumber();
        $this->unresolvedOptions['emonth']  = $card->getExpiryMonth();
        $this->unresolvedOptions['eyear']   = $card->getExpiryYear();
        $this->unresolvedOptions['cvc']     = $card->getCvc();

        return $this;
    }

    /**
     * A credit card token previously received from an eCommerce payment or an other MO/TO payment.
     *
     * @param string $token A credit card token previously received from an eCommerce payment or an other MO/TO payment.
     * @param string $cvc   The CVC/CVV/CVV2/Security Code
     *
     * @return $this
     */
    public function setCreditCardToken($token, $cvc = null)
    {
        $this->unresolvedOptions['credit_card_token'] = $token;
        if ($cvc) {
            $this->unresolvedOptions['cvc'] = $cvc;
        }

        return $this;
    }

    /**
     * The type of payment
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
     * The source of the payment.
     *
     * @param string $paymentSource
     *
     * @return $this
     */
    public function setPaymentSource($paymentSource)
    {
        $this->unresolvedOptions['payment_source'] = $paymentSource;

        return $this;
    }

    /**
     * If you wish to decide which fraud detection service to use
     *
     * @param string $fraudService
     *
     * @return $this
     */
    public function setFraudService($fraudService)
    {
        $this->unresolvedOptions['fraud_service'] = $fraudService;

        return $this;
    }

    /**
     * The surcharge amount to apply to the payment.
     *
     * @param float $surcharge
     *
     * @return $this
     */
    public function setSurcharge($surcharge)
    {
        $this->unresolvedOptions['surcharge'] = $surcharge;

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
     * Configure options
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'terminal',
            'shop_orderid',
            'amount',
            'currency',
            'type',
            'payment_source',
        ]);
        $resolver->setAllowedValues('type', Types\PaymentTypes::getAllowed());
        $resolver->setDefault('type', 'payment');
        $resolver->setAllowedValues('payment_source', Types\PaymentSources::getAllowed());
        $resolver->setDefault('payment_source', 'moto');

        $resolver->setDefined([
            'cardnum',
            'emonth',
            'eyear',
            'cvc',
            'credit_card_token',
            'transaction_info',
            'fraud_service',
            'surcharge',
            'customer_info',
            'shipping_method',
            'customer_created_date'
        ]);
        $resolver->setAllowedValues('fraud_service', Types\FraudServices::getAllowed());
        $resolver->setAllowedTypes('surcharge', ['int', 'float']);
        $resolver->setAllowedValues('shipping_method', Types\ShippingMethods::getAllowed());

        $resolver->setNormalizer('cardnum', function (Options $options, $value) {
            if (isset($options['credit_card_token'])) {
                throw new \InvalidArgumentException(
                    sprintf('You can not set both a credit card and a credit card token')
                );
            }

            return $value;
        });

        $resolver->setNormalizer('credit_card_token', function (Options $options, $value) {
            $fields = ['cardnum', 'emonth', 'eyear'];
            foreach ($fields as $field) {
                if (isset($options[$field])) {
                    throw new \InvalidArgumentException(
                        sprintf('You can not set both a credit card token and a credit card')
                    );
                }
            }

            return $value;
        });
    }

    /**
     * Handle response
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return ReservationOfFixedAmountResponse
     */
    protected function handleResponse(Request $request, Response $response)
    {
        $body = (string)$response->getBody();
        $xml  = simplexml_load_string($body);

        return ResponseSerializer::serialize(ReservationOfFixedAmountResponse::class, $xml->Body, false, $xml->Header);
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
        $query = $this->buildUrl($options);

        return sprintf('reservationOfFixedAmount/?%s', $query);
    }
}
