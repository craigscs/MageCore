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
 * @package     Vaimo_IntegrationBase
 * @copyright   Copyright (c) 2009-2013 Vaimo AB
 * @author      Urmo Schmidt
 */
namespace Vaimo\IntegrationBase\Model\Import;

abstract class Abst extends \Magento\Framework\DataObject
{
    protected $_type;
    protected $_eventPrefix = 'abstract';
    protected $_name = 'Import';
    protected $_collection = null;
    protected $_logFile = 'integrationbase.log';
    protected $_read = null;
    protected $_write = null;
    protected $_limit = 0;
    protected $_successCount = 0;
    protected $_failureCount = 0;
    protected $_successMessage = '%d record(s) updated';
    protected $_failureMessage = '%d record(s) failed to update';

    abstract protected function _importRecord($item);
    abstract protected function _deleteRecord($item);

    protected $object_manager;

    public function setObjectManager($object_manager)
    {
    $this->object_manager = $object_manager;
    }

    protected function _init($type, $name)
    {
        $this->_type = $type;
        $this->_name = $name;
    }

    protected function _getEntityTypeByCode($entityCode)
    {
        $group = strstr($entityCode, '_', true);
        $type = substr(strstr($entityCode, '_'), 1);
        return $group . '/' . $type;
    }

    protected function _getCollection()
    {
        if (!$this->_collection) {
            $statuses = array(
                1,
                2,
            );

            $productCollection = $this->object_manager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
            $productCollection->load();

            // Allow project specific customizations to collection loading
//            $event = $this->object_manager->create('Vaimo\IntegrationBase\Import')
//            Mage::dispatchEvent($this->_eventPrefix . '_integrationbase_import_collection_prepare', array('collection' => $collection));

            $this->_collection = $collection;
        }

        return $this->_collection;
    }

    protected function _log($message)
    {
//        Mage::log($message, null, $this->_logFile, true);
        echo $message . "\n";
        flush();
        @ob_flush();
    }

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getRead()
    {
        if (!$this->_read) {
            $this->_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        }

        return $this->_read;
    }

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getWrite()
    {
        if (!$this->_write) {
            $this->_write = Mage::getSingleton('core/resource')->getConnection('core_write');
        }

        return $this->_write;
    }

    protected function _getTableName($modelEntity)
    {
        return Mage::getSingleton('core/resource')->getTableName($modelEntity);
    }

    public function helper()
    {
        return Mage::helper('integrationbase');
    }

    final public function import($limit = 0, $operationId = 0)
    {
//        Mage::log("General Import Started", null, "VaimoProductImport.log");
        $this->_limit = $limit;
//        $this->_log($this->_name . ' started');

        if ($this->_limit) {
//            $this->_log($this->helper()->__('Maximum number of records to be processed: %d', $this->_limit));
        }

        $logged = false;
        $progressMin = 0;
        $progressMax = count($this->_getCollection());
        $progressPos = 0;

        foreach ($this->_getCollection() as $item) {
            if (!$logged) {
                $this->_log('');
                $logged = true;
            }

            Mage::dispatchEvent('integrationbase_import_' . $this->_eventPrefix . '_before', array('item' => $item));

            switch ($item->getRowStatus()) {
                case Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_IMPORT:
                    try {
                        $this->_importRecord($item);
                        $item->setRowStatus(Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_IMPORTED);
                        $item->setRowResult('');
                        $item->save();
                        $this->_successCount++;
                    } catch (Exception $e) {
                        $item->setRowResult($e->getMessage());
                        $item->save();
                        $this->_log('ERROR: ' . $e->getMessage());
                        $this->_failureCount++;
                    }
                    break;
                case Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_DELETE:
                    try {
                        $this->_deleteRecord($item);
                        $item->setRowStatus(Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_DELETED);
                        $item->setRowResult('');
                        $item->save();
                        $this->_successCount++;
                    } catch (Exception $e) {
                        $item->setRowResult($e->getMessage());
                        $item->save();
                        $this->_log('ERROR: ' . $e->getMessage());
                        $this->_failureCount++;
                    }
                    break;
            }

            Mage::dispatchEvent('integrationbase_import_' . $this->_eventPrefix . '_after', array('item' => $item));

            if ($operationId) {
                Mage::helper('scheduler')->setOperationProgress($operationId, $progressMin, $progressMax, ++$progressPos);
            }
        }

        if ($logged) {
            $this->_log('');
        }

        $this->_log($this->_name . ' complete');
    }

    final public function run($limit = 0, $operationId)
    {
//        try {
//            $this->import($limit, $operationId);
//
//            if ($this->_successCount == 0 && $this->_failureCount == 0) {
//                $this->_log(\Icommerce_Utils::getTriggerResultXml(\Icommerce_Utils::TRIGGER_STATUS_NOTHING_TO_DO, ''));
//            } elseif ($this->_successCount > 0 && $this->_failureCount > 0) {
//                $this->_log(\Icommerce_Utils::getTriggerResultXml(
//                    \Icommerce_Utils::TRIGGER_STATUS_EXCEPTIONS,
//                    $this->helper()->__($this->_successMessage, $this->_successCount)
//                        . ', ' . $this->helper()->__($this->_failureMessage, $this->_failureCount)
//                ));
//            } elseif ($this->_successCount > 0) {
//                $this->_log(\Icommerce_Utils::getTriggerResultXml(
//                    \Icommerce_Utils::TRIGGER_STATUS_SUCCEEDED,
//                    $this->helper()->__($this->_successMessage, $this->_successCount)
//                ));
//            } else {
//                $this->_log(\Icommerce_Utils::getTriggerResultXml(
//                    \Icommerce_Utils::TRIGGER_STATUS_FAILED,
//                    $this->helper()->__($this->_failureMessage, $this->_failureCount)
//                ));
//            }
//        } catch (\Exception $e) {
//            $this->_log(\Icommerce_Utils::getTriggerResultXml(\Icommerce_Utils::TRIGGER_STATUS_FAILED, $e->getMessage()));
//        }
    }
}
