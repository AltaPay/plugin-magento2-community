<?php
namespace SDM\Altapay\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchInterface;

class UpdateTransactionData implements DataPatchInterface, PatchRevertableInterface
{
    private $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        // Retrieve the table name and column name
        $tableName = $this->moduleDataSetup->getTable('sdm_altapay');
        $columnName = 'transactiondata';

        // Update the column data using SQL query
        $updateSql = "UPDATE " . $tableName . " SET " . $columnName . " = JSON_OBJECT(
            'error_message', JSON_EXTRACT(" . $columnName . ", '$.error_message'),
            'CardHolderErrorMessage', JSON_EXTRACT(" . $columnName . ", '$.CardHolderErrorMessage'),
            'CardHolderErrorMessageMustBeShown', JSON_EXTRACT(" . $columnName . ", '$.CardHolderErrorMessageMustBeShown')
        )";
        $this->moduleDataSetup->getConnection()->query($updateSql);

        $this->moduleDataSetup->endSetup();
    }

    public function revert()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
