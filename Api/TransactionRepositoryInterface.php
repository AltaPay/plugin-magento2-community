<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Api;
use SDM\Altapay\Api\Data\TransactionInterface;

interface TransactionRepositoryInterface
{
    /**
     * It creates the entity and saves the XML request.
     *
     * @param string $orderid
     * @param string $transactionid
     * @param string $paymentid
     * @param string $terminal
     * @param bool   $requireCapture
     * @param string $paymentStatus
     * @param string $paymentNature
     * @param string $result
     * @param bool   $cardHolderMessageMustBeShown
     * @param string $customerErrorMessage
     * @param string $merchantErrorMessage
     * @param string $fraudRiskScore
     * @param string $fraudExplanation
     * @param string $fraudRecommendation
     */
    public function addTransactionData(
        $orderid,
        $transactionid,
        $paymentid,
        $terminal = null,
        $requireCapture = 0,
        $paymentStatus = null,
        $paymentNature = null,
        $result = null,
        $cardHolderMessageMustBeShown = 0,
        $customerErrorMessage = null,
        $merchantErrorMessage = null,
        $fraudRiskScore = null,
        $fraudExplanation = null,
        $fraudRecommendation = null
    );

    /**
     * Get transaction by Order ID
     *
     * @param string $orderId
     */
    public function getTransactionDataByOrderId($orderId);
}
