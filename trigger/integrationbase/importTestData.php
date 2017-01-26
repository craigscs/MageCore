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
 * @author      Urmo Schmidt <urmo.schmidt@vaimo.com>
 */

header('Content-Type: text/plain; charset=utf-8');
ini_set('memory_limit', '1024M');
chdir('../..');

require 'app/Mage.php';

if (!Mage::isInstalled()) {
    echo "Application is not installed yet, please complete install wizard first.";
    exit;
}

$_SERVER['SCRIPT_NAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

echo 'Product Import' . "\n";

$productData = array(
    array(
        'sku' => 'OXFORD',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
        'parent_sku' => '',
        'configurable_attributes' => array('color', 'size'),
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            'website_ids' => array(1, 2),
            'name' => 'Oxford Dress Shirt',
            'description' => 'By using finer yarns combined with our unique finishing process we’ve improved the lustre and performance of the classic oxford shirt. The special weaving technique forms a fabric with elegant texture and soft colours. Team it up with a well cut-suit for a modern and smart business outfit.',
            'short_description' => 'Classic oxford shirt',
            'price' => 24.95,
        ),
    ),
    array(
        'sku' => 'OXFORD-BLUE-S',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        'parent_sku' => 'OXFORD',
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'website_ids' => array(1, 2),
            'name' => 'Oxford Dress Shirt, Blue, Small',
            'color' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'color', 'Blue'),
            'size' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'size', 'S'),
            'price' => 24.95,
        ),
    ),
    array(
        'sku' => 'OXFORD-BLUE-M',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        'parent_sku' => 'OXFORD',
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'website_ids' => array(1, 2),
            'name' => 'Oxford Dress Shirt, Blue, Medium',
            'color' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'color', 'Blue'),
            'size' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'size', 'M'),
            'price' => 24.95,
        ),
    ),
    array(
        'sku' => 'OXFORD-BLUE-L',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        'parent_sku' => 'OXFORD',
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'website_ids' => array(1, 2),
            'name' => 'Oxford Dress Shirt, Blue, Large',
            'color' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'color', 'Blue'),
            'size' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'size', 'L'),
            'price' => 24.95,
        ),
    ),
    array(
        'sku' => 'OXFORD-RED-S',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        'parent_sku' => 'OXFORD',
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'website_ids' => array(1, 2),
            'name' => 'Oxford Dress Shirt, Red, Small',
            'color' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'color', 'Red'),
            'size' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'size', 'S'),
            'price' => 24.95,
        ),
    ),
    array(
        'sku' => 'OXFORD-RED-M',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        'parent_sku' => 'OXFORD',
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'website_ids' => array(1, 2),
            'name' => 'Oxford Dress Shirt, Red, Medium',
            'color' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'color', 'Red'),
            'size' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'size', 'M'),
            'price' => 24.95,
        ),
    ),
    array(
        'sku' => 'OXFORD-RED-L',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        'parent_sku' => 'OXFORD',
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'website_ids' => array(1, 2),
            'name' => 'Oxford Dress Shirt, Red, Large',
            'color' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'color', 'Red'),
            'size' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'size', 'L'),
            'price' => 24.95,
        ),
    ),
    array(
        'sku' => 'GEOMETRIC',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
        'parent_sku' => '',
        'configurable_attributes' => array('color'),
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            'website_ids' => array(1, 2),
            'name' => 'Geometric Tie',
            'description' => 'Crafted from luxurious woven silk this piece of neckwear combines impeccable draping with a unique texture. 100% silk, made in England.',
            'short_description' => 'Luxurious tie',
            'price' => 12.50,
        ),
    ),
    array(
        'sku' => 'GEOMETRIC-RED',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        'parent_sku' => 'GEOMETRIC',
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'website_ids' => array(1, 2),
            'name' => 'Geometric Tie, Red',
            'color' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'color', 'Red'),
            'price' => 12.50,
        ),
    ),
    array(
        'sku' => 'GEOMETRIC-PURPLE',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        'parent_sku' => 'GEOMETRIC',
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'website_ids' => array(1, 2),
            'name' => 'Geometric Tie, Purple',
            'color' => Mage::helper('integrationbase/attribute')->getAttributeValue('catalog_product', 'color', 'Purple'),
            'price' => 12.50,
        ),
    ),
    array(
        'sku' => 'OUTFIT',
        'attribute_set_id' => 4,
        'type_id' => Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
        'product_data' => array(
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            'website_ids' => array(1, 2),
            'name' => 'Outfit',
            'sku_type' => 1, // 0 - Dynamic, 1 - Fixed
            'weight_type' => 0, // 0 - Dynamic, 1 - Fixed
            'price_type' => 0, // 0 - Dynamic, 1 - Fixed
        ),
        'bundle_data' => array(
            'options' => array(
                array(
                    'title' => 'Shirt',
                    'type' => 'select',
                    'required' => true,
                    'position' => 1,
                    'selections' => array(
                        array(
                            'product_sku' => 'OXFORD-BLUE-S',
                            'selection_qty' => 1,
                            'selection_can_change_qty' => false,
                            'position' => 1,
                            'is_default' => true,
                        ),
                        array(
                            'product_sku' => 'OXFORD-BLUE-M',
                            'selection_qty' => 1,
                            'selection_can_change_qty' => false,
                            'position' => 2,
                            'is_default' => false,
                        ),
                        array(
                            'product_sku' => 'OXFORD-BLUE-L',
                            'selection_qty' => 1,
                            'selection_can_change_qty' => false,
                            'position' => 3,
                            'is_default' => false,
                        ),
                        array(
                            'product_sku' => 'OXFORD-RED-S',
                            'selection_qty' => 1,
                            'selection_can_change_qty' => false,
                            'position' => 4,
                            'is_default' => false,
                        ),
                        array(
                            'product_sku' => 'OXFORD-RED-M',
                            'selection_qty' => 1,
                            'selection_can_change_qty' => false,
                            'position' => 5,
                            'is_default' => false,
                        ),
                        array(
                            'product_sku' => 'OXFORD-RED-L',
                            'selection_qty' => 1,
                            'selection_can_change_qty' => false,
                            'position' => 6,
                            'is_default' => false,
                        ),
                    ),
                ),
                array(
                    'title' => array(
                        0 => 'Tie',
                        1 => 'Tie',
                        2 => 'Slips'
                    ),
                    'type' => 'checkbox',
                    'required' => false,
                    'position' => 2,
                    'selections' => array(
                        array(
                            'product_sku' => 'GEOMETRIC-RED',
                            'selection_qty' => 1,
                            'selection_can_change_qty' => true,
                            'position' => 1,
                            'is_default' => false,
                        ),
                        array(
                            'product_sku' => 'GEOMETRIC-PURPLE',
                            'selection_qty' => 1,
                            'selection_can_change_qty' => true,
                            'position' => 2,
                            'is_default' => true,
                        ),
                    ),
                ),
            ),
        ),
    ),
);

