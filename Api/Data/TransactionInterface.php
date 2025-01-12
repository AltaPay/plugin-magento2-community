<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Api\Data;

interface TransactionInterface
{
    /**
     * Constants defined for keys of the data array.
     */
    const TABLE_NAME           = 'sdm_altapay';
    const ENTITY_ID            = 'id';
    const PAYMENT_ID           = 'paymentid';
    const TRANSACTION_ID       = 'transactionid';
    const ORDER_ID             = 'orderid';
    const CREATED_AT           = 'created_at';
    const TERMINAL                         = 'terminal';
    const REQUIRE_CAPTURE                  = 'require_capture';
    const PAYMENT_STATUS                   = 'payment_status';
    const PAYMENT_NATURE                   = 'payment_nature';
    const RESULT                           = 'result';
    const CARD_HOLDER_MESSAGE_MUST_BE_SHOWN = 'card_holder_message_must_be_shown';
    const CUSTOMER_ERROR_MESSAGE           = 'customer_error_message';
    const MERCHANT_ERROR_MESSAGE           = 'merchant_error_message';
    const FRAUD_RISK_SCORE                 = 'fraud_risk_score';
    const FRAUD_EXPLANATION                = 'fraud_explanation';
    const FRAUD_RECOMMENDATION             = 'fraud_recommendation';

    /**
     * @param string $paymentid
     */
    public function setPaymentid($paymentid);

    /**
     * @return string
     */
    public function getPaymentid();

    /**
     * @param string $transactionid
     */
    public function setTransactionid($transactionid);

    /**
     * @return string
     */
    public function getTransactionid();

    /**
     * @param string $orderid
     */
    public function setOrderid($orderid);

    /**
     * @return string
     */
    public function getOrderid();

    /**
     * @param string|null $terminal
     * @return $this
     */
    public function setTerminal($terminal);

    /**
     * @return string|null
     */
    public function getTerminal();

    /**
     * @param int|null $requireCapture
     * @return $this
     */
    public function setRequireCapture($requireCapture);

    /**
     * @return int|null
     */
    public function getRequireCapture();

    /**
     * @param string|null $paymentStatus
     * @return $this
     */
    public function setPaymentStatus($paymentStatus);

    /**
     * @return string|null
     */
    public function getPaymentStatus();

    /**
     * @param string|null $paymentNature
     * @return $this
     */
    public function setPaymentNature($paymentNature);

    /**
     * @return string|null
     */
    public function getPaymentNature();

    /**
     * @param string|null $result
     * @return $this
     */
    public function setResult($result);

    /**
     * @return string|null
     */
    public function getResult();

    /**
     * @param bool $cardHolderMessageMustBeShown
     * @return $this
     */
    public function setCardHolderMessageMustBeShown($cardHolderMessageMustBeShown);

    /**
     * @return bool|null
     */
    public function getCardHolderMessageMustBeShown();

    /**
     * @param string|null $customerErrorMessage
     * @return $this
     */
    public function setCustomerErrorMessage($customerErrorMessage);

    /**
     * @return string|null
     */
    public function getCustomerErrorMessage();

    /**
     * @param string|null $merchantErrorMessage
     * @return $this
     */
    public function setMerchantErrorMessage($merchantErrorMessage);

    /**
     * @return string|null
     */
    public function getMerchantErrorMessage();

    /**
     * @param string|null $fraudRiskScore
     * @return $this
     */
    public function setFraudRiskScore($fraudRiskScore);

    /**
     * @return string|null
     */
    public function getFraudRiskScore();

    /**
     * @param string|null $fraudExplanation
     * @return $this
     */
    public function setFraudExplanation($fraudExplanation);

    /**
     * @return string|null
     */
    public function getFraudExplanation();

    /**
     * @param string|null $fraudRecommendation
     * @return $this
     */
    public function setFraudRecommendation($fraudRecommendation);

    /**
     * @return string|null
     */
    public function getFraudRecommendation();
}
