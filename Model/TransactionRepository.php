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
use SDM\Altapay\Api\Data\TransactionInterface;
use SDM\Altapay\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;

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
     * @var TransactionCollectionFactory
     */
    protected $transactionCollectionFactory;

    /**
     * TransactionRepository constructor.
     *
     * @param TransactionFactory    $transactionFactory
     * @param StoreManagerInterface $storeManager
     * @param TransactionCollectionFactory $transactionCollectionFactory
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        StoreManagerInterface $storeManager,
        TransactionCollectionFactory $transactionCollectionFactory
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->storeManager       = $storeManager;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
    }

    /**
     * It creates the entity and saves the JSON request.
     *
     * @param string $orderid
     * @param string $transactionid
     * @param string $paymentid
     * @param string $transactiondata
     * @param string $parametersdata
     */
    public function addTransactionData($orderid, $transactionid, $paymentid, $transactiondata, $parametersdata)
    {
        /** @var Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->setOrderid($orderid);
        $transaction->setTransactionid($transactionid);
        $transaction->setPaymentid($paymentid);
        $transaction->setTransactiondata($transactiondata);
        $transaction->setParametersdata($parametersdata);
        $transaction->getResource()->save($transaction);
    }

    /**
     * Get transaction by Order ID
     *
     * @param string $orderId
     * @return $transactionId
     */
    public function getTransactionDataByOrderId($orderId)
    {
        $collection = $this->transactionCollectionFactory->create()
            ->addFieldToSelect(TransactionInterface::TRANSACTION_ID)
            ->addFieldToFilter(TransactionInterface::ORDER_ID, $orderId);

        $transactionId = $collection->getFirstItem()->getData(TransactionInterface::TRANSACTION_ID);

        return $transactionId;
    }
}
