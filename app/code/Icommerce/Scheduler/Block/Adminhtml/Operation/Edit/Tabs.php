<?php

namespace Icommerce\Scheduler\Block\Adminhtml\Operation\Edit;
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('operationTabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Scheduler Task'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('details', array(
            'label'     => __('Details'),
            'title'     => __('Details'),
            'content'   => $this->getLayout()->createBlock('scheduler/adminhtml_operation_edit_tab_details')->toHtml(),
        ));

        if (Mage::registry('operation_data')->getId()) {
            $this->addTab('history', array(
                'label' => __('History'),
                'class' => 'ajax',
                'url' => $this->getUrl('*/*/history', array('_current' => true)),
            ));
        }

        if (isset($_SESSION['admin']['active_tab_id'])) {
            $this->setActiveTab($_SESSION['admin']['active_tab_id']);
        }

        return parent::_beforeToHtml();
    }
}