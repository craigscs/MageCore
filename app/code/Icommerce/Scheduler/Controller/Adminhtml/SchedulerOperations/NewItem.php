<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 12:49 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;


class NewItem extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\Framework\App\Response\Http\FileFactory $fileFactory, \Magento\Framework\Translate\InlineInterface $translateInline, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory, \Magento\Framework\Controller\Result\RawFactory $resultRawFactory)
    {
        parent::__construct($context, $coreRegistry, $fileFactory, $translateInline, $resultPageFactory, $resultJsonFactory, $resultLayoutFactory, $resultRawFactory);
    }

    public function execute()
    {
        $this->_forward('edit');
    }
}