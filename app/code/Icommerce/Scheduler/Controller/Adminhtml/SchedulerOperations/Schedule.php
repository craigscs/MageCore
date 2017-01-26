<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 3:14 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;


class Schedule extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    protected $op;
    protected $help;
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
                                \Magento\Framework\Translate\InlineInterface $translateInline, \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
                                \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, \Icommerce\Scheduler\Helper\Data $help,
                                \Icommerce\Scheduler\Model\Operation $op)
    {
        $this->op = $op;
        $this->help = $help;
        parent::__construct($context, $coreRegistry, $fileFactory, $translateInline, $resultPageFactory, $resultJsonFactory, $resultLayoutFactory, $resultRawFactory);
    }
    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $operation = $this->op->load($id);
            $operation->setRunAsap(1);
            $operation->save();

            $this->getMessageManager()->addSuccessMessage('Task scheduled to run as soon as possible');
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
        }

        $this->_redirect('*/*/index');
    }
}