<?php

namespace Mageplaza\HelloWorld\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    protected $storeManager;
    protected $objectManager;

    const XML_PATH_HELLOWORLD = 'helloworld/';



    public function __construct(Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager
    ) {
        $this->objectManager = $objectManager;
        $this->storeManager  = $storeManager;
        parent::__construct($context);
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function makeStorableArrayFieldValue($value,$update = false,$prefix = false)
    {
        if ($this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_decodeArrayFieldValue($value,$update,$prefix);
        }
        $value = $this->_serializeValue($value);
        return $value;
    }

    protected function _isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (!is_array($row) || !array_key_exists('db_field', $row) || !array_key_exists('file_field', $row)) {
                return false;
            }
        }
        return true;
    }

    protected function _serializeValue($value)
    {
        if (is_numeric($value)) {
            $data = (float)$value;
            return (string)$data;
        } else if (is_array($value)) {
            $data = array();
            foreach ($value as $groupId => $qty) {
                if (!array_key_exists($groupId, $data)) {
                    $data[$groupId] = $qty;
                }
            }
            if (count($data) == 1 && array_key_exists(\Magento\Customer\Model\Group::CUST_GROUP_ALL, $data)) {
                return (string)$data[\Magento\Customer\Model\Group::CUST_GROUP_ALL];
            }
            return serialize($data);
        } else {
            return '';
        }
    }

    protected function _decodeArrayFieldValue(array $value,$update,$prefix)
    {
        $result = array();
        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (!is_array($row) || !array_key_exists('db_field', $row) || !array_key_exists('file_field', $row)) {
                continue;
            }
            $groupId = $row['db_field'];
            $qty = $row['file_field'];
            if ($update){
                $qty = $row['new'];
            }
            if ($prefix){
                $qty = $row['prefix'];
            }
            $result[$groupId] = $qty;
        }
        return $result;
    }


    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_HELLOWORLD . $code, $storeId);
    }


}