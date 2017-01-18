<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/18/2017
 * Time: 11:51 AM
 */

namespace Mageplaza\HelloWorld\Model;
class Import
{
    protected $_successCount = 0;
    protected $_failCount = 0;
    protected $_resultStatus;
    protected $_resultMessage;
    protected $_verbose = false;
    protected $_write;
    protected $_read;
    protected $_fieldMapping;
    protected $_defaultValues;
    protected $_prefix;

    public function __construct()
    {
//        $this->_write = Mage::getSingleton('core/resource')->getConnection('core_write');
//        $this->_read = Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    public function __destruct()
    {

    }

    protected function _loadConfig($profile)
    {
        $temp = unserialize($profile->getFieldMapping());
        foreach ($temp as $key => $value) {
            $this->_fieldMapping[substr($key, strpos($key, '.') + 1)] = $value;
        }

        $temp = unserialize($profile->getDefaultValues());
        foreach ($temp as $key => $value) {
            $this->_defaultValues[substr($key, strpos($key, '.') + 1)] = $value;
        }

        $temp = unserialize($profile->getPrefix());
        foreach ($temp as $key => $value) {
            if ($value != 'null') {
                $this->_prefix[substr($key, strpos($key, '.') + 1)] = substr($value, strpos($value, '.') + 1);
            }
        }

    }

    protected function _setResultStatus()
    {
        if ($this->_failCount == 0 && $this->_successCount > 0) {
            $this->_resultStatus = \Icommerce_Utils::TRIGGER_STATUS_SUCCEEDED;
        } else if ($this->_failCount > 0 && $this->_successCount == 0) {
            $this->_resultStatus = \Icommerce_Utils::TRIGGER_STATUS_FAILED;
        } else if ($this->_failCount > 0 && $this->_successCount > 0) {
            $this->_resultStatus = \Icommerce_Utils::TRIGGER_STATUS_EXCEPTIONS;
        } else {
            $this->_resultStatus = \Icommerce_Utils::TRIGGER_STATUS_NONE;
        }
    }

    public function setVerbosity($verbosity)
    {
        $this->_verbose = $verbosity;
    }

    function getRead()
    {
        if( !$this->_read )
            $this->_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        return $this->_read;
    }

    protected function _logText($text)
    {
        //if ($this->_verbose) {
//        Mage::log($text, null, "manny.log");
        //}
    }

    protected function _unmapNew($profile,$data)
    {
        $fieldMapping = array();
        $tempData = array();
        $fields = array();
        $temp = unserialize($profile->getUpdateMapping());
        foreach ($temp as $key => $value) {
            $fields[substr($key, strpos($key, '.') + 1)] = $value;
        }
        if ($fields) {
            foreach ($fields as $key => $value) {
                if ($value == '1') {
                    unset($data[$key]);
                }
            }
        }
        return $data;
    }

    public function getResult()
    {
        return array(
            'status' => $this->_resultStatus,
            'message' => $this->_resultMessage,
        );
    }

    public function dispatch_event($e, $d)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\Event\ManagerInterface $manager */
        $manager = $om->get('Magento\Framework\Event\ManagerInterface');
        $manager->dispatch($e, $d);
    }

