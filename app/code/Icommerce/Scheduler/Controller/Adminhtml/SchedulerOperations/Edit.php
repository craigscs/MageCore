<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 12:35 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;


class Edit extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    protected $auth;
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
                                \Magento\Framework\Translate\InlineInterface $translateInline, \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
                                \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, \Magento\Backend\Model\Auth\Session $authSession)
    {
        $this->auth = $authSession;
        parent::__construct($context, $coreRegistry, $fileFactory, $translateInline, $resultPageFactory, $resultJsonFactory, $resultLayoutFactory, $resultRawFactory);
    }

    public function execute()
    {
        $this->_initAction($this->__('Scheduler Task'));
        $this->getLayout()->getBlock('head')->addItem('js', 'prototype/window.js');
        $this->getLayout()->getBlock('head')->addItem('js_css', 'prototype/windows/themes/default.css');
        $this->getLayout()->getBlock('head')->addCss('lib/prototype/windows/themes/magento.css');
        $this->_initOperation();

        $id = $this->getRequest()->getParam('id');
        $operation = $this->_coreRegistry('operation_data');

        if ($operation->getId() || $id == 0) {
            $data = $this->auth->getFormData(true);

            if (!empty($data)) {
                $operation->setData($data);
            }

            $this->_coreRegistry->unregister('operation_data');
            $this->_coreRegistry->register('operation_data', $operation);

            $this->_addBreadcrumb(__('Module manager'), __('Module manager'));
            $this->_addBreadcrumb(__('Module edit'), __('Module edit'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('\Icommerce\Scheduler\Adminhtml\Operation\Edit'))
                ->_addLeft($this->getLayout()->createBlock('\Icommerce\Scheduler\Adminhtml\Opeartion\Edit\Tabs'));
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(__('Task does not exist'));
            $this->_redirect('*/*/index');
        }
    }
}