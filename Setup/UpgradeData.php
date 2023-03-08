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
