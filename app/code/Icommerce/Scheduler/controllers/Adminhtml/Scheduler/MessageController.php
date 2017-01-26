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

namespace Icommerce\Scheduler\Block\Adminhtml\Scheduler;
class MessageController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('icommerce/scheduler/messages');
    }

    protected function _construct()
    {
        // Define module dependent translate
        parent::_construct();
        $this->setUsedModuleName('Icommerce_Scheduler');
    }

    protected function _initAction($title)
    {
        $this->_title($title)
            ->loadLayout()
            ->_setActiveMenu('icommerce/scheduler/message');

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction($this->__('Scheduler Messages'));
        $this->_addContent($this->getLayout()->createBlock('scheduler/adminhtml_message'));
        $this->renderLayout();
    }

    public function massDeleteAction()
    {
        $items = $this->getRequest()->getParam('scheduler');
        if (!is_array($items)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('scheduler')->__('Please select item(s)'));
        } else {
            try {
                foreach ($items as $item) {
                    $model = Mage::getModel('scheduler/message')->load($item);
                    $model->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('scheduler')->__('Total of %d item(s) were successfully deleted', count($items))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
}