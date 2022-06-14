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

namespace SDM\Altapay\Response\Embeds;

use DateTime;
use SDM\Altapay\Response\AbstractResponse;
use SDM\Altapay\Response\Embeds\PaymentInfo;

class Transaction extends AbstractResponse
{
    /**
     * Childs of the response
     *
     * @var array<string, array<string, mixed>>
     */
    protected $childs = [
        'PaymentNatureService'      => [
            'class' => PaymentNatureService::class,
            'array' => false
        ],
        'PaymentInfos'              => [
            'class' => PaymentInfo::class,
            'array' => 'PaymentInfo',
        ],
        'ChargebackEvents'          => [
            'class' => ChargebackEvent::class,
            'array' => 'ChargebackEvent'
        ],
        'CustomerInfo'              => [
            'class' => CustomerInfo::class,
            'array' => false
        ],
        'ReconciliationIdentifiers' => [
            'class' => ReconciliationIdentifier::class,
            'array' => 'ReconciliationIdentifier'
        ],
        'CreditCardExpiry'          => [
            'class' => CreditCard::class,
            'array' => false
        ],
        'CardInformation'          => [
            'class' => CardInformation::class,
            'array' => false
        ]
    ];

    /**
     * @var string
     */
    public $TransactionId;

    /**
     * @var string
     */
    public $PaymentId;

    /**
     * @var string
     */
    public $CardStatus;

    /**
     * @var string
     */
    public $CreditCardExpiry;

    /**
     * @var string
     */
    public $CreditCardToken;

    /**
     * @var string
     */
    public $CreditCardMaskedPan;

    /**
     * @var string
     */
    public $IsTokenized;

    /**
     * @var string
     */
    public $ThreeDSecureResult;

    /**
     * @var string
     */
    public $LiableForChargeback;

    /**
     * @var string
     */
    public $CVVCheckResult;

    /**
     * @var string
     */
    public $BlacklistToken;

    /**
     * @var string
     */
    public $ShopOrderId;

    /**
     * @var string
     */
    public $Shop;

    /**
     * @var string
     */
    public $Terminal;

    /**
     * @var string
     */
    public $TransactionStatus;

    /**
     * @var string
     */
    public $ReasonCode;

    /**
     * @var string
     */
    public $MerchantCurrency;

    /**
     * @var string
     */
    public $MerchantCurrencyAlpha;

    /**
     * @var string
     */
    public $CardHolderCurrency;

    /**
     * @var string
     */
    public $CardHolderCurrencyAlpha;

    /**
     * @var string
     */
    public $AuthType;

    /**
     * @var float
     */
    public $ReservedAmount;

    /**
     * @var float
     */
    public $CapturedAmount;

    /**
     * @var float
     */
    public $RefundedAmount;

    /**
     * @var float
     */
    public $RecurringDefaultAmount;

    /**
     * @var float
     */
    public $CreditedAmount;

    /**
     * @var float
     */
    public $SurchargeAmount;

    /**
     * @var DateTime
     */
    public $CreatedDate;

    /**
     * @var DateTime
     */
    public $UpdatedDate;

    /**
     * @var string
     */
    public $PaymentNature;

    /**
     * @var string
     */
    public $PaymentSchemeName;

    /**
     * @var string
     */
    public $PaymentSource;

    /**
     * @var PaymentNatureService
     */
    public $PaymentNatureService;

    /**
     * @var CardInformation
     */
    public $CardInformation;

    /**
     * @var float
     */
    public $FraudRiskScore;

    /**
     * @var string
     */
    public $FraudExplanation;

    /**
     * @var string
     */
    public $FraudRecommendation;

    /**
     * @var string
     */
    public $AddressVerification;

    /**
     * @var string
     */
    public $AddressVerificationDescription;

    /**
     * @var string
     */
    public $ChargebackEvents;

    /**
     * @var PaymentInfo[]
     */
    public $PaymentInfos = [];

    /**
     * @var CustomerInfo
     */
    public $CustomerInfo;

    /**
     * @var ReconciliationIdentifier[]
     */
    public $ReconciliationIdentifiers = [];

    /**
     * @var string
     */
    public $InvoiceOrderInfo;

    /**
     * @param string $CreatedDate
     *
     * @return $this
     */
    protected function setCreatedDate($CreatedDate)
    {
        $this->CreatedDate = new \DateTime($CreatedDate);

        return $this;
    }

    /**
     * @param string $UpdatedDate
     *
     * @return $this
     */
    protected function setUpdatedDate($UpdatedDate)
    {
        $this->UpdatedDate = new \DateTime($UpdatedDate);

        return $this;
    }

    /**
     * @param float $ReservedAmount
     *
     * @return $this
     */
    public function setReservedAmount($ReservedAmount)
    {
        $this->ReservedAmount = (float)$ReservedAmount;

        return $this;
    }

    /**
     * @param float $CapturedAmount
     *
     * @return $this
     */
    public function setCapturedAmount($CapturedAmount)
    {
        $this->CapturedAmount = (float)$CapturedAmount;

        return $this;
    }

    /**
     * @param float $CreditedAmount
     *
     * @return $this
     */
    public function setCreditedAmount($CreditedAmount)
    {
        $this->CreditedAmount = (float)$CreditedAmount;

        return $this;
    }

    /**
     * @param float $SurchargeAmount
     *
     * @return $this
     */
    public function setSurchargeAmount($SurchargeAmount)
    {
        $this->SurchargeAmount = (float)$SurchargeAmount;

        return $this;
    }

    /**
     * @param float $RefundedAmount
     *
     * @return $this
     */
    public function setRefundedAmount($RefundedAmount)
    {
        $this->RefundedAmount = (float)$RefundedAmount;

        return $this;
    }

    /**
     * @param float $RecurringDefaultAmount
     *
     * @return $this
     */
    public function setRecurringDefaultAmount($RecurringDefaultAmount)
    {
        $this->RecurringDefaultAmount = (float)$RecurringDefaultAmount;

        return $this;
    }

    /**
     * @param float $FraudRiskScore
     *
     * @return $this
     */
    public function setFraudRiskScore($FraudRiskScore)
    {
        $this->FraudRiskScore = (float)$FraudRiskScore;

        return $this;
    }
}
