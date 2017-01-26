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
 * @copyright   Copyright (c) 2009-2013 Vaimo AB
 * @author      Raivo Balins <raivo.balins@vaimo.com>
 */

/** @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;
$old_magento = version_compare(Mage::getVersion(), '1.6.0.0', '<') || !method_exists($installer, 'getFkName');

$installer->startSetup();

if ($old_magento) {
    $definition = "smallint(6) NOT NULL DEFAULT '0' COMMENT 'Email Enabled'";
} else {
    $definition = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => false,
        'default'   => 0,
        'comment'   => 'Email Enabled'
    );
}
$installer->getConnection()->addColumn($installer->getTable('scheduler/operation'), 'email_enabled', $definition);

if ($old_magento) {
    $definition = "varchar(60) NOT NULL DEFAULT '0,1,2,3,4' COMMENT 'Email Status'";
} else {
    $definition = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 60,
        'nullable'  => false,
        'default'   => '0,1,2,3,4',
        'comment'   => 'Email Status'
    );
}
$installer->getConnection()->addColumn($installer->getTable('scheduler/operation'), 'email_status', $definition);

if ($old_magento) {
    $definition = "varchar(60) NULL COMMENT 'Email Template'";
} else {
    $definition = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 60,
        'nullable'  => true,
        'default'   => null,
        'comment'   => 'Email Template'
    );
}
$installer->getConnection()->addColumn($installer->getTable('scheduler/operation'), 'email_template', $definition);

if ($old_magento) {
    $definition = "varchar(60) NULL COMMENT 'Email Sender'";
} else {
    $definition = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 60,
        'nullable'  => true,
        'default'   => null,
        'comment'   => 'Email Sender'
    );
}
$installer->getConnection()->addColumn($installer->getTable('scheduler/operation'), 'email_sender', $definition);

if ($old_magento) {
    $definition = "varchar(255) DEFAULT NULL COMMENT 'Email Receiver'";
} else {
    $definition = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => null,
        'comment'   => 'Email Receiver'
    );
}
$installer->getConnection()->addColumn($installer->getTable('scheduler/operation'), 'email_receiver', $definition);

$installer->endSetup();