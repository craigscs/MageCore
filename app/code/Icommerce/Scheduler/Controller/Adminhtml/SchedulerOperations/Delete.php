<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 2:11 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;
class Delete extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    protected $op;
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
                                \Magento\Framework\Translate\InlineInterface $translateInline, \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
                                \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, \Icommerce\Scheduler\Model\Operation $op)
    {
        $this->op = $op;
        parent::__construct($context, $coreRegistry, $fileFactory, $translateInline, $resultPageFactory, $resultJsonFactory, $resultLayoutFactory, $resultRawFactory);
    }

    public function execute()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = $this->op;

                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();

                $this->getMessageManager()->addSuccessMessage(__('Item was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (\Exception $e) {
                $this->getMessageManager()->addErrorMessage($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }
}