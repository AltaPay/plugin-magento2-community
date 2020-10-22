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

use SDM\Altapay\Response\AbstractResponse;

class Transaction extends AbstractResponse
{
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
        ]
    ];

    /**
     * @var TransactionId
     */
    public $TransactionId;

    /**
     * @var PaymentId
     */
    public $PaymentId;

    /**
     * @var CardStatus
     */
    public $CardStatus;

    /**
     * @var CreditCardExpiry
     */
    public $CreditCardExpiry;

    /**
     * @var CreditCardToken
     */
    public $CreditCardToken;

    /**
     * @var CreditCardMaskedPan
     */
    public $CreditCardMaskedPan;

    /**
     * @var IsTokenized
     */
    public $IsTokenized;

    /**
     * @var ThreeDSecureResult
     */
    public $ThreeDSecureResult;

    /**
     * @var LiableForChargeback
     */
    public $LiableForChargeback;

    /**
     * @var CVVCheckResult
     */
    public $CVVCheckResult;

    /**
     * @var BlacklistToken
     */
    public $BlacklistToken;

    /**
     * @var ShopOrderId
     */
    public $ShopOrderId;

    /**
     * @var Shop
     */
    public $Shop;

    /**
     * @var Terminal
     */
    public $Terminal;

    /**
     * @var TransactionStatus
     */
    public $TransactionStatus;

    /**
     * @var ReasonCode
     */
    public $ReasonCode;

    /**
     * @var MerchantCurrency
     */
    public $MerchantCurrency;

    /**
     * @var MerchantCurrencyAlpha
     */
    public $MerchantCurrencyAlpha;

    /**
     * @var CardHolderCurrency
     */
    public $CardHolderCurrency;

    /**
     * @var CardHolderCurrencyAlpha
     */
    public $CardHolderCurrencyAlpha;

    /**
     * @var AuthType
     */
    public $AuthType;

    /**
     * @var ReservedAmount
     */
    public $ReservedAmount;

    /**
     * @var CapturedAmount
     */
    public $CapturedAmount;

    /**
     * @var RefundedAmount
     */
    public $RefundedAmount;

    /**
     * @var RecurringDefaultAmount
     */
    public $RecurringDefaultAmount;

    /**
     * @var CreditedAmount
     */
    public $CreditedAmount;

    /**
     * @var SurchargeAmount
     */
    public $SurchargeAmount;

    /**
     * @var CreatedDate
     */
    public $CreatedDate;

    /**
     * @var UpdatedDate
     */
    public $UpdatedDate;

    /**
     * @var PaymentNature
     */
    public $PaymentNature;

    /**
     * @var PaymentSchemeName
     */
    public $PaymentSchemeName;

    /**
     * @var PaymentSource
     */
    public $PaymentSource;

    /**
     * @var PaymentNatureService
     */
    public $PaymentNatureService;

    /**
     * @var FraudRiskScore
     */
    public $FraudRiskScore;

    /**
     * @var FraudExplanation
     */
    public $FraudExplanation;

    /**
     * @var FraudRecommendation
     */
    public $FraudRecommendation;

    /**
     * @var AddressVerification
     */
    public $AddressVerification;

    /**
     * @var AddressVerificationDescription
     */
    public $AddressVerificationDescription;

    /**
     * @var ChargebackEvents
     */
    public $ChargebackEvents;

    /**
     * @var PaymentInfos
     */
    public $PaymentInfos;

    /**
     * @var CustomerInfo
     */
    public $CustomerInfo;

    /**
     * @var ReconciliationIdentifiers
     */
    public $ReconciliationIdentifiers;

    /**
     * @var InvoiceOrderInfo
     */
    public $InvoiceOrderInfo;

    /**
     * @param $CreatedDate
     *
     * @return $this
     */
    protected function setCreatedDate($CreatedDate)
    {
        $this->CreatedDate = new \DateTime($CreatedDate);

        return $this;
    }

    /**
     * @param $UpdatedDate
     *
     * @return $this
     */
    protected function setUpdatedDate($UpdatedDate)
    {
        $this->UpdatedDate = new \DateTime($UpdatedDate);

        return $this;
    }

    /**
     * @param $ReservedAmount
     *
     * @return $this
     */
    public function setReservedAmount($ReservedAmount)
    {
        $this->ReservedAmount = (float)$ReservedAmount;

        return $this;
    }

    /**
     * @param $CapturedAmount
     *
     * @return $this
     */
    public function setCapturedAmount($CapturedAmount)
    {
        $this->CapturedAmount = (float)$CapturedAmount;

        return $this;
    }

    /**
     * @param $CreditedAmount
     *
     * @return $this
     */
    public function setCreditedAmount($CreditedAmount)
    {
        $this->CreditedAmount = (float)$CreditedAmount;

        return $this;
    }

    /**
     * @param $SurchargeAmount
     *
     * @return $this
     */
    public function setSurchargeAmount($SurchargeAmount)
    {
        $this->SurchargeAmount = (float)$SurchargeAmount;

        return $this;
    }

    /**
     * @param $RefundedAmount
     *
     * @return $this
     */
    public function setRefundedAmount($RefundedAmount)
    {
        $this->RefundedAmount = (float)$RefundedAmount;

        return $this;
    }

    /**
     * @param $RecurringDefaultAmount
     *
     * @return $this
     */
    public function setRecurringDefaultAmount($RecurringDefaultAmount)
    {
        $this->RecurringDefaultAmount = (float)$RecurringDefaultAmount;

        return $this;
    }

    /**
     * @param $FraudRiskScore
     *
     * @return $this
     */
    public function setFraudRiskScore($FraudRiskScore)
    {
        $this->FraudRiskScore = (float)$FraudRiskScore;

        return $this;
    }
}
