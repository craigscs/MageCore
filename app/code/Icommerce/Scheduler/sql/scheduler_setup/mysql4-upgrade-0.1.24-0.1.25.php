<?php
/**
 * Copyright (c) 2009-2013 Vaimo AB
 *
 * Vaimo reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Vaimo, except as provided by licence. A licence
 * under Vaimo's rights in the Program may be available directly from
 * Vaimo.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    Vaimo
 * @package     Icommerce_Scheduler
 * @copyright   Copyright (c) 2009-2012 Vaimo AB
 * @author      Urmo Schmidt
 */

/** @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();

if (version_compare(Mage::getVersion(), '1.6.0.0', '<') || !method_exists($installer, 'getFkName')) {
    $installer->run("

CREATE TABLE `icommerce_scheduler_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Id',
  `operation_id` int(10) unsigned NOT NULL COMMENT 'Operation Id',
  `created_at` datetime NOT NULL COMMENT 'Created At',
  `status` smallint(5) unsigned NOT NULL COMMENT 'Status',
  `message` varchar(255) DEFAULT NULL COMMENT 'Message',
  PRIMARY KEY (`id`),
  KEY `IDX_ICOMMERCE_SCHEDULER_MESSAGE_OPERATION_ID` (`operation_id`),
  CONSTRAINT `FK_SCHEDULER_OP_ID` FOREIGN KEY (`operation_id`) REFERENCES `icommerce_scheduler_operation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Scheduler Messages';

");
} else {
/**
 * Create table 'icommerce_scheduler_message'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('scheduler/message'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Id')
    ->addColumn('operation_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Operation Id')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => false,
    ), 'Created At')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Status')
    ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Message')
    ->addIndex($installer->getIdxName('scheduler/message', array('operation_id')), array('operation_id'))
    ->addForeignKey($installer->getFkName('scheduler/message', 'operation_id', 'scheduler/operation', 'id'),
        'operation_id', $installer->getTable('scheduler/operation'), 'id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Scheduler Messages');

$installer->getConnection()->createTable($table);
}

$installer->endSetup();