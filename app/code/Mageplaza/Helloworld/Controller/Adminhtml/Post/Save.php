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
namespace Mageplaza\HelloWorld\Controller\Adminhtml\Post;

class Save extends \Mageplaza\HelloWorld\Controller\Adminhtml\Post
{
    /**
     * Upload model
     * 
     * @var \Mageplaza\HelloWorld\Model\Upload
     */
    protected $_uploadModel;

    /**
     * File model
     * 
     * @var \Mageplaza\HelloWorld\Model\Post\File
     */
    protected $_fileModel;

    /**
     * Image model
     * 
     * @var \Mageplaza\HelloWorld\Model\Post\Image
     */
    protected $_imageModel;

    /**
     * Backend session
     * 
     * @var \Magento\Backend\Model\Session
     */
    protected $_backendSession;

    protected $helper;
    /**
     * constructor
     * 
     * @param \Mageplaza\HelloWorld\Model\Upload $uploadModel
     * @param \Mageplaza\HelloWorld\Model\Post\File $fileModel
     * @param \Mageplaza\HelloWorld\Model\Post\Image $imageModel
     * @param \Magento\Backend\Model\Session $backendSession
     * @param \Mageplaza\HelloWorld\Model\PostFactory $postFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Mageplaza\HelloWorld\Model\Upload $uploadModel,
        \Mageplaza\HelloWorld\Model\Post\File $fileModel,
        \Mageplaza\HelloWorld\Model\Post\Image $imageModel,
        \Magento\Backend\Model\Session $backendSession,
        \Mageplaza\HelloWorld\Model\PostFactory $postFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Backend\App\Action\Context $context
    )
    {
        $this->_uploadModel    = $uploadModel;
        $this->_fileModel      = $fileModel;
        $this->_imageModel     = $imageModel;
        $this->_backendSession = $backendSession;
        $this->helper = $context->getObjectManager()->create('Mageplaza\HelloWorld\Helper\Data');
        parent::__construct($postFactory, $registry, $resultRedirectFactory, $context);
    }

    /**
     * run the action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPost('post');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $data = $this->_filterData($data);
            $post = $this->_initPost();
            $post->setData($data);

            $curlInfo = $data['curl_info'];
            $curlInfo = $this->helper->makeStorableArrayFieldValue($curlInfo);
            $post->setCurlInfo($curlInfo);

            $fileInfo = $data['file_info'];
            $fileInfo = $this->helper->makeStorableArrayFieldValue($fileInfo);
            $post->setFileInfo($fileInfo);

            $soapInfo = $data['soap_info'];
            $soapInfo = $this->helper->makeStorableArrayFieldValue($soapInfo);
            $post->setSoapInfo($soapInfo);

            $defaultValues = $data['default_values'];
            $defaultValues = $this->helper->makeStorableArrayFieldValue($defaultValues);
            $post->setDefaultValues($defaultValues);

            $fm = $data['field_mapping'];
            $fm = $this->helper->makeStorableArrayFieldValue($fm);
            $post->setFieldMapping($fm);

            $prefix = $data['field_mapping'];
            $prefix = $this->helper->makeStorableArrayFieldValue($prefix, false, true);
            $post->setPrefix($prefix);

            $updateMapping = $this->helper->makeStorableArrayFieldValue($fm,true);
            $post->setUpdateMapping($updateMapping);

//            var_dump($post); die();
            if ($this->getRequest()->getParam('id') != '') {
                $post->setProfileId($this->getRequest()->getParam('id'));
            }

            $this->_eventManager->dispatch(
                'mageplaza_helloworld_post_prepare_save',
                [
                    'post' => $post,
                    'request' => $this->getRequest()
                ]
            );
            try {
                $post->save();
                $this->messageManager->addSuccess(__('The Post has been saved.'));
                $this->_backendSession->setMageplazaHelloWorldPostData(false);
                if ($this->getRequest()->getParam('back')) {
                    $resultRedirect->setPath(
                        'mageplaza_helloworld/*/edit',
                        [
                            'post_id' => $post->getId(),
                            '_current' => true
                        ]
                    );
                    return $resultRedirect;
                }
                $resultRedirect->setPath('mageplaza_helloworld/*/');
                return $resultRedirect;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the Post.'));
            }
            $this->_getSession()->setMageplazaHelloWorldPostData($data);
            $resultRedirect->setPath(
                'mageplaza_helloworld/*/edit',
                [
                    'post_id' => $post->getId(),
                    '_current' => true
                ]
            );
            return $resultRedirect;
        }
        $resultRedirect->setPath('mageplaza_helloworld/*/');
        return $resultRedirect;
    }

    /**
     * filter values
     *
     * @param array $data
     * @return array
     */
    protected function _filterData($data)
    {
        if (isset($data['sample_multiselect'])) {
            if (is_array($data['sample_multiselect'])) {
                $data['sample_multiselect'] = implode(',', $data['sample_multiselect']);
            }
        }
        return $data;
    }
}
