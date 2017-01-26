<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 3:24 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;


class Refresh extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
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
        $ids = $this->getRequest()->getParam('ids');
        $statuses = $this->help->getOperationStatusesOptionArray();

        /** @var Icommerce_Scheduler_Model_Resource_Operation_Collection $collection */
        $collection = $this->op->getCollection();
        $collection->addFieldToSelect(array('status', 'run_asap', 'next_run', 'last_run', 'last_status', 'progress_min', 'progress_max', 'progress_pos'));
        $collection->addFieldToFilter('id', array('in' => explode(',', $ids)));
        $result = array();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
        foreach ($collection as $operation) {
            $result['operations'][$operation->getId()] = array(
                'status' => $this->help->getStatusHtml($operation),
                'next_run' => $operation->getRunAsap() ? 'ASAP' : ($operation->getNextRun() != '0000-00-00 00:00:00' ? $objDate->formatDate($operation->getNextRun(),
                    Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true) : ''),
                'last_run' => $operation->getLastRun() != '0000-00-00 00:00:00' ? $objDate->formatDate($operation->getLastRun(), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true) : '',
                'last_status' => $this->help->getLastStatusHtml($operation->getLastStatus()),
            );
        }
        $this->getResponse()->setBody(json_encode($result));
    }
}