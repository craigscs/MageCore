<?php

namespace Icommerce\Scheduler\Block\Adminhtml\Operation;
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    protected $om;
    protected $reg;
    public function __construct(\Magento\Framework\Registry $registry,
                                \Magento\Framework\ObjectManager\ObjectManager $om)
    {
        parent::__construct();

        $this->om = $om;
        $this->reg = $registry;
        $this->_objectId = 'id';
        $this->_blockGroup = 'scheduler';
        $this->_controller = 'adminhtml_operation';

        $auth = $this->om->get('Magento\Framework\AuthorizationInterface');

        if ($auth->isAllowed('icommerce/scheduler/operations/actions/save')) {
            $this->_updateButton('save', 'label', __('Save'));
        } else {
            $this->_removeButton('save');
            $this->_removeButton('reset');
        }


        if ($auth->isAllowed('icommerce/scheduler/operations/actions/delete')) {
            $this->_updateButton('delete', 'label', Mage::helper('scheduler')->__('Delete'));
        } else {
            $this->_removeButton('delete');
        }
    }

    public function getHeaderText()
    {
        if( $this->reg('operation_data') && $this->reg('operation_data')->getId() )
            return __("Edit Scheduler Task: '%s'", $this->escapeHtml($this->reg('operation_data')->getId()));
        else
            return __('Add Scheduler Task');
    }
}