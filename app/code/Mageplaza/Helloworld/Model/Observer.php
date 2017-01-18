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
 * @package     Vaimo_IntegrationUI
 * @copyright   Copyright (c) 2009-2013 Vaimo AB
 * @author      Raivo Balins
 */

namespace Mageplaza\HelloWorld\Model;

class Observer
{
    protected $context;
    protected $_logFile = 'integrationui.log';

    public function __construct($context) {
    $this->context = $context;
}

    public function addOrderToQueue(\Magento\Framework\Event\Observer $observer)
    {
        $orderId = $observer->getOrder()->getEntityId();
        Mage::helper('integrationui')->setIntegrationOrderStatus($orderId, 0);
    }

    function get_this_class_methods($class){
        $allClassMethods = get_class_methods($class);
        if($parent_class = get_parent_class($class)){
            $parentClassMethods = get_class_methods($parent_class);
            $thisClassMethods = array_diff($allClassMethods, $parentClassMethods);
        }else{
            $thisClassMethods = $allClassMethods;
        }
        return($thisClassMethods);
    }

    public function productImportAfter(\Magento\Framework\Event\Observer $observer)
    {
        if (isset($observer['profile'])) {
            $profile = $observer['profile'];
        } else {
            return;
        }
        $default = array();
        if (isset($observer['default'])) {
            $default = $observer['default'];
        }
        $fieldMapping = array();
        if (isset($observer['mapping'])) {
            $fieldMapping = $observer['mapping'];
        }
        $data = array();
        if (isset($observer['response'])) {
            $data = $observer['response'];
        }
        $unmap = array();
        $temp = unserialize($profile->getUpdateMapping());
        foreach ($temp as $key => $value) {
            if ($value == 1) {
                $unmap[] = substr($key, strpos($key, '.') + 1);
            }
        }

        //mapping
        $attributeSets = array();
        $sets = Mage::getStoreConfig('integrationui/settings/attribute_sets');
        if ($sets) {
            $sets = unserialize($sets);
            foreach ($sets as $set) {
                $attributeSets[$set['code']] = $set['set'];
            }
        }
        $websites = array();
        $sites = Mage::getStoreConfig('integrationui/settings/site');
        if ($sites) {
            $sites = unserialize($sites);
            foreach ($sites as $site) {
                $websites[$site['code']] = $site['site'];
            }
        }
        $taxClasses = array();
        $clases = Mage::getStoreConfig('integrationui/settings/tax_class');
        if ($clases) {
            $clases = unserialize($clases);
            foreach ($clases as $class) {
                $taxClasses[$class['code']] = $class['text'];
            }
        }
        $productStatuses = array();
        $statuses = Mage::getStoreConfig('integrationui/settings/product_status', 1);
        if ($statuses) {
            $statuses = unserialize($statuses);
            foreach ($statuses as $status) {
                $productStatuses[$status['code']] = $status['stat'];
            }
        }

        $item = $default;
        $complexMapper = Mage::helper('integrationui/complexMapping');
        $complexMappingFields = $this->get_this_class_methods($complexMapper);

        foreach ($fieldMapping as $key => $value) {
            if (!isset($fieldMapping[$key])) {
                continue;
            }
            $newDataValue = null;
            $attr = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $key);
            if ($data[$value] != null) {

                if (in_array($value, $complexMappingFields)) {
                    $newDataValue = $complexMapper->$value($data[$value], $key);
                } else if (isset($data[$value])) {
                    $newDataValue = $data[$value];
                }

                if (($attr->getFrontendInput() == 'select' or $attr->getFrontendInput() == 'multiselect') and ($attr->getAttributeCode() <> 'status' and $attr->getAttributeCode() <> 'visibility')) {
                    if ($attr->usesSource()) {
                        $valuesIds = array_map(array($attr->getSource(), 'getOptionId'), array($newDataValue));
                        $optionId = current($valuesIds);
                        if (is_null($optionId)) {
                            $option = array(
                                'order' => array('a' => 0),
                                'value' => array('a' => array(0 => $newDataValue),),);
                            $attr->setOption($option)->save();

                            //Reload the attribute in order to get the new id
                            $attr = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $key);
                            $valuesIds = array_map(array($attr->getSource(), 'getOptionId'), array($newDataValue));
                            $optionId = current($valuesIds);
                        }
                        $newDataValue = $optionId;
                        $data[$value] = $newDataValue;//$attr->getOptionId($attr->getAttributeId(), $newDataValue);
                    }
                }
            }

