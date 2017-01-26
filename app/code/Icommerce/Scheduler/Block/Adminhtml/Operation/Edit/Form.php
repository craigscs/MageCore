<?php

namespace Icommerce\Scheduler\Block\Adminhtml\Operation\Edit;
class Form extends \Magento\Backend\Block\Widget\Form
{
    protected function _construct()
    {
        parent::_construct();
    }

    protected function _prepareForm()
    {
        $form = new \Magento\Framework\Data\Form(array(
        	'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post',
        ));

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}