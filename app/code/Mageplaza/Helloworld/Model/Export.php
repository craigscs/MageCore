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

class Export
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
        $this->_fieldMapping = unserialize($profile->getFieldMapping());
        $this->_fieldMapping = array_flip($this->_fieldMapping);
        foreach ($this->_fieldMapping as $key => $value) {
            $this->_fieldMapping[$key] = substr($value, strpos($value, '.') + 1);
        }
        $this->_fieldMapping = array_flip($this->_fieldMapping);

        $this->_defaultValues = unserialize($profile->getDefaultValues());
        $this->_defaultValues = array_flip($this->_defaultValues);
        foreach ($this->_defaultValues as $key => $value) {
            $this->_defaultValues[$key] = substr($value, strpos($value, '.') + 1);
        }
        $this->_defaultValues = array_flip($this->_defaultValues);

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
        if ($this->_verbose) {
            echo $text . "\n";
        }
    }

    public function getStatuses()
    {
        $result = explode(',',Mage::getStoreConfig('integrationui/settings/order_status'));

        return $result;
    }

    public function dispatch_event($e, $d)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\Event\ManagerInterface $manager */
        $manager = $om->get('Magento\Framework\Event\ManagerInterface');
        $manager->dispatch($e, $d);
    }

    public function export(\Magento\Framework\Event\Observer $observer)
    {
        $profile = $observer->getProfile();
        $this->_loadConfig($profile);
        $approach = $profile->getApproach();
        $type = substr($profile->getProcess(),0,-7);
        $method = 'export' . $type;

        $orders = Icommerce_Db::getRead()->query("SELECT * FROM integrationui_order_status WHERE integration_status = ?", $profile->getStatus());
        $statuses = $this->getStatuses();

        if ($orders->rowCount() > 0) {
            foreach ($orders as $order) {
                $ord = Mage::getModel('sales/order')->load($order['order_id']);
                if (!in_array($ord->getStatus(), $statuses)) {
                    continue;
                }

                try {
                    switch ($approach) {
                        case 'file':
                            $this->dispatch_event('integrationui_order_export_before_file', array('profile' => $profile, 'order_id' => $order['order_id']));

                            $this->dispatch_event('integrationui_order_export_after_file', array('profile' => $profile, 'order_id' => $order['order_id']));

                            $this->_successCount++;
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
                                        // *** More values can be added in Block/Adminhtml/Form/Field/Curlfield.php - POSTFIELDS
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
                            $this->dispatch_event('integrationui_order_export_before_curl_request', array('profile' => $profile, 'curl' => &$ch, 'order_id' => $order['order_id']));

                            //$response = curl_exec($ch);
                            //curl_close($ch);

                            $this->dispatch_event('integrationui_order_export_after_curl_request', array('profile' => $profile, 'response' => &$response, 'order_id' => $order['order_id']));

                            $this->_successCount++;
                            break;
                        case 'soap':
                            $soapInfo = unserialize($profile->getSoapInfo());
                            $client = new SoapClient($soapInfo['url']);
                            //$client->__setLocation('http://webservices.trp.uatlab.mychain.co.za:8899/argility.dolfininterface.webservices.trp/dolfinliveinterface.asmx');

                            $params = array();
                            if (isset($soapInfo['parameters']))
                            {
                                foreach ($soapInfo['parameters'] as $param) {
                                    if (isset($param['db_field'])) {
                                        $params[$param['db_field']] = $param['file_field'];
                                    }
                                }
                            }
                            $this->dispatch_event('integrationui_' . $type . '_export_before_soap_request', array('profile' => $profile, 'params' => &$params, 'order_id' => $order['order_id']));
                            //$response = $client->ProcessTransaction(array($params));
                            $response = $client->__soapCall($soapInfo['method'],array($params));
                            if (!$response) {
                                $this->_successCount++;
                            } else {
                                $this->_failCount++;
                            }
                            $this->dispatch_event('integrationui_' . $type . '_export_after_soap_request', array('profile' => $profile, 'params' => &$params, 'order_id' => $order['order_id'], 'response' => &$response));
                            //$this->$method($response);
                            break;
                    }
                    Mage::helper('integrationui')->setIntegrationOrderStatus($order['order_id'], $profile->getStatusAfter());
                    $this->_setResultStatus();
                    $this->_resultMessage = Mage::helper('integrationui')->__('%d orders exported', $this->_successCount);
                } catch (\Exception $e) {
                    $this->_resultStatus = \Icommerce_Utils::TRIGGER_STATUS_FAILED;
                    $this->_resultMessage = $e->getMessage();
                }
            }
        }
    }

    public function getResult()
    {
        return array(
            'status' => $this->_resultStatus,
            'message' => $this->_resultMessage,
        );
    }

}