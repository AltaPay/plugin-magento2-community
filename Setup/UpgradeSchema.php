<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        //Add a new attribute for the redirect to the payment form
        $setup->startSetup();
        $orderTable    = 'sales_order';
        $columnName    = 'altapay_payment_form_url';
        $oldColumnName = 'valitor_payment_form_url';
        if (!$setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $columnName)) {
            if ($setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $oldColumnName)) {
                $setup->getConnection()->changeColumn(
                    $setup->getTable('sales_order'),
                    $oldColumnName,
                    $columnName,
                    [
                        'type'     => Table::TYPE_TEXT,
                        'length'   => 655366,
                        'nullable' => true,
                        'visible'  => false,
                        'comment'  => 'Altapay Payment Form Url',
                    ]
                );
            } else {
                $setup->getConnection()->addColumn(
                    $setup->getTable($orderTable),
                    $columnName,
                    [
                        'type'     => Table::TYPE_TEXT,
                        'length'   => 65536,
                        'nullable' => true,
                        'visible'  => false,
                        'comment'  => 'Altapay Payment Form Url',
                    ]
                );
            }
        } elseif ($setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $oldColumnName)) {
            $setup->getConnection()->dropColumn(
                $setup->getTable($orderTable),
                $oldColumnName
            );
        }
        /**
         * Replace database priceInclusive column
         */
        $columnTaxConfig    = 'altapay_price_includes_tax';
        $oldColumnTaxConfig = 'valitor_price_includes_tax';
        if (!$setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $columnTaxConfig)) {
            if ($setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $oldColumnTaxConfig)) {
                $setup->getConnection()->changeColumn(
                    $setup->getTable('sales_order'),
                    $oldColumnTaxConfig,
                    $columnTaxConfig,
                    [
                        'type'     => Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'visible'  => false,
                        'comment'  => 'Whether catalog prices entered from Magento Admin include tax.',
                    ]
                );
            } else {
                $setup->getConnection()->addColumn(
                    $setup->getTable($orderTable),
                    $columnTaxConfig,
                    [
                        'type'     => Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'visible'  => false,
                        'comment'  => 'Whether catalog prices entered from Magento Admin include tax.',
                    ]
                );
            }
        } elseif ($setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $oldColumnTaxConfig)) {
            $setup->getConnection()->dropColumn(
                $setup->getTable($orderTable),
                $oldColumnTaxConfig
            );
        }
        /**
         * Replace database table from sdm_valitor to sdm_altapay
         */
        if ($setup->getConnection()->isTableExists($setup->getTable('sdm_valitor')) == true
            && $setup->getConnection()->isTableExists($setup->getTable('sdm_altapay')) == false
        ) {
            $setup->getConnection()->renameTable($setup->getTable('sdm_valitor'), $setup->getTable('sdm_altapay'));
        }
        /**
         * Replace database table from valitor_token to altapay_token
         */
        $altapayTokenTableName = $setup->getTable('altapay_token');
        if ($setup->getConnection()->isTableExists($setup->getTable('valitor_token')) == true) {
            $setup->getConnection()->renameTable($setup->getTable('valitor_token'), $setup->getTable('altapay_token'));
        } elseif ($setup->getConnection()->isTableExists($altapayTokenTableName) != true) {
            $altapayTokenTableName = $setup->getConnection()->newTable(
                $altapayTokenTableName
            )->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ],
                'Id'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Customer Id'
            )->addColumn(
                'token',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Token'
            )->addColumn(
                'masked_pan',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Masked Pan'
            )->addColumn(
                'currency_code',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Currency Code'
            )->addColumn(
                'primary',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => 0],
                'Primary Token'
            )->addColumn(
                'timestamp',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false],
                'Timestamp'
            )->addColumn(
                'expires',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Card Expiry Date'
            )->addColumn(
                'card_type',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Card Type'
            )->setComment('Altapay Tokens');
            $setup->getConnection()->createTable($altapayTokenTableName);
        }
        $setup->endSetup();
    }
}
