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

namespace SDM\Valitor\Response\Embeds;

use SDM\Valitor\Response\AbstractResponse;

class Transaction extends AbstractResponse
{
    protected $childs = [
        'PaymentNatureService' => [
            'class' => PaymentNatureService::class,
            'array' => false
        ],
        'PaymentInfos' => [
            'class' => PaymentInfo::class,
            'array' => 'PaymentInfo',
        ],
        'ChargebackEvents' => [
            'class' => ChargebackEvent::class,
            'array' => 'ChargebackEvent'
        ],
        'CustomerInfo' => [
            'class' => CustomerInfo::class,
            'array' => false
        ],
        'ReconciliationIdentifiers' => [
            'class' => ReconciliationIdentifier::class,
            'array' => 'ReconciliationIdentifier'
        ],
        'CreditCardExpiry' => [
            'class' => CreditCard::class,
            'array' => null
        ]
    ];

    public $TransactionId;
    public $PaymentId;
    public $CardStatus;

    /**
     * @var CreditCard
     */
    public $CreditCardExpiry;

    public $CreditCardToken;
    public $CreditCardMaskedPan;
    /**
     * @var boolean
     */
    public $IsTokenized;
    public $ThreeDSecureResult;
    public $LiableForChargeback;
    public $CVVCheckResult;
    public $BlacklistToken;
    public $ShopOrderId;
    public $Shop;
    public $Terminal;
    public $TransactionStatus;
    public $ReasonCode;
    public $MerchantCurrency;
    public $MerchantCurrencyAlpha;
    public $CardHolderCurrency;
    public $CardHolderCurrencyAlpha;
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
     * @var \DateTime
     */
    public $CreatedDate;

    /**
     * @var \DateTime
     */
    public $UpdatedDate;
    public $PaymentNature;
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
     * @var float
     */
    public $FraudRiskScore;
    public $FraudExplanation;
    public $FraudRecommendation;
    
    public $AddressVerification;
    public $AddressVerificationDescription;

    /**
     * @var ChargebackEvent[]
     */
    public $ChargebackEvents;

    /**
     * @var PaymentInfo[]
     */
    public $PaymentInfos;

    /**
     * @var CustomerInfo
     */
    public $CustomerInfo;

    /**
     * @var ReconciliationIdentifier[]
     */
    public $ReconciliationIdentifiers;

    /**
     * @param string $CreatedDate
     * @return Transaction
     */
    protected function setCreatedDate($CreatedDate)
    {
        $this->CreatedDate = new \DateTime($CreatedDate);
        return $this;
    }

    /**
     * @param string $UpdatedDate
     * @return Transaction
     */
    protected function setUpdatedDate($UpdatedDate)
    {
        $this->UpdatedDate = new \DateTime($UpdatedDate);
        return $this;
    }

    /**
     * @param float $ReservedAmount
     * @return Transaction
     */
    public function setReservedAmount($ReservedAmount)
    {
        $this->ReservedAmount = (float) $ReservedAmount;
        return $this;
    }

    /**
     * @param float $CapturedAmount
     * @return Transaction
     */
    public function setCapturedAmount($CapturedAmount)
    {
        $this->CapturedAmount = (float) $CapturedAmount;
        return $this;
    }

    /**
     * @param float $CreditedAmount
     * @return Transaction
     */
    public function setCreditedAmount($CreditedAmount)
    {
        $this->CreditedAmount = (float) $CreditedAmount;
        return $this;
    }

    /**
     * @param float $SurchargeAmount
     * @return Transaction
     */
    public function setSurchargeAmount($SurchargeAmount)
    {
        $this->SurchargeAmount = (float) $SurchargeAmount;
        return $this;
    }

    /**
     * @param float $RefundedAmount
     * @return Transaction
     */
    public function setRefundedAmount($RefundedAmount)
    {
        $this->RefundedAmount = (float) $RefundedAmount;
        return $this;
    }

    /**
     * @param float $RecurringDefaultAmount
     * @return Transaction
     */
    public function setRecurringDefaultAmount($RecurringDefaultAmount)
    {
        $this->RecurringDefaultAmount = (float) $RecurringDefaultAmount;
        return $this;
    }

    /**
     * @param float $FraudRiskScore
     * @return Transaction
     */
    public function setFraudRiskScore($FraudRiskScore)
    {
        $this->FraudRiskScore = (float) $FraudRiskScore;
        return $this;
    }
}
