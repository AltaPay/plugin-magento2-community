<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */
namespace SDM\Valitor\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 * @package SDM\Valitor\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        //Add a new attribute for the redirect to the payment form
        $setup->startSetup();
        $orderTable = 'sales_order';
        $columnName = 'valitor_payment_form_url';
        $oldColumnName = 'altapay_payment_form_url';
        if (!$setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $columnName)) {
            if ($setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $oldColumnName)) {
                $setup->getConnection()->changeColumn(
                    $setup->getTable('sales_order'),
                    'altapay_payment_form_url',
                    'valitor_payment_form_url',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 655366,
                        'nullable' => true,
                        'visible' => false,
                        'comment' => 'Valitor Payment Form Url',
                    ]
                );
            } else {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable($orderTable),
                        $columnName,
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'length' => 65536,
                            'nullable' => true,
                            'visible' => false,
                            'comment' => 'Valitor Payment Form Url',
                        ]
                    );
            }
        } elseif ($setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $oldColumnName)) {
            $setup->getConnection()
                    ->dropColumn(
                        $setup->getTable($orderTable),
                        $oldColumnName
                    );
        }
        $setup->endSetup();
    }
}
