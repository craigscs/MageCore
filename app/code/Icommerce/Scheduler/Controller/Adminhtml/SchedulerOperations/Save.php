<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 12:53 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;
class Save extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    protected $op;
    protected $messageManager;
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
                                \Magento\Framework\Translate\InlineInterface $translateInline, \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
                                \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, \Icommerce\Scheduler\Model\Operation $op)
    {
        $this->messageManager = $context->getMessageManager();
        $this->op = $op;
        parent::__construct($context, $coreRegistry, $fileFactory, $translateInline, $resultPageFactory, $resultJsonFactory, $resultLayoutFactory, $resultRawFactory);
    }

    public function execute()
    {
        if ($data = $this->getRequest()->getPost()) {
            /** @var $model Icommerce_Scheduler_Model_Operation */
            $model = $this->op;
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
            if ($password != \Icommerce\Scheduler\Helper\Data::PROTECTED_PASSWORD) {
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
                $this->messageManager->addSuccessMessage(__('Task was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }

                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                $this->getMessageManager()->addErrorMessage($e->getMessage());
//                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $this->getMessageManager()->addErrorMessage(__('Unable to find Task to save'));
        $this->_redirect('*/*/');
    }
}