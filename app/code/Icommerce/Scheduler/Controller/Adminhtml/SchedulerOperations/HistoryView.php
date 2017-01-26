<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 2:22 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;


class HistoryView extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    protected $his;
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
                                \Magento\Framework\Translate\InlineInterface $translateInline, \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
                                \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, \Icommerce\Scheduler\Model\History $his)
    {
        $this->his = $his;
        parent::__construct($context, $coreRegistry, $fileFactory, $translateInline, $resultPageFactory, $resultJsonFactory, $resultLayoutFactory, $resultRawFactory);
    }
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $history = $this->his;
        $history->load($id);
        $result = '<pre style="width: 100%; height: 99%; overflow-y: scroll;">' . $history->getResult() . '</pre>';
        $this->getResponse()->setBody($result);
    }
}