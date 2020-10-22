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
     * @param string $transactiondata
     *
     * @see TransactionInterface
     */
    public function setTransactiondata($transactiondata)
    {
        $this->setData(TransactionInterface::TRANSACTION_DATA, $transactiondata);
    }

    /**
     * @return string
     * @see TransactionInterface
     */
    public function getTransactiondata()
    {
        return $this->getData(TransactionInterface::TRANSACTION_DATA);
    }

    /**
     * @param string $parametersdata
     *
     * @see TransactionInterface
     */
    public function setParametersdata($parametersdata)
    {
        $this->setData(TransactionInterface::PARAMETERS_DATA, $parametersdata);
    }

    /**
     * @return string
     * @see TransactionInterface
     */
    public function getParametersdata()
    {
        return $this->getData(TransactionInterface::PARAMETERS_DATA);
    }
}
