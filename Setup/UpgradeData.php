<?php

namespace SDM\Altapay\Setup;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        if (version_compare($context->getVersion(), '3.6.0', '<')) {
            $connection = $this->moduleDataSetup->getConnection();
            $tableName = $this->moduleDataSetup->getTable('altapay_token');
            $connection->update(
                $tableName,
                [
                    'masked_pan' => new \Zend_Db_Expr("CONCAT('************', RIGHT(masked_pan, 4))")
                ]
            ); 
        }
        
        $setup->endSetup();
    }
}
