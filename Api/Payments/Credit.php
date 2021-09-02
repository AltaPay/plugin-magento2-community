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
use SDM\Altapay\Exceptions\CreditCardTokenAndCardUsedException;
use SDM\Altapay\Response\CreditResponse;
use SDM\Altapay\Serializer\ResponseSerializer;
use SDM\Altapay\Traits;
use SDM\Altapay\Types\PaymentSources;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This will create a Credit payment. The payment can be made with a credit card, or a credit card token and the CVV.
 */
class Credit extends AbstractApi
{
    use Traits\AmountTrait;
    use Traits\TerminalTrait;
    use Traits\CurrencyTrait;
    use Traits\ShopOrderIdTrait;
    use Traits\TransactionInfoTrait;

    /**
     * The source of the payment. Default is "moto"
     *
     * @param string $paymentsource
     *
     * @return $this
     */
    public function setPaymentSource($paymentsource)
    {
        $this->unresolvedOptions['payment_source'] = $paymentsource;
        return $this;
    }

    /**
     * Set the card used
     *
     * @param Card $card
     *
     * @return $this
     */
    public function setCard(Card $card)
    {
        $this->unresolvedOptions['cardnum'] = $card->getCardNumber();
        $this->unresolvedOptions['emonth'] = $card->getExpiryMonth();
        $this->unresolvedOptions['eyear'] = $card->getExpiryYear();
        $this->unresolvedOptions['cvc'] = $card->getCvc();
        return $this;
    }

    /**
     * A credit card token previously received from an eCommerce payment or an other MO/TO payment.
     *
     * @param string $token
     *
     * @return $this
     */
    public function setCreditCardToken($token)
    {
        $this->unresolvedOptions['credit_card_token'] = $token;
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
        $resolver->setRequired(['terminal', 'shop_orderid', 'amount', 'currency']);

        $resolver->setDefined([
            'transaction_info', 'payment_source', 'credit_card_token',
            'cardnum', 'emonth', 'eyear', 'cvc'
        ]);

        $resolver->setAllowedValues('payment_source', PaymentSources::getAllowed());
        $resolver->setDefault('payment_source', 'moto');

        $resolver->setNormalizer('credit_card_token', function (Options $options, $value) {
            if ($value && isset($options['cardnum'])) {
                throw new CreditCardTokenAndCardUsedException(
                    'Both "credit_card_token" and "card" can not be set at the same time, please use only one of them'
                );
            }

            return $value;
        });
    }

    /**
     * Handle response
     *
     * @param Request           $request
     * @param ResponseInterface $response
     *
     * @return CreditResponse
     */
    protected function handleResponse(Request $request, ResponseInterface $response)
    {
        $body = (string) $response->getBody();
        $xml = new \SimpleXMLElement($body);

        return ResponseSerializer::serialize(CreditResponse::class, $xml->Body, $xml->Header);
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
        $query = $this->buildUrl($options);
        return sprintf('credit?%s', $query);
    }
}
