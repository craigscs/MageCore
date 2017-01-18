<?php
/**
 * Mageplaza_HelloWorld extension
 *                     NOTICE OF LICENSE
 * 
 *                     This source file is subject to the Mageplaza License
 *                     that is bundled with this package in the file LICENSE.txt.
 *                     It is also available through the world-wide-web at this URL:
 *                     https://www.mageplaza.com/LICENSE.txt
 * 
 *                     @category  Mageplaza
 *                     @package   Mageplaza_HelloWorld
 *                     @copyright Copyright (c) 2016
 *                     @license   https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\HelloWorld\Block\Adminhtml\Post\Edit\Tab;

class Post extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Wysiwyg config
     * 
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * Country options
     * 
     * @var \Magento\Config\Model\Config\Source\Locale\Country
     */
    protected $_countryOptions;

    /**
     * Country options
     * 
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $_booleanOptions;

    /**
     * Sample Multiselect options
     * 
     * @var \Mageplaza\HelloWorld\Model\Post\Source\SampleMultiselect
     */
    protected $_sampleMultiselectOptions;

    protected $context;
    protected $objectManager;

    /**
     * constructor
     * 
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param \Magento\Config\Model\Config\Source\Locale\Country $countryOptions
     * @param \Magento\Config\Model\Config\Source\Yesno $booleanOptions
     * @param \Mageplaza\HelloWorld\Model\Post\Source\SampleMultiselect $sampleMultiselectOptions
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Magento\Config\Model\Config\Source\Locale\Country $countryOptions,
        \Magento\Config\Model\Config\Source\Yesno $booleanOptions,
        \Mageplaza\HelloWorld\Model\Post\Source\SampleMultiselect $sampleMultiselectOptions,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->_wysiwygConfig            = $wysiwygConfig;
        $this->_countryOptions           = $countryOptions;
        $this->_booleanOptions           = $booleanOptions;
        $this->_sampleMultiselectOptions = $sampleMultiselectOptions;
        $this->context = $context;
        parent::__construct($context, $registry, $formFactory, $data);

        $form = $this->_formFactory->create();
        $curlInfo = $form->addFieldset(
            'curl_info',
            [
                'legend' => __('Curl Information'),
                'class'  => 'fieldset-wide'
            ]
        );
        $field = $curlInfo->addField('curl_info[general]', 'text', array(
            'name'      => 'curl_info[general]',
            'label'     => __('CURL Information'),
            'comment'   => '<b>Option:</b> CURLOPT_URL</br><b>Value:</b> https://www.example.com/',
        ));
        $fieldRenderer = $this->getLayout()->createBlock('\Mageplaza\HelloWorld\Block\Adminhtml\Form\Field\Curllist',"", array($context));
        $field->setRenderer($fieldRenderer);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Mageplaza\HelloWorld\Model\Post $post */
        $post = $this->_coreRegistry->registry('integrationui_profile');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('post_');
        $form->setFieldNameSuffix('post');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Profile Information'),
                'class'  => 'fieldset-wide'
            ]
        );
        $fieldset->addType('image', 'Mageplaza\HelloWorld\Block\Adminhtml\Post\Helper\Image');
        $fieldset->addType('file', 'Mageplaza\HelloWorld\Block\Adminhtml\Post\Helper\File');
        if ($post->getId()) {
            $fieldset->addField(
                'post_id',
                'hidden',
                ['name' => 'post_id']
            );
        }
        $fieldset->addField(
            'name',
            'text',
            [
                'name'  => 'name',
                'label' => __('Name'),
                'title' => __('Name'),
                'required' => true,
            ]
        );
        $fieldset->addField('process', 'select', array(
            'name'      => 'process',
            'label'     => __('Process'),
            'required'  => false,
            'options'   => array(
                'product_import'=> __('Product Import'),
            ),
        ));
        $fieldset->addField('approach', 'select', array(
            'name'      => 'approach',
            'label'     => __('Approach'),
            'required'  => false,
            'options'   => array(
                'file'=> __('File'),
                'Curl' => __('CURL'),
                'Soap' => __('Soap'),
            ),
        ));
        $fieldset->addField('event', 'text', array(
            'name'      => 'event',
            'label'     => __('Event'),
            'required'  => false,
        ));

        $fileInfo = $form->addFieldset(
            'file_info',
            [
                'legend' => __('File Information'),
                'class'  => 'fieldset-wide'
            ]
        );
        $fileInfo->addField('file_info[pattern]', 'text', array(
            'name'      => 'file_info[pattern]',
            'label'     => __('Pattern'),
            'required'  => false,
            'comment'   => 'siin kirjeldus',
        ));

        $fileInfo->addField('file_info[done]', 'text', array(
            'name'      => 'file_info[done]',
            'label'     => __('Imported Path'),
            'required'  => false,
        ));

        $fileInfo->addField('file_info[type]', 'select', array(
            'name'      => 'file_info[type]',
            'label'     => __('Type'),
            'required'  => false,
            'options'   => array(
                'csv' => __('CSV File'),
                'xml' => __('XML File'),
            ),
        ));

        $fileInfo->addField('file_info[delimiter]', 'text', array(
            'name'      => 'file_info[delimiter]',
            'label'     => __('Field Delimiter'),
            'required'  => false,
        ));

        $fileInfo->addField('file_info[enclosure]', 'text', array(
            'name'      => 'file_info[enclosure]',
            'label'     => __('Enclose Values In'),
            'required'  => false,
        ));

        $curlInfo = $form->addFieldset(
            'curl_info',
            [
                'legend' => __('Curl Information'),
                'class'  => 'fieldset-wide'
            ]
        );
        $field = $curlInfo->addField('curl_info[general]', 'text', array(
            'name'      => 'curl_info[general]',
            'label'     => __('CURL Information'),
            'comment'   => '<b>Option:</b> CURLOPT_URL</br><b>Value:</b> https://www.example.com/',
        ));
        $fieldRenderer = $this->getLayout()->createBlock('\Mageplaza\HelloWorld\Block\Adminhtml\Form\Field\Curllist',"", array($this->context));
        $field->setRenderer($fieldRenderer);

        $field = $curlInfo->addField('curl_info[header]', 'text', array(
            'name'      => 'curl_info[header]',
            'label'     => __('CURL Header'),
        ));
        $fieldRenderer = $this->getLayout()->createBlock('\Mageplaza\HelloWorld\Block\Adminhtml\Form\Field\Curl', "", array($this->context));
        $field->setRenderer($fieldRenderer);

        $soapInfo = $form->addFieldset(
            'soap_info',
            [
                'legend' => __('Soap Information'),
                'class'  => 'fieldset-wide'
            ]
        );
        $soapInfo->addField('soap_info[url]', 'text', array(
            'name'      => 'soap_info[url]',
            'label'     => __('URL'),
        ));

        $soapInfo->addField('soap_info[method]', 'text', array(
            'name'      => 'soap_info[method]',
            'label'     => __('Method'),
        ));

        $soapInfo = $soapInfo->addField('soap_info[parameters]', 'text', array(
            'name'      => 'soap_info[parameters]',
            'label'     => ('Parameters'),
        ));

        $defaultValues = $form->addFieldset('default_values_form', array('legend' => __('Default Values')));

        $field = $defaultValues->addField('default_values', 'text', array(
            'name'      => 'default_values',
            'label'     => __('Default Values'),
        ));

        $fieldRenderer = $this->getLayout()->createBlock('\Mageplaza\HelloWorld\Block\Adminhtml\Form\Field\Defaultvalues', "", array($this->context));
        $field->setRenderer($fieldRenderer);

        $fieldMapping = $form->addFieldset('field_mapping_form', array('legend' => __('Field Mapping')));

        $fieldMapping->addField('field_mapping', 'text', array(
            'name'      => 'field_mapping',
            'label'     => __('Field Mapping'),
        ));
        $fieldRenderer = $this->getLayout()->createBlock('\Mageplaza\HelloWorld\Block\Adminhtml\Form\Field\Fieldmapping', "", array($this->context));
        $fieldMapping->setRenderer($fieldRenderer);

        $order = $form->addFieldset('order', array('legend' => __('Order Export')));

//        $order->addField('status', 'select', array(
//            'name'      => 'status',
//            'label'     => __('Export Status'),
//            'required'  => false,
//            'values'   => Mage::helper('integrationui')->getOrderStatusList(),
//        ));
//
        $order->addField('status_after', 'select', array(
            'name'      => 'status_after',
            'label'     => __('Status After Export'),
            'required'  => false,
//            'values'   => Mage::helper('integrationui')->getOrderStatusList(),
        ));


        $postData = $this->_session->getData('mageplaza_helloworld_post_data', true);
        if ($postData) {
            $post->addData($postData);
        } else {
            if (!$post->getId()) {
                $post->addData($post->getDefaultValues());
            }
        }
        $form->addValues($post->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Post');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }
}
