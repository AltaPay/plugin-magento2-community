<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */

namespace SDM\Altapay\Api\Data;

/**
 * Transaction interface.
 * @api
 */
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
    const TRANSACTION_DATA     = 'transactiondata';
    const PARAMETERS_DATA      = 'parametersdata';
    const CREATED_AT           = 'created_at';
    
    /**
     * @param string $paymentid
     */
    public function setPaymentid(string $paymentid);

    /**
     * @return string
     */
    public function getPaymentid(): string;

    /**
     * @param string $transactionid
     */
    public function setTransactionid(string $transactionid);

    /**
     * @return string
     */
    public function getTransactionid(): string;

    /**
     * @param string $orderid
     */
    public function setOrderid(string $orderid);

    /**
     * @return string
     */
    public function getOrderid(): string;

    /**
     * @param string $parametersdata
     */
    public function setParametersdata(string $parametersdata);

    /**
     * @return string
     */
    public function getParametersdata(): string;

    /**
     * @param string $transactiondata
     */
    public function setTransactiondata(string $transactiondata);
    
    /**
     * @return string
     */
    public function getTransactiondata(): string;
}