foreach ($productData as $data) {
    /** @var $product Vaimo_IntegrationBase_Model_Product */
    $product = Mage::getModel('integrationbase/product');
    $product->load($data['sku'], 'sku');
    $product->setRawData($data);
    $product->setSku($data['sku']);
    $product->setAttributeSetId($data['attribute_set_id']);
    $product->setTypeId($data['type_id']);
    $product->setParentSku($data['parent_sku']);
    $product->setConfigurableAttributes($data['configurable_attributes']);
    $product->setProductData($data['product_data']);
    if (isset($data['bundle_data'])) {
        $product->setBundleData($data['bundle_data']);
    }
    $product->setRowStatus(Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_IMPORT);
    $product->save();
    echo $product->getSku() . "\n";
}

echo "\n" . 'Stock Import' . "\n";

$stockData = array(
    array(
        'sku' => 'OXFORD',
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK,
    ),
    array(
        'sku' => 'OXFORD-BLUE-S',
        'qty' => 40,
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK,
    ),
    array(
        'sku' => 'OXFORD-BLUE-M',
        'qty' => 70,
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK,
    ),
    array(
        'sku' => 'OXFORD-BLUE-L',
        'qty' => 0,
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_OUT_OF_STOCK,
    ),
    array(
        'sku' => 'OXFORD-RED-S',
        'qty' => 40,
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK,
    ),
    array(
        'sku' => 'OXFORD-RED-M',
        'qty' => 0,
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_OUT_OF_STOCK,
    ),
    array(
        'sku' => 'OXFORD-RED-L',
        'qty' => 90,
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK,
    ),
    array(
        'sku' => 'GEOMETRIC',
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK,
    ),
    array(
        'sku' => 'GEOMETRIC-RED',
        'qty' => 90,
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK,
    ),
    array(
        'sku' => 'GEOMETRIC-PURPLE',
        'qty' => 6,
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK,
    ),
    array(
        'sku' => 'OUTFIT',
        'stock_status' => Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK,
    ),
);

