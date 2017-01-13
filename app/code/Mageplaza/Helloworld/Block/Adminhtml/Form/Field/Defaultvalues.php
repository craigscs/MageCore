<?php
/**
 * Copyright (c) 2009-2012 Vaimo AB
 *
 * Vaimo reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Vaimo, except as provided by licence. A licence
 * under Vaimo's rights in the Program may be available directly from
 * Vaimo.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    Vaimo
 * @package     Vaimo_IntegrationUI
 * @copyright   Copyright (c) 2009-2012 Vaimo AB
 * @author      Urmo Schmidt
 */

namespace Mageplaza\Helloworld\Block\Adminhtml\Form\Field;

class Defaultvalues extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    protected $_dbFieldRenderer;
    protected $context;

    public function __construct(\Magento\Backend\Block\Template\Context $om)
    {
        $this->context = $om;
        return parent::__construct($om);
    }


    /**
     * Retrieve dbfield column renderer
     *
     * @return Vaimo_IntegrationUI_Block_Adminhtml_Form_Field_Dbfield
     */
    protected function _getDbFieldRenderer()
    {
        if (!$this->_dbFieldRenderer) {
            $this->_dbFieldRenderer = $this->getLayout()->createBlock(
                'Mageplaza\HelloWorld\Block\Adminhtml\Form\Field\Dbfield', '',
                array($this->context, 'is_render_to_js_template' => true)
            );
            //            $this->_dbFieldRenderer->setClass('customer_group_select');
            $this->_dbFieldRenderer->setExtraParams('style="width:300px"');
        }
        return $this->_dbFieldRenderer;
    }

    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
        $this->addColumn('db_field', array(
            'label' => __('Entity Attribute'),
            'renderer' => $this->_getDbFieldRenderer(),
        ));
        $this->addColumn('file_field', array(
            'label' => __('Value'),
            'style' => 'width:200px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Default Value');
    }

    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getDbFieldRenderer()->calcOptionHash($row->getData('db_field')),
            'selected="selected"'
        );
    }
}