            if ($newDataValue && !is_null($newDataValue)) {
                $item[$key] = $newDataValue;
            }
            $this->_log($key . ': ' . $data[$value] . ' / ' . $newDataValue);
        }


        if ($attributeSets) {
            if (isset($attributeSets[$item['attribute_set_id']])) {
                $item['attribute_set_id'] = $attributeSets[$item['attribute_set_id']];
            } else {
                $productData['attribute_set_id'] = $default['attribute_set_id'];
            }
        }
        if ($websites) {
            if (isset($websites[$item['website_id']])) {
                $item['website_id'] = $websites[$item['website_id']];
            }
        }
        if ($taxClasses) {
            if (isset($taxClasses[$item['tax_class_id']])) {
                $item['tax_class_id'] = $taxClasses[$item['tax_class_id']];
            }
        }
        if ($productStatuses) {
            if (isset($productStatuses[$item['status']])) {
                $item['status'] = $productStatuses[$item['status']];
            }
        }


        $response = array();
        $response[$item['sku']]['sku'] = $item['sku'];
        $response[$item['sku']]['attribute_set_id'] = $item['attribute_set_id'];
        $response[$item['sku']]['type_id'] = \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE;
        $response[$item['sku']]['parent_sku'] = null;
        $response[$item['sku']]['configurable_attributes'] = array();
        $response[$item['sku']]['product_data'] = $item;
        $response[$item['sku']]['product_data']['website_ids'] = $item['website_id'];
        $response[$item['sku']]['product_data']['status'] = $item['status'];
        $response[$item['sku']]['product_data']['visibility'] = $item['visibility'];
        unset ($response[$item['sku']]['product_data']['sku']);
        unset ($response[$item['sku']]['product_data']['attribute_set_id']);
        unset ($response[$item['sku']]['product_data']['category_ids']);

        $this->stockUpdate($response[$item['sku']]['sku'],$data['qty']);

        $this->unmap($response,$unmap);
        $observer->getEvent()->setResponse($response);

    }

    public function unmap(&$product,$unmap)
    {
        foreach ($product as $key => $value) {
            $this->_log('Unmapping SKU: ' . $key);
            $productId = Mage::getModel('catalog/product')->getIdBySku($key);

            if ($productId) {
                $this->_log('Product found');
                foreach ($unmap as $field) {
                    $this->_log('Unmapping: ' . $field);
                    if (isset($product[$key][$field])) {
                        unset($product[$key][$field]);
                    }
                    if (isset($product[$key]['product_data'][$field])) {
                        unset($product[$key]['product_data'][$field]);
                    }
                }
                if (isset($product[$key]['status'])) {
                    unset($product[$key]['status']);
                }
                if (isset($product[$key]['product_data']['status'])) {
                    unset($product[$key]['product_data']['status']);
                }
            }
        }
    }

    public function _log($m)
    {
        return null;
    }

    public function stockUpdate($sku,$qty)
    {
        $response = array();
        $response[0]['sku'] = $sku;
        $response[0]['qty'] = $qty;
        if ($qty > 0){
            $response[0]['stock_status'] = \Magento\CatalogInventory\Model\Stock::STOCK_IN_STOCK;
        } else {
            $response[0]['stock_status'] = \Magento\CatalogInventory\Model\Stock::STOCK_OUT_OF_STOCK;
        }
        Mage::getModel('integrationui/import')->importstock($response);
    }
}
