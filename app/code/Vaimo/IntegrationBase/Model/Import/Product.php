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

class Product extends \Vaimo\IntegrationBase\Model\Import\Abst
{
    protected $_productModel;
    protected $_eventPrefix = 'product';

    protected function _construct()
    {
        parent::_construct();
        $this->_init('integrationbase/product', 'Product import');
        $this->_successMessage = '%d product(s) imported';
        $this->_failureMessage = '%d product(s) failed to import';
        $this->_productModel = Mage::getModel('catalog/product');
    }

    protected function _getSuperAttributeId($productId, $attributeId)
    {
        $select = $this->_getRead()
            ->select()
            ->from($this->_getTableName('catalog/product_super_attribute'))
            ->where('product_id = ?', $productId)
            ->where('attribute_id = ?', $attributeId);

        return $this->_getRead()->fetchOne($select);
    }

    protected function _setSuperAttributeData($productId, array $attributes)
    {
        $position = 0;

        foreach ($attributes as $code) {
            /** @var $attribute Mage_Eav_Model_Entity_Attribute */
            $attribute = Mage::getModel('eav/entity_attribute');
            $attribute->loadByCode(Mage_Catalog_Model_Product::ENTITY, $code);

            /** @var $configurableAttribute Mage_Catalog_Model_Product_Type_Configurable_Attribute */
            $configurableAttribute = Mage::getModel('catalog/product_type_configurable_attribute');

            if ($id = $this->_getSuperAttributeId($productId, $attribute->getId())) {
                $configurableAttribute->load($id);
            }

            $configurableAttribute
                ->setProductId($productId)
                ->setAttributeId($attribute->getAttributeId())
                ->setPosition($position++)
                ->setStoreId(0)
                ->setUseDefault(null)
                ->setLabel($attribute->getFrontendLabel())
                ->setAttributeCode($attribute->getAttributeCode())
                ->setFrontendLabel($attribute->getFrontendLabel())
                ->setStoreLabel($attribute->getFrontendLabel())
                ->save();
        }
    }

    protected function _getSuperLinkId($productId, $parentId)
    {
        $select = $this->_getRead()
            ->select()
            ->from($this->_getTableName('catalog/product_super_link'))
            ->where('product_id = ?', $productId)
            ->where('parent_id = ?', $parentId);

        return $this->_getRead()->fetchOne($select);
    }

    protected function _linkProducts($simpleId, $configurableSku)
    {
        if (!$configurableSku) {
            return;
        }

        $configurableId = Mage::getSingleton('catalog/product')->getIdBySku($configurableSku);

        if (!$this->_getSuperLinkId($simpleId, $configurableId)) {
            $this->_getWrite()->delete($this->_getTableName('catalog/product_super_link'), array(
                'product_id = ?' => $simpleId
            ));

            $this->_getWrite()->delete($this->_getTableName('catalog/product_relation'), array(
                'child_id = ?' => $simpleId
            ));

            $this->_getWrite()->insert($this->_getTableName('catalog/product_super_link'), array(
                'product_id' => $simpleId,
                'parent_id' => $configurableId,
            ));

            $this->_getWrite()->insert($this->_getTableName('catalog/product_relation'), array(
                'parent_id' => $configurableId,
                'child_id' => $simpleId,
            ));
        }
    }

    protected function _setBundleData($productId, $bundleData)
    {
        if (!isset($bundleData['options']) || !is_array($bundleData['options'])) {
            return;
        }

        $this->_getWrite()->delete($this->_getTableName('bundle/option'), array('parent_id = ?' => (int)$productId));

        foreach ($bundleData['options'] as $optionData) {
            // bundle_option
            $bind = array(
                'parent_id' => (int)$productId,
                'required' => isset($optionData['required']) ? (int)$optionData['required'] : 0,
                'position' => isset($optionData['position']) ? (int)$optionData['position'] : 0,
                'type' => isset($optionData['type']) ? $optionData['type'] : 'select',
            );

            $this->_getWrite()->insert($this->_getTableName('bundle/option'), $bind);
            $optionId = $this->_getWrite()->lastInsertId();

            // bundle_option_value
            if (isset($optionData['title'])) {
                if (is_array($optionData['title'])) {
                    foreach ($optionData['title'] as $storeId => $title) {
                        $bind = array(
                            'option_id' => $optionId,
                            'store_id' => $storeId,
                            'title' => $title,
                        );

                        $this->_getWrite()->insert($this->_getTableName('bundle/option_value'), $bind);
                    }
                } else {
                    $bind = array(
                        'option_id' => $optionId,
                        'store_id' => 0,
                        'title' => $optionData['title'],
                    );

                    $this->_getWrite()->insert($this->_getTableName('bundle/option_value'), $bind);
                }
            }

            // bundle_selection
            if (isset($optionData['selections']) && is_array($optionData['selections'])) {
                foreach ($optionData['selections'] as $selectionData) {
                    $bind = array(
                        'option_id' => $optionId,
                        'parent_product_id' => $productId,
                        'product_id' => Mage::getSingleton('catalog/product')->getIdBySku($selectionData['product_sku']),
                        'position' => isset($selectionData['position']) ? (int)$selectionData['position'] : 0,
                        'is_default' => isset($selectionData['is_default']) ? (int)$selectionData['is_default'] : 0,
                        'selection_qty' => isset($selectionData['selection_qty']) ? (float)$selectionData['selection_qty'] : 1,
                        'selection_can_change_qty' => isset($selectionData['selection_can_change_qty']) ? (int)$selectionData['selection_can_change_qty'] : 1,
                    );

                    $this->_getWrite()->insert($this->_getTableName('bundle/selection'), $bind);
                }
            }
        }
    }

    /**
     * @param Vaimo_IntegrationBase_Model_Product $item
     * @return bool
     */
    protected function _importRecord($item)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = $this->_productModel->reset();
        $productId = $product->getIdBySku($item->getSku());

        if ($productId) {
            $this->_log($item->getSku() . ' (update)');
            $product->load($productId);
        } else {
            $this->_log($item->getSku() . ' (create)');
            $product->setSku($item->getSku());
            $product->setTypeId($item->getTypeId());
            $product->setStockData(array());
        }

        $product->setAttributeSetId($item->getAttributeSetId());

        if ($item->getProductData('add_category_ids')) {
            $product->setCategoryIds(array_merge($product->getCategoryIds(), $item->getProductData('add_category_ids')));
        }

        if ($item->getProductData('remove_category_ids')) {
            $product->setCategoryIds(array_diff($product->getCategoryIds(), $item->getProductData('remove_category_ids')));
        }

        $product->addData($item->getProductData());

        // Shop specific special price will not be used if there is no value stored for default store
        if (!$product->hasSpecialPrice()) {
            $product->setSpecialPrice(null);
        }

        // Fire an event that would allow different customizations based on the product data
        Mage::dispatchEvent('integrationbase_import_product', array(
            'product' => $product,
            'raw' => $item
        ));

        $product->save();

        switch ($product->getTypeId()) {
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                $this->_setSuperAttributeData($product->getId(), $item->getConfigurableAttributes());
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_SIMPLE:
                $this->_linkProducts($product->getId(), $item->getParentSku());
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                $this->_setBundleData($product->getId(), $item->getBundleData());
                break;
        }

        Mage::helper('integrationbase/pricerules')->applyAllRulesToProduct($product,'product');

        return $this;
    }

    protected function _deleteRecord($item)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product');
        $productId = $product->getIdBySku($item->getSku());

        if ($productId) {
            $this->_log($item->getSku() . ' (delete)');
            $product->load($productId);
            $product->delete();
        }

        return $this;
    }
}