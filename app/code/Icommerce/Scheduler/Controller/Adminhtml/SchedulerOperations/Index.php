<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 12:30 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;


class Index extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    public function execute()
    {
        $this->_initAction(__('Scheduler Tasks'));
        $this->getLayout()->getBlock('head')->addJs('vaimo/scheduler/operation.js');
        $this->_addContent($this->getLayout()->createBlock('Icommerce\Scheduler\Block\Adminhtml\Operation'));
        $this->renderLayout();
    }
}