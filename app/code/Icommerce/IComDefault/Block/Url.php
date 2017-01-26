<?php
//require_once( "Icommerce/Utils/StrUtils.php" );

class Icommerce_Default_Block_Url extends \Magento\Framework\View\Element\Template {

    public function getTheUrl(){
        $type = $this->getData("url");
        switch( $type ){

            case "current":
            case "this":
            case "now":
                //$url = Mage::getUrl();
                $url = $_SERVER["REQUEST_URI"];
                $p = strpos($url,"?");
                if( $p ){
                    $url = substr( $url, 0, $p );
                }
                break;

            case "current_category":
            case "current_cat_prod":
                //$url = Mage::getUrl();
                $url = $_SERVER["REQUEST_URI"];
                $p = strpos($url,"?");
                if( $p ) $url = substr( $url, 0, $p );
                if( strendswith($url,".html") ){
                    break;
                }
                /*
                $sl = strlen($url);
                if( $url[$sl-1]=="/" ) $url = substr( $url, 0, $sl-1 );

                // Get the last component of the URL and see if it is a valid category
                $matches = array();*/
                //if( preg_match("|.*/([^/]*)?$|",$url,$matches )>0 ){
                /*  if( Icommerce_Category::urlKeyExists($matches[1]) ){
                        break;
                    }
                }*/

            case 'category_id':
                $cat_id = $this->getData("id");
                $url = Icommerce_Eav::getValue( $cat_id, "url_path", "catalog_category" );
                break;

            case "baseurl":
            case "base_url":
            case "base":
            default:
                $url = Mage::getBaseUrl();
                break;
        }
        $suffix = $this->getData("suffix");
        if( $suffix ) {
            $url .= $suffix;
        }

        if( strlen($url) && $url[0]!='/' ){
            $url = Mage::getBaseUrl() . $url;
        }
        return $url;
    }

    public function _toHtml(){
        return $this->getTheUrl();
    }

}
