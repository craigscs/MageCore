<?php
/**
 *                     Mageplaza_HelloWorld extension
 *                     NOTICE OF LICENSE
 * 
 *                     This source file is subject to the Mageplaza License
 *                     that is bundled with this package in the file LICENSE.txt.
 *                     It is also available through the world-wide-web at this URL:
 *                     https://www.mageplaza.com/LICENSE.txt
 * 
 *                     @category  Mageplaza
 *                     @package   Mageplaza_HelloWorld
 *                     @copyright Copyright (c) 2016
 *                     @license   https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\HelloWorld\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * install tables
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('integrationui_profile')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('integrationui_profile')
            )
            ->addColumn(
                'profile_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'Post ID'
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable => false'],
                'Name'
            )
            ->addColumn(
                'process',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Process'
            )
            ->addColumn(
                'file_info',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'File Info'
            )
            ->addColumn(
                'curl_info',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Curl Info'
            )
            ->addColumn(
                'approach',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Approach'
            )
            ->addColumn(
                'event',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Event'
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '64k',
                [],
                'Status'
            )
            ->addColumn(
                'status_after',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '64k',
                [],
                'Status After'
            )
            ->addColumn(
                'Default Values',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Default Values'
            )
            ->addColumn(
                'field_mapping',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Field Mapping'
            )
            ->addColumn(
                'update_mapping',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Update Mapping'
            )
            ->addColumn(
                'prefix',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Prefix'
            )
            ->addColumn(
                'soap_info',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Soap Info'
            )
            ->setComment('Integration UI Profile');
            $installer->getConnection()->createTable($table);
        }

//        $installer->addAttribute('catalog_category', 'code', array(
//            'type'          => 'varchar',
//            'label'         => 'Code',
//            'required'      => true,
//            'unique'        => true,
//            'sort_order'    => 0,
//            'global'        => 0,
//            'group'         => 'General Information'
//        ));
//
//        $table = $installer->getConnection()
//            ->newTable($installer->getTable('integrationui_order_status'))
//            ->addColumn('order_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
//                'identity'  => true,
//                'unsigned'  => true,
//                'nullable'  => false,
//                'primary'   => true,
//            ), 'Order Id')
//            ->addColumn('integration_status', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,, null, array(), 'Integration Status')
//            ->addForeignKey(
//                $installer->getFkName(
//                    'integrationui_order_status',
//                    'order_id',
//                    'sales/order',
//                    'entity_id'),
//                'order_id', $installer->getTable('sales_order'), 'entity_id',
//                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE, \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE)
//            ->setComment('Integration Order Status');
//
//        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
