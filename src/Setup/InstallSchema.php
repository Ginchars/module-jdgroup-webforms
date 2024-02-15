<?php declare(strict_types=1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\JdgroupWebforms\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use MageMe\WebForms\Setup\Table\{FormTable, FieldTable};

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();
        $formTable = $setup->getTable(FormTable::TABLE_NAME);

        $setup->getConnection()->addColumn($formTable, 'enable_sap_export',
            [
                Table::OPTION_TYPE => Table::TYPE_BOOLEAN,
                Table::OPTION_UNSIGNED => true,
                Table::OPTION_NULLABLE => false,
                Table::OPTION_DEFAULT => 0,
                'comment' => 'Enable SAP export'
            ]
        );

        $setup->endSetup();
    }
}
