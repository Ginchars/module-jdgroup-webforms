<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
 
namespace Vaimo\JdgroupWebforms\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use MageMe\WebForms\Setup\Table\{FormTable, FieldTable};

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();
        $formTable = $setup->getTable(FormTable::TABLE_NAME);
        $fieldTable = $setup->getTable(FieldTable::TABLE_NAME);

        if (!$setup->getConnection()->tableColumnExists($formTable, 'enable_sap_export')) {
            $setup->getConnection()->addColumn($formTable, 'enable_sap_export',
                [
                    Table::OPTION_TYPE => Table::TYPE_BOOLEAN,
                    Table::OPTION_UNSIGNED => true,
                    Table::OPTION_NULLABLE => false,
                    Table::OPTION_DEFAULT => 0,
                    'comment' => 'Enable SAP export'
                ]
            );
        }

        if (!$setup->getConnection()->tableColumnExists($formTable, 'sap_export_payload_template'))
        {
            $setup->getConnection()->addColumn($formTable, 'sap_export_payload_template',
                [
                    Table::OPTION_TYPE => Table::TYPE_TEXT,
                    'comment' => 'SAP Export Payload Template'
                ]
            );
        }

        $setup->endSetup();
    }
}
