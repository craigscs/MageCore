<?php

namespace Icommerce\Scheduler\Block\Adminhtml\History\Edit;
class Form extends \Magento\Backend\Block\Widget\Form
{
    protected $help;
    public function __construct(\Magento\Backend\Block\Template\Context $context,
                                \Icommerce\Scheduler\Helper\Data $help,
                                array $data)
    {
        $this->help = $help;
        parent::__construct($context, $data);
    }

    protected function _prepareForm()
    {
        $form = new \Magento\Framework\Data\Form();

        $form->addField('created_at', 'label', array(
            'label'     => __('Started'),
            'name'      => 'created_at',
        ));

        $form->addField('finished_at', 'label', array(
            'label'     => __('Finished'),
            'name'      => 'finished_at',
        ));

        $form->addField('status', 'select', array(
            'label'     => __('Status'),
            'name'      => 'status',
            'values'    => $this->help->getHistoryStatusesOptionArray(true),
        ));

        $form->addField('message', 'label', array(
            'label'     => __('Message'),
            'name'      => 'message',
        ));

        $form->addField('result', 'textarea', array(
            'label'     => '', __('Result'),
            'name'      => 'result',
            'required'  => false,
            'style'     => 'width: 100%; height: 400px;',
        ));

        $this->setForm($form);
//        $form->setValues(Mage::registry('current_scheduler_history')->getData());

        return parent::_prepareForm();
    }

}