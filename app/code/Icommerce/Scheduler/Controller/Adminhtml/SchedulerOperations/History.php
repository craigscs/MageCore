<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 2:30 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;

class History extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    public function execute()
    {
        $this->_initOperation();
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('Icommerce/Scheduler/Block/Adminhtml/Operation/Edit/Tab/History', 'root'));
        $this->renderLayout();
    }
}