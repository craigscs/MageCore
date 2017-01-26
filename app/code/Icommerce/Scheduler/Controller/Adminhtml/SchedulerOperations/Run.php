<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 2:37 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;

class Run extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    protected $help;
    protected $op;
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
            if ($this->help->isSchedulerDisabled()) {
                throw new \Exception(__('Scheduler disabled right now, please try later'));
            }

            $id = $this->getRequest()->getParam('id');
            $operation = $this->op->load($id);
            $result = $this->help->runOperation($operation);

            $message = $this->__('Task run complete');
            if (isset($result['status']) && $result['status']) {
                $statuses = $this->help->getHistoryStatusesOptionArray();
                if (isset($statuses[$result['status']])) {
                    $message .= '. ' . __('Status: %s', $statuses[$result['status']]);
                }
            }
            if (isset($result['message']) && $result['message']) {
                $message .= '<br>' . $result['message'];
            }

            $this->getMessageManager()->addSuccessMessage()$message);
        } catch (Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
        }

        $this->_redirect('*/*/index');
    }
}