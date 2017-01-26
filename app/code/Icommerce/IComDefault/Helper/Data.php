<?php

namespace Icommerce\IcomDefault\Helper;

class Data {

    protected $_moduleManager;
    protected $scopeConfig;

    public function __construct( \Magento\Framework\Module\Manager $moduleManager,
                                 \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->_moduleManager = $moduleManager;
        $this->scopeConfig = $scopeConfig;
    }

    // Get a data member from a model object, load from database if needed.
    static function getLoadModelData( \Magento\Framework\Model\AbstractModel $obj, $attr ){
        $v = $obj->getData($attr);
        if( $v ) return $v;

        $coll = $obj->getResourceCollection()
            ->addAttributeToSelect($attr)
            ->addAttributeToFilter( "entity_id", $obj->getId() )
            ->setPage(1,1);
        foreach( $coll as $item ){
            $v = $item->getData($attr);
            $obj->setData($attr,$v);
            return $v;
        }
        return null;
    }

    // Check if a module is loaded and active or not
    function isModuleActive( $moduleName )
    {
        return $this->moduleManager->isEnabled($moduleName);
    }

    // getStoreConfig, but try with theme/store code first:
    // Ordinary use:
    //   <myvar>47</myvar>
    // Per store use: ("gb" store code)
    //   <myvar-gb>47</myvar>
    function getStoreConfig( $val ) {
        return $this->scopeConfig->getValue('dev/debug/template_hints', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

    }
}
