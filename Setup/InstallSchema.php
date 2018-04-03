<?php

namespace EditionGuard\EditionGuard\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        $columns = [
            'use_editionguard' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Editionguard flag'
            ],
            'editionguard_resource' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'COMMENT' => 'Editionguard unique resource ID'
            ],
            'editionguard_src' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'COMMENT' => 'Editionguard content source URL'
            ],
        ];

        $connection = $installer->getConnection();

        foreach ($columns as $name => $definition) {
            $connection->addColumn($installer->getTable('downloadable_link'), $name, $definition);
        }

        foreach ($columns as $name => $definition) {
            $connection->addColumn($installer->getTable('downloadable_link_purchased_item'), $name, $definition);
        }

        $installer->endSetup();
    }
}