foreach ($stockData as $data) {
    /** @var $stock Vaimo_IntegrationBase_Model_Stock */
    $stock = Mage::getModel('integrationbase/stock');
    $stock->load($data['sku'], 'sku');
    $stock->setRawData($data);
    $stock->setSku($data['sku']);
    $stock->setQty($data['qty']);
    $stock->setStockStatus($data['stock_status']);
    $stock->setRowStatus(Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_IMPORT);
    $stock->save();
    echo $stock->getSku() . ' ' . $stock->getQty() . "\n";
}

echo "\n" . 'Link Import' . "\n";

$linkData = array(
    array(
        'link_type_code' => 'up_sell',
        'product_sku' => 'OXFORD',
        'linked_product_sku' => 'GEOMETRIC'
    ),
);

foreach ($linkData as $data) {
    /** @var $link Vaimo_IntegrationBase_Model_Link */
    $link = Mage::getModel('integrationbase/link');
    $link->loadByTypeAndSkus($data['link_type_code'], $data['product_sku'], $data['linked_product_sku']);
    $link->setRawData($data);
    $link->setLinkTypeCode($data['link_type_code']);
    $link->setProductSku($data['product_sku']);
    $link->setLinkedProductSku($data['linked_product_sku']);
    $link->setRowStatus(Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_IMPORT);
    $link->save();
    echo $link->getProductSku() . ' <- ' . $link->getLinkedProductSku() . "\n";
}

echo "\n" . 'Attribute Import' . "\n";

$attributeData = array(
    array(
        'store_id' => 2,
        'entity_type' => 'catalog_product',
        'lookup_field' => 'sku',
        'lookup_value' => 'OXFORD',
        'attribute_code' => 'description',
        'attribute_value' => 'Genom vårt val av extra fina bomullsgarner och unika beredningsprocess har vi förbättrat såväl oxfordtygets lyster som prestanda. Den speciella vävtekniken skapar ett skjorttyg med elegant struktur och mjuka färger. Matcha med en välskuren kostymen för en sofistikerad businesstil.',
    ),
    array(
        'store_id' => 2,
        'entity_type' => 'catalog_product',
        'lookup_field' => 'sku',
        'lookup_value' => 'OXFORD',
        'attribute_code' => 'short_description',
        'attribute_value' => 'Classic oxford skjorta',
    ),
    array(
        'store_id' => 2,
        'entity_type' => 'catalog_product',
        'lookup_field' => 'sku',
        'lookup_value' => 'OXFORD',
        'attribute_code' => 'price',
        'attribute_value' => 220,
    ),
    array(
        'store_id' => 2,
        'entity_type' => 'catalog_product',
        'lookup_field' => 'sku',
        'lookup_value' => 'GEOMETRIC',
        'attribute_code' => 'description',
        'attribute_value' => 'Den lyxigt vävda sidenkvalitén ger denna slips en unik struktur med oklanderlig form. 100% siden, sydd i England.',
    ),
    array(
        'store_id' => 2,
        'entity_type' => 'catalog_product',
        'lookup_field' => 'sku',
        'lookup_value' => 'GEOMETRIC',
        'attribute_code' => 'price',
        'attribute_value' => 99,
    ),
);

foreach ($attributeData as $data) {
    /** @var $attribute Vaimo_IntegrationBase_Model_Attribute */
    $attribute = Mage::getModel('integrationbase/attribute');
    $attribute->loadByCode($data['entity_id'], $data['entity_type'], $data['attribute_code'], $data['store_id']);
    $attribute->setRawData($data);
    $attribute->addData($data);
    $attribute->setRowStatus(Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_IMPORT);
    $attribute->save();
    echo $attribute->getEntityId() . "\n";
}