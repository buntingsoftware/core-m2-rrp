<?php

namespace Bunting\Core\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $table = $setup->getConnection()
            ->newTable($setup->getTable('bunting_core_bunting'))
            ->addColumn('bunting_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
                'identity'  => true,
                'unsigned'  => true,
                'nullable'  => false,
                'primary'   => true,
            ), 'Id')
            ->addColumn('bunting_email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
                'nullable'  => false,
            ), 'Email')
            ->addColumn('bunting_account_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
                'nullable'  => false,
            ), 'Account Id')
            ->addColumn('bunting_website_monitor_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
                'nullable'  => false,
            ), 'Website Monitor Id')
            ->addColumn('bunting_unique_code', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
                'nullable'  => false,
            ), 'Unique Code')
            ->addColumn('bunting_subdomain', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
                'nullable'  => false,
            ), 'Subdomain')
            ->addColumn('feed_token', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
                'nullable'  => false,
            ), 'Feed Token')
            ->addColumn('password_api', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
                'nullable'  => false,
            ), 'Password Api')
            ->addColumn('server_region_subdomain_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
                'nullable'  => false,
            ), 'Server Region subdomain ID');
        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}