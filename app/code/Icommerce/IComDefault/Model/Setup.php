<?php
/**
 * Created by JetBrains PhpStorm.
 * User: arne
 * Date: 2011-11-04
 * Time: 05.47
 * To change this template use File | Settings | File Templates.
 */

namespace Icommerce\IcomDefault\Model;

class Setup extends \Magento\Framework\Module\Setup  {

    public function getPreviousVersion(){
        return $this->_getResource()->getDataVersion($this->_resourceName);
    }

    public function getNextVersion(){
        return (string)$this->_moduleConfig->version;
    }

}

