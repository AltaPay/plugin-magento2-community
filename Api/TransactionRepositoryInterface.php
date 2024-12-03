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
     * @param string $transactiondata
     * @param string $parametersdata
     */
    public function addTransactionData($orderid, $transactionid, $paymentid, $transactiondata, $parametersdata);

    /**
     * Get transaction by Order ID
     *
     * @param string $orderId
     */
    public function getTransactionDataByOrderId($orderId);
}
