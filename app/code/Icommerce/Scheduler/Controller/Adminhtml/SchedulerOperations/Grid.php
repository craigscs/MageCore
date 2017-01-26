<?php
/**
 * Created by PhpStorm.
 * User: Craig
 * Date: 1/24/2017
 * Time: 3:21 PM
 */

namespace Icommerce\Scheduler\Controller\Adminhtml;


class Grid extends \Icommerce\Scheduler\Controller\Adminhtml\SchedulerOperations
{
    public function execute()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('Icommerce\Scheduler\Block\Adminhtml\Operation\Grid')->toHtml()
        );
    }
}