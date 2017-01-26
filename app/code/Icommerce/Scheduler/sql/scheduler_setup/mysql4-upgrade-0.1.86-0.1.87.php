<?php
/**
 * Copyright (c) 2009-2015 Vaimo AB
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
 * @copyright   Copyright (c) 2009-2015 Vaimo AB
 * @author      Raivo Balins <raivo.balins@vaimo.com>
 */

/** @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;
$old_magento = version_compare(Mage::getVersion(), '1.6.0.0', '<') || !method_exists($installer, 'getFkName');

$installer->startSetup();

if ($old_magento) {
    $definition = "smallint(6) DEFAULT NULL COMMENT 'Rerun on Failed Status'";
} else {
    $definition = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => true,
        'comment'   => 'Rerun on Failed Status'
    );
}

$installer->getConnection()->addColumn($installer->getTable('scheduler/operation'), 'rerun', $definition);

if ($old_magento) {
    $definition = "smallint(6) DEFAULT NULL COMMENT 'Rerun Tries Count'";
} else {
    $definition = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => true,
        'comment'   => 'Rerun Tries Count'
    );
}

$installer->getConnection()->addColumn($installer->getTable('scheduler/operation'), 'rerun_count', $definition);

if ($old_magento) {
    $definition = "smallint(6) DEFAULT NULL COMMENT 'Rerun Progress'";
} else {
    $definition = array(
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => true,
        'comment'   => 'Rerun Progress'
    );
}

$installer->getConnection()->addColumn($installer->getTable('scheduler/operation'), 'rerun_progress', $definition);

$installer->endSetup();