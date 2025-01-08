<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use SDM\Altapay\Api\Data\TransactionInterface;

class Transaction extends AbstractModel implements TransactionInterface, IdentityInterface
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\SDM\Altapay\Model\ResourceModel\Transaction::class);
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [TransactionInterface::TABLE_NAME . '_' . $this->getId()];
    }

    /**
     * @param string $transactionid
     *
     * @return void
     * @see TransactionInterface
     */
    public function setTransactionid($transactionid)
    {
        $this->setData(TransactionInterface::TRANSACTION_ID, $transactionid);
    }

    /**
     * @return string
     * @see TransactionInterface
     */
    public function getTransactionid()
    {
        return (string)$this->getData(TransactionInterface::TRANSACTION_ID);
    }

    /**
     * @param string $paymentid
     *
     * @see TransactionInterface
     */
    public function setPaymentid($paymentid)
    {
        $this->setData(TransactionInterface::PAYMENT_ID, $paymentid);
    }

    /**
     * @return string
     * @see TransactionInterface
     */
    public function getPaymentid()
    {
        return (string)$this->getData(TransactionInterface::PAYMENT_ID);
    }

    /**
     * @param string $orderid
     *
     * @see TransactionInterface
     */
    public function setOrderid($orderid)
    {
        $this->setData(TransactionInterface::ORDER_ID, $orderid);
    }

    /**
     * @return string
     * @see TransactionInterface
     */
    public function getOrderid()
    {
        return (string)$this->getData(TransactionInterface::ORDER_ID);
    }

    /**
     * @param string|null $terminal
     */
    public function setTerminal($terminal)
    {
        $this->setData(TransactionInterface::TERMINAL, $terminal);
    }

    /**
     * @return string
     */
    public function getTerminal()
    {
        return (string)$this->getData(TransactionInterface::TERMINAL);
    }

    /**
     * @param string|null $requireCapture
     */
    public function setRequireCapture($requireCapture)
    {
        $this->setData(TransactionInterface::REQUIRE_CAPTURE, $requireCapture);
    }

    /**
     * @return string
     */
    public function getRequireCapture()
    {
        return (string)$this->getData(TransactionInterface::REQUIRE_CAPTURE);
    }

    /**
     * @param string|null $paymentStatus
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->setData(TransactionInterface::PAYMENT_STATUS, $paymentStatus);
    }

    /**
     * @return string
     */
    public function getPaymentStatus()
    {
        return (string)$this->getData(TransactionInterface::PAYMENT_STATUS);
    }

    /**
     * @param string|null $paymentNature
     */
    public function setPaymentNature($paymentNature)
    {
        $this->setData(TransactionInterface::PAYMENT_NATURE, $paymentNature);
    }

    /**
     * @return string
     */
    public function getPaymentNature()
    {
        return (string)$this->getData(TransactionInterface::PAYMENT_NATURE);
    }

    /**
     * @param string|null $result
     */
    public function setResult($result)
    {
        $this->setData(TransactionInterface::RESULT, $result);
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return (string)$this->getData(TransactionInterface::RESULT);
    }

    /**
     * @param string|null $cardHolderMessageMustBeShown
     */
    public function setCardHolderMessageMustBeShown($cardHolderMessageMustBeShown)
    {
        $this->setData(TransactionInterface::CARD_HOLDER_MESSAGE_MUST_BE_SHOWN, $cardHolderMessageMustBeShown);
    }

    /**
     * @return string
     */
    public function getCardHolderMessageMustBeShown()
    {
        return (string)$this->getData(TransactionInterface::CARD_HOLDER_MESSAGE_MUST_BE_SHOWN);
    }

    /**
     * @param string|null $customerErrorMessage
     */
    public function setCustomerErrorMessage($customerErrorMessage)
    {
        $this->setData(TransactionInterface::CUSTOMER_ERROR_MESSAGE, $customerErrorMessage);
    }

    /**
     * @return string
     */
    public function getCustomerErrorMessage()
    {
        return (string)$this->getData(TransactionInterface::CUSTOMER_ERROR_MESSAGE);
    }

    /**
     * @param string|null $merchantErrorMessage
     */
    public function setMerchantErrorMessage($merchantErrorMessage)
    {
        $this->setData(TransactionInterface::MERCHANT_ERROR_MESSAGE, $merchantErrorMessage);
    }

    /**
     * @return string
     */
    public function getMerchantErrorMessage()
    {
        return (string)$this->getData(TransactionInterface::MERCHANT_ERROR_MESSAGE);
    }

    /**
     * @param string|null $fraudRiskScore
     */
    public function setFraudRiskScore($fraudRiskScore)
    {
        $this->setData(TransactionInterface::FRAUD_RISK_SCORE, $fraudRiskScore);
    }

    /**
     * @return string
     */
    public function getFraudRiskScore()
    {
        return (string)$this->getData(TransactionInterface::FRAUD_RISK_SCORE);
    }

    /**
     * @param string|null $fraudExplanation
     */
    public function setFraudExplanation($fraudExplanation)
    {
        $this->setData(TransactionInterface::FRAUD_EXPLANATION, $fraudExplanation);
    }

    /**
     * @return string
     */
    public function getFraudExplanation()
    {
        return (string)$this->getData(TransactionInterface::FRAUD_EXPLANATION);
    }

    /**
     * @param string|null $fraudRecommendation
     */
    public function setFraudRecommendation($fraudRecommendation)
    {
        $this->setData(TransactionInterface::FRAUD_RECOMMENDATION, $fraudRecommendation);
    }

    /**
     * @return string
     */
    public function getFraudRecommendation()
    {
        return (string)$this->getData(TransactionInterface::FRAUD_RECOMMENDATION);
    }
}
