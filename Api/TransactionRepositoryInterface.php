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
     * @param string      $orderid
     * @param string      $transactionid
     * @param string      $paymentid
     * @param string|null $terminal
     * @param string|null $requireCapture
     * @param string|null $paymentStatus
     * @param string|null $paymentNature
     * @param string|null $result
     * @param string|null $cardHolderMessageMustBeShown
     * @param string|null $customerErrorMessage
     * @param string|null $merchantErrorMessage
     * @param string|null $fraudRiskScore
     * @param string|null $fraudExplanation
     * @param string|null $fraudRecommendation
     */
    public function addTransactionData(
        $orderid,
        $transactionid,
        $paymentid,
        $terminal = null,
        $requireCapture = null,
        $paymentStatus = null,
        $paymentNature = null,
        $result = null,
        $cardHolderMessageMustBeShown = null,
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
