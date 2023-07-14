<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Cron;

use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;

class UpdateTransactionData
{
    const CRON_ENABLED = 'payment/sdm_altapay_config/cronScheduledTableOpt/enabled';
    const CRON_BATCH_LIMIT  = 'payment/sdm_altapay_config/cronScheduledTableOpt/limit';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var ResourceConnection
     */
    protected $connection;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param LoggerInterface $logger
     * @param ResourceConnection $resource
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ResourceConnection $resource,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
    }

    /**
     * @return void
     */
    public function execute()
    {
        $storeScope = ScopeInterface::SCOPE_STORE;
        $cronEnabled = $this->scopeConfig->getValue(self::CRON_ENABLED, $storeScope);
        $batchLimit = $this->scopeConfig->getValue(self::CRON_BATCH_LIMIT, $storeScope);
        $tableName = $this->resource->getTableName('sdm_altapay');
        $columnName = 'transactiondata';

        try {
            if (!$cronEnabled) {
                $this->logger->info('Cron is not enabled');
                return;
            }

            // Check if there are remaining records to update
            $hasRemainingRecords = $this->hasRemainingRecords($tableName);
            if ($hasRemainingRecords) {
                // Start transaction
                $this->connection->beginTransaction();
                $batchSize = 100; // Adjust batch size as per your needs
                try {
                    if(!empty($batchLimit)) {
                        $batchSize = $batchLimit; 
                    }

                    $updateSql = "UPDATE $tableName
                      SET has_xml_flag = 1,
                          $columnName = JSON_OBJECT(
                              'error_message', JSON_EXTRACT($columnName, '$.error_message'),
                              'CardHolderErrorMessage', JSON_EXTRACT($columnName, '$.CardHolderErrorMessage'),
                              'CardHolderErrorMessageMustBeShown', JSON_EXTRACT($columnName, '$.CardHolderErrorMessageMustBeShown')
                          )
                      WHERE has_xml_flag = 0
                      LIMIT $batchSize";

                    $this->connection->query($updateSql);
                    $this->logger->info('Optimize 100 records of sdm_altapay table');

                    // Commit the transaction if everything succeeded
                    $this->connection->commit();
                } catch (\Exception $e) {
                    // Rollback the transaction if an exception occurred
                    $this->connection->rollBack();
                    throw new \Exception(__('Something went wrong, ' . $e->getMessage()));
                }
            }
        } catch (\Exception $e) {
            throw new \Exception(__('Something went wrong, ' . $e->getMessage()));
        }
    }

    /**
     * @param $tableName
     *
     * @return bool
     */
    protected function hasRemainingRecords($tableName)
    {
        $selectSql = "SELECT COUNT(*) FROM $tableName WHERE has_xml_flag = 0";
        $result = $this->connection->fetchOne($selectSql);
        return $result > 0;
    }
}
