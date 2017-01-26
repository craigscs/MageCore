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

class Icommerce_Scheduler_Adminhtml_Scheduler_OperationController extends Mage_Adminhtml_Controller_Action
{
    protected function _construct()
    {
        // Define module dependent translate
        parent::_construct();
        $this->setUsedModuleName('Icommerce_Scheduler');
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'new' :
                return Mage::getSingleton('admin/session')->isAllowed('icommerce/scheduler/operations/actions/new');
                break;
            case 'edit' :
                return Mage::getSingleton('admin/session')->isAllowed('icommerce/scheduler/operations/actions/edit');
                break;
            case 'save' :
                return Mage::getSingleton('admin/session')->isAllowed('icommerce/scheduler/operations/actions/save');
                break;
            case 'delete':
                return Mage::getSingleton('admin/session')->isAllowed('icommerce/scheduler/operations/actions/delete');
                break;
            case 'run':
                return Mage::getSingleton('admin/session')->isAllowed('icommerce/scheduler/operations/actions/run');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('icommerce/scheduler/operations');
                break;
        }
    }

    protected function _initOperation()
    {
        $operationId = (int) $this->getRequest()->getParam('id');
        $operation = Mage::getModel('scheduler/operation');

        if ($operationId) {
            $operation->load($operationId);
            if ($operation->getPassword()) {
                $operation->setPassword(Icommerce_Scheduler_Helper_Data::PROTECTED_PASSWORD);
            }
        }

        Mage::register('operation_data', $operation);
    }

    protected function _initAction($title)
    {
        try {
            $this->_title($title)
                ->loadLayout()
                ->_setActiveMenu('icommerce/scheduler');
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/dashboard');
        }

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction($this->__('Scheduler Tasks'));
        $this->getLayout()->getBlock('head')->addJs('vaimo/scheduler/operation.js');
        $this->_addContent($this->getLayout()->createBlock('scheduler/adminhtml_operation'));
        $this->renderLayout();
    }

    public function editAction()
    {
        $this->_initAction($this->__('Scheduler Task'));
        $this->getLayout()->getBlock('head')->addItem('js', 'prototype/window.js');
        $this->getLayout()->getBlock('head')->addItem('js_css', 'prototype/windows/themes/default.css');
        $this->getLayout()->getBlock('head')->addCss('lib/prototype/windows/themes/magento.css');
        $this->_initOperation();

        $id = $this->getRequest()->getParam('id');
        $operation = Mage::registry('operation_data');

        if ($operation->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);

            if (!empty($data)) {
                $operation->setData($data);
            }

            Mage::unregister('operation_data');
            Mage::register('operation_data', $operation);

            $this->_addBreadcrumb(Mage::helper('scheduler')->__('Module manager'), Mage::helper('scheduler')->__('Module manager'));
            $this->_addBreadcrumb(Mage::helper('scheduler')->__('Module edit'), Mage::helper('scheduler')->__('Module edit'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('scheduler/adminhtml_operation_edit'))
                    ->_addLeft($this->getLayout()->createBlock('scheduler/adminhtml_operation_edit_tabs'));
            $this->renderLayout();
        } else {
           Mage::getSingleton('adminhtml/session')->addError(Mage::helper('scheduler')->__('Task does not exist'));
           $this->_redirect('*/*/index');
        }
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            /** @var $model Icommerce_Scheduler_Model_Operation */
            $model = Mage::getModel('scheduler/operation');

            $model->setCode($this->getRequest()->getParam('code'));
            $model->setName($this->getRequest()->getParam('name'));
            $model->setComment($this->getRequest()->getParam('comment'));
            $model->setStatus($this->getRequest()->getParam('status'));
            $model->setMasterId($this->getRequest()->getParam('master_id'));
            $model->setMasterOrder($this->getRequest()->getParam('master_order'));
            $model->setUrlOverride($this->getRequest()->getParam('url_override'));
            $model->setAuthenticationType($this->getRequest()->getParam('authentication_type'));
            $model->setUsername($this->getRequest()->getParam('username'));

            $password = $this->getRequest()->getParam('password');
            if ($password != Icommerce_Scheduler_Helper_Data::PROTECTED_PASSWORD) {
                $model->setPassword($this->getRequest()->getParam('password'));
            }

            if ($saveHistory = $this->getRequest()->getParam('save_history')) {
                $model->setSaveHistory(implode(',', $saveHistory));
            } else {
                $model->setSaveHistory('');
            }

            $model->setRerun($this->getRequest()->getParam('rerun'));
            $model->setRerunCount($this->getRequest()->getParam('rerun_count'));

            $model->setEmailEnabled($this->getRequest()->getParam('email_enabled'));
            if ($emailStatus = $this->getRequest()->getParam('email_status')) {
                $model->setEmailStatus(implode(',', $emailStatus));
            } else {
                $model->setEmailStatus('');
            }
            $model->setEmailTemplate($this->getRequest()->getParam('email_template'));
            $model->setEmailSender($this->getRequest()->getParam('email_sender'));
            $model->setEmailReceiver($this->getRequest()->getParam('email_receiver'));

            $model->setRecurrenceInfo($this->getRequest()->getParam('recurrence_info'));

            $parameters = $this->getRequest()->getParam($this->getRequest()->getParam('code'));
            $model->setParameters($parameters);

            if ($this->getRequest()->getParam('id') != '') {
                $model->setId($this->getRequest()->getParam('id'));
            }

            try {
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('scheduler')->__('Task was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }

                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('scheduler')->__('Unable to find Task to save'));
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('scheduler/operation');

                $model->setId($this->getRequest()->getParam('id'))
                       ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('scheduler')->__('Item was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction()
    {
        $items = $this->getRequest()->getParam('scheduler');
        if (!is_array($items)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('scheduler')->__('Please select item(s)'));
        } else {
            try {
                foreach ($items as $item) {
                    $model = Mage::getModel('scheduler/operation')->load($item);
                    $model->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('scheduler')->__(
                        'Total of %d item(s) were successfully deleted', count($items)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function historyAction()
    {
        $this->_initOperation();
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('scheduler/adminhtml_operation_edit_tab_history', 'root'));
        $this->renderLayout();
    }

    public function historyViewAction()
    {
        $id = $this->getRequest()->getParam('id');
        $history = Mage::getModel('scheduler/history');
        $history->load($id);
        $result = '<pre style="width: 100%; height: 99%; overflow-y: scroll;">' . $history->getResult() . '</pre>';
        $this->getResponse()->setBody($result);
    }

    public function runAction()
    {
        try {
            if (Mage::helper('scheduler')->isSchedulerDisabled()) {
                Mage::throwException($this->__('Scheduler disabled right now, please try later'));
            }

            $id = $this->getRequest()->getParam('id');
            $operation = Mage::getModel('scheduler/operation')->load($id);
            $result = Mage::helper('scheduler')->runOperation($operation);

            $message = $this->__('Task run complete');
            if (isset($result['status']) && $result['status']) {
                $statuses = Mage::helper('scheduler')->getHistoryStatusesOptionArray();
                if (isset($statuses[$result['status']])) {
                    $message .= '. ' . $this->__('Status: %s', $statuses[$result['status']]);
                }
            }
            if (isset($result['message']) && $result['message']) {
                $message .= '<br>' . $result['message'];
            }

            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/index');
    }

    public function scheduleAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $operation = Mage::getModel('scheduler/operation')->load($id);
            $operation->setRunAsap(1);
            $operation->save();

            Mage::getSingleton('adminhtml/session')->addSuccess('Task scheduled to run as soon as possible');
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/index');
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('scheduler/adminhtml_operation_grid')->toHtml()
        );
    }

    public function refreshAction()
    {
        $ids = $this->getRequest()->getParam('ids');
        $statuses = Mage::helper('scheduler')->getOperationStatusesOptionArray();

        /** @var Icommerce_Scheduler_Model_Resource_Operation_Collection $collection */
        $collection = Mage::getModel('scheduler/operation')->getCollection();
        $collection->addFieldToSelect(array('status', 'run_asap', 'next_run', 'last_run', 'last_status', 'progress_min', 'progress_max', 'progress_pos'));
        $collection->addFieldToFilter('id', array('in' => explode(',', $ids)));
        $result = array();
        foreach ($collection as $operation) {
            $result['operations'][$operation->getId()] = array(
                'status' => Mage::helper('scheduler')->getStatusHtml($operation),
                'next_run' => $operation->getRunAsap() ? 'ASAP' : ($operation->getNextRun() != '0000-00-00 00:00:00' ? Mage::helper('core')->formatDate($operation->getNextRun(), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true) : ''),
                'last_run' => $operation->getLastRun() != '0000-00-00 00:00:00' ? Mage::helper('core')->formatDate($operation->getLastRun(), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true) : '',
                'last_status' => Mage::helper('scheduler')->getLastStatusHtml($operation->getLastStatus()),
            );
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}