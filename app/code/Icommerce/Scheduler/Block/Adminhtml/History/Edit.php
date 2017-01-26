<?php

namespace Icommerce\Scheduler\Block\Adminhtml\History;
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    private $_history;
    protected $reg;
    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->reg = $registry;
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'scheduler';
        $this->_controller = 'adminhtml_history';

        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('save');
    }

    protected function getHistory()
    {
        if (!$this->_history) {
            $this->_history = $this->reg->registry('current_scheduler_history');
        }

        return $this->_history;
    }

    public function getHeaderText()
    {
        return __('History') . ' | ' . $this->formatDate($this->getHistory()->getCreatedAt(), 'medium', true);
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*/edit', array('id' => $this->getHistory()->getOperationId()));
    }

}