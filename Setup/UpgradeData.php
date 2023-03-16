<?php

namespace SDM\Altapay\Setup;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * UpgradeData constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }


    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        if (version_compare($context->getVersion(), '3.6.1', '<')) {
            $connection = $this->moduleDataSetup->getConnection();
            $tableName = $this->moduleDataSetup->getTable('altapay_token');
            $column = 'masked_pan';
            // Remove asterisks and leave last 4 digits from all existing records in the table
            $connection->beginTransaction();
            try {
                $query = "UPDATE {$tableName} SET {$column} = SUBSTR(REPLACE({$column}, '*', ''), -4) WHERE {$column} LIKE '%*%'";
                $connection->query($query);
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }
        
        $setup->endSetup();
    }
}
