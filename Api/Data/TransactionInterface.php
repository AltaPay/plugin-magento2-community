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
    const XML_FLAG             = 'has_xml_flag';
    const TRANSACTION_DATA     = 'transactiondata';
    const CREATED_AT           = 'created_at';

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
     * @param string $transactiondata
     */
    public function setTransactiondata($transactiondata);

    /**
     * @return string
     */
    public function getTransactiondata();

    /**
     * @param string $transactiondata
     */
    public function setXmlFlag($transactiondata);

    /**
     * @return string
     */
    public function getXmlFlag();
}