    public function import(\Magento\Framework\Event\Observer $observer)
    {
        Mage::log("Starting product import.", null, 'VaimoProductImport.log');
        ini_set('memory_limit', '-1');
        $profile = $observer->getProfile();
        $this->_loadConfig($profile);
        $approach = $profile->getApproach();
        $type = substr($profile->getProcess(),0,-7);
        $method = 'import' . $type;

        try {
            switch ($approach) {
                case 'file':
                    $fileInfo = unserialize($profile->getFileInfo());

                    foreach (glob($fileInfo['pattern']) as $filename) {
                        $csvReader = new Icommerce_CsvReader($filename, null, $fileInfo['delimiter'], $fileInfo['enclosure']);
                        if (!$csvReader->valid()) {
                            $this->_logText(__('File failed to open for reading to import.'));
                            throw new \Exception("Failed opening " . $filename . " file");
                        }
                        $this->dispatch_event('integrationui_import_before_file_import', array('type' => $type));
                        foreach ($csvReader as $data) {
                            try {
                                $this->dispatch_event('integrationui_' . $type . '_import_after_file_import', array('profile' => $profile, 'default' => $this->_defaultValues, 'mapping' => $this->_fieldMapping, 'response' => &$data));
                                $this->$method($data);
                            } catch (\Exception $e) {
                                $this->_logText(__json_encode($e));
                                $this->_logText(__('Product import failed: %s', $e->getMessage()));
                                $this->_failCount++;
                            }
                        }
                        $this->dispatch_event('integrationui_import_after_file_import', array('type' => $type));
                        $donepath = $fileInfo['done'];
                        if (!file_exists($donepath)) mkdir($donepath, 0777, true);
                        rename($filename, $donepath . basename($filename));
                        break;
                    }
                    break;
                case 'curl':

                    $curlInfo = unserialize($profile->getCurlInfo());
                    $ch = curl_init();

                    foreach ($curlInfo['general'] as $key => $value) {
                        if ($value) {
                            switch (substr($value['db_field'], strpos($value['db_field'], '.') + 1)) {
                                case 'CURLOPT_RETURNTRANSFER':
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, $value['file_field']);
                                    break;
                                case 'CURLOPT_URL':
                                    curl_setopt($ch, CURLOPT_URL, $value['file_field']);
                                    break;
                                case 'CURLOPT_SSL_VERIFYPEER':
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $value['file_field']);
                                    break;
                                case 'CURLOPT_SSL_VERIFYHOST':
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $value['file_field']);
                                    break;
                                case 'CURLOPT_POST':
                                    curl_setopt($ch, CURLOPT_POST, $value['file_field']);
                                    break;
                                case 'CURLOPT_POSTFIELDS':
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $value['file_field']);
                                    break;
                                // *** More values can be added in Block/Adminhtml/Form/Field/Curlfield.php - POSTFILDS
                            }
                        }
                    }

                    $header = false;
                    foreach ($curlInfo['header'] as $key => $value) {
                        if ($value) {
                            if ($header) {
                                $header .= ', ';
                            }
                            $header .= $value['db_field'] . ': ' . $value['file_field'];
                        }
                    }

                    if ($header) {
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
                    }

                    $this->dispatch_event('integrationui_' . $type . '_import_before_curl_request', array('profile' => $profile, 'curl' => &$ch));

                    $response = curl_exec($ch);
                    curl_close($ch);

                    $this->dispatch_event('integrationui_' . $type . '_import_after_curl_request', array('profile' => $profile, 'default' => $this->_defaultValues, 'mapping' => $this->_fieldMapping, 'response' => &$response));

                    foreach ($response as $data) {
                        $this->$method($data);
                    }
                    break;
                case 'soap':
                    $soapInfo = unserialize($profile->getSoapInfo());
                    $client = new SoapClient($soapInfo['url']);
                    $response = $client->__soapCall($soapInfo['method'],array());
                    Mage::dispatchEvent('integrationui_' . $type . '_import_after_soap_request', array('profile' => $profile, 'default' => $this->_defaultValues, 'mapping' => $this->_fieldMapping, 'response' => &$response));
                    $this->$method($response);
                    break;
            }
            $this->_setResultStatus();
            $this->_resultMessage = Mage::helper('integrationui')->__('%d records imported', $this->_successCount);
        } catch (Exception $e) {
            $this->_resultStatus = \Icommerce_Utils::TRIGGER_STATUS_FAILED;
            $this->_resultMessage = $e->getMessage();
        }
    }

    public function importproduct($productData)
    {
        if (isset($productData['sku'])) {
            $productId = Mage::getModel('catalog/product')->getIdBySku($productData['sku']);
            if ($productId) {
                $product = Mage::getModel('catalog/product')->load($productId);
                $product->setData(array_merge($product->getData(), $productData));
                $product->save();
                echo $product->getSku() . "\n";
                $this->_successCount++;
            }

        } else {
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
                if ($product->getData('raw_data') != $product->getOrigData('raw_data')) {
                    $product->setRowStatus(Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_IMPORT);
                }
                $product->save();
                echo $product->getSku() . "\n";
                $this->_successCount++;
            }
        }
    }

    public function importprice($priceData)
    {
        if ($priceData == 1) {
            $this->_successCount++;
        } else {
            foreach ($priceData as $data) {
                /** @var $price Vaimo_IntegrationBase_Model_Price */
                $price = Mage::getModel('integrationbase/price');
                //$price->loadBySkuAndStore($data['sku'], $data['store_id']);
                $price->load($data['sku'], 'sku');
                $price->setRawData($data);
                $price->setStoreId($data['store_id']);
                $price->setSku($data['sku']);
                $price->setPrice($data['price']);
                if ($data['special_price']) {
                    $price->setSpecialPrice($data['special_price']);
                }
                $price->setTaxClassId($data['tax_class_id']);
                if ($price->getData('raw_data') != $price->getOrigData('raw_data')) {
                    $price->setRowStatus(Vaimo_IntegrationBase_Helper_Data::ROW_STATUS_IMPORT);
                }
                $price->save();
                echo $price->getSku() . ' ' . $price->getPrice() . "\n";
                $this->_successCount++;
            }
        }
    }

    public function importstock($stockData)
    {
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
            $this->_successCount++;
        }
    }

    // Added method for order status UTI import
    public function importorderstatus($data)
    {
        $orderStatus = Mage::getModel('ordertracking/orderstatus');
        $orderStatus->setData($data);
        $orderStatus->save();
        $this->_successCount++;
    }

}