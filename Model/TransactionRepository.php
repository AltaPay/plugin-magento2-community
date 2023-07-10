<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Magento\Store\Model\StoreManagerInterface;
use SDM\Altapay\Api\TransactionRepositoryInterface;
use SDM\Altapay\Model\TransactionFactory;

/**
 * Class TransactionRepository
 * Service class to write module's transaction entities.
 */
class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * TransactionRepository constructor.
     *
     * @param TransactionFactory    $transactionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->storeManager       = $storeManager;
    }

    /**
     * It creates the entity and saves the JSON request.
     *
     * @param string $orderid
     * @param string $transactionid
     * @param string $paymentid
     * @param string $transactiondata
     */
    public function addTransactionData($orderid, $transactionid, $paymentid, $transactiondata)
    {
        /** @var Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->setOrderid($orderid);
        $transaction->setTransactionid($transactionid);
        $transaction->setPaymentid($paymentid);
        $transaction->setTransactiondata($transactiondata);
        $transaction->getResource()->save($transaction);
    }
}
