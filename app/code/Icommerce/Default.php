<?php
/**
 * Copyright (c) 2009-2011 Icommerce Nordic AB
 *
 * Icommerce reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Icommerce, except as provided by licence. A licence
 * under Icommerce's rights in the Program may be available directly from
 * Icommerce.
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
 * @category    Icommerce
 * @package     Icommerce_Default
 * @copyright   Copyright (c) 2009-2011 Icommerce Nordic AB
 */

class Icommerce_Default {

    static function getModelData( Mage_Core_Model_Abstract $obj, $attr ){
        return self::getLoadModelData( $obj, $attr );
    }

    static function getLoadModelData( Mage_Core_Model_Abstract $obj, $attr ){
        $v = $obj->getData($attr);
        if( $v ) return $v;

        try {
            $v = Icommerce_Eav::readValue( $obj, $attr );
        } catch( Exception $e ){
            return null;
        }
        return $v;
    }

    /**
	 * Sets data on Varien_Object but avoids tagging it as "modified" if there really is no data change
     * (Magento by default does not care if anytrhing really change, after any setData the object will be
     * tagged for full save operation)
	 * is equal to the version specified or newer.
	 * @param Varien_Object $model
     * @param string $key
     * @param mixed $value
	 * @param Varien_Object $model
	 */
    static function setModelData( $model, $key, $value ){
        $vv = self::getLoadModelData( $model, $key );
        if( $value!=$vv ){
            $model->setData($key,$value);
        }
        return $model;
    }

    static function isModuleActive( $modname ){
        return Icommerce_Default_Helper_Data::isModuleActive( $modname );
    }

    static $_mv;
    // Returns an integer like 1410 or 1323
    static function getMagentoVersion( ){
        if( !self::$_mv ){
            $v = explode( ".",Mage::getVersion() );
            $v[] = 0;
            self::$_mv = $v[0]*1000 + $v[1]*100 + $v[2]*10 + $v[3];
        }
        return self::$_mv;
    }

    static function isEnterprise(){
        return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise');
    }

    /**
	 * Returns true if the base version of this Magento installation
	 * is equal to the version specified or newer.
	 * @param string $version
	 * @param unknown_type $task
	 */
	static function isBaseMageVersionAtLeast($version, $task = null)
	{
		// convert Magento Enterprise, Professional, Community to a base version
		$mage_base_version = self::convertVersionToCommunityVersion ( Mage::getVersion (), $task );

		if (version_compare ( $mage_base_version, $version, '>=' ))
			return true;

		return false;
	}

	static function convertVersionToCommunityVersion($version, $task = null)
	{
		/* Enterprise -
         * 1.9 | 1.8 | 1.5
         */
		if (self::isEnterprise())
		{
		    if (version_compare ( $version, '1.11.0', '>=' ))
		        return '1.6.0';
			if (version_compare ( $version, '1.9.1', '>=' ))
				return '1.5.0';
			if (version_compare ( $version, '1.9.0', '>=' ))
				return '1.4.2';
			if (version_compare ( $version, '1.8.0', '>=' ))
				return '1.3.1';
			return '1.3.1';
		}

		/* Professional -
         * If Entprise_Enterprise module is installed but it didn't pass Enterprise_Enterprise tests
         * then the installation must be Magento Pro edition.
         * 1.7 | 1.8
         */
		if (Mage::getConfig()->getModuleConfig( 'Enterprise_Enterprise' ))
		{
			if (version_compare ( $version, '1.8.0', '>=' ))
				return '1.4.1';
			if (version_compare ( $version, '1.7.0', '>=' ))
				return '1.3.1';
			return '1.3.1';
		}

		return $version;
	}

    // getStoreConfig, but try with theme/store code first:
    // Ordinary use:
    //   <myvar>47</myvar>
    // Per store use: ("gb" store code)
    //   <myvar-gb>47</myvar-gb>
    static function getStoreConfig( $val ) {
        return Icommerce_Default_Helper_Data::getStoreConfig( $val );
    }

    static $_is_logged_in;
    static function isLoggedIn(){
        if( Icommerce_Default::$_is_logged_in===null ){
            # Hack: Allow ourselves to be always logged in
            if( self::getStoreConfig("always_logged_in") ){
                $id = 1;
            }
            else {
                $cust = Mage::getSingleton('customer/session');
                $id = $cust->getId();
            }
            Icommerce_Default::$_is_logged_in = ($id>0);
        }
        return Icommerce_Default::$_is_logged_in;
    }

    static function getSiteRoot( $ensure_trailing_slash=false ){
        // Make sure Magento config is initialized
        Mage::app();
        $dir = Mage::getBaseDir();
        if( $ensure_trailing_slash ){
            $l = strlen($dir);
            if( $dir[$l-1]!='/' ){
                $dir .= "/";
            }
        }
        return $dir;
    }

    static function getInstanceName(){
        // Make sure Magento config is initialized
        Mage::app();
        $mv = array();
        preg_match( "@.*/(\\w+)@", Mage::getBaseDir(), $mv );
        return count($mv)>1 ? $mv[1] : null;
    }

    // Alias for above
    static function getInstance(){
        return self::getInstanceName();
    }

    static function getCurrentUrl( ){
        $uri = $_SERVER["REQUEST_URI"];
        if( ($p=strpos($uri,"?"))!==FALSE )
            $uri = substr($uri,0,$p);
        return "http://".$_SERVER["SERVER_NAME"].$uri;
    }


    static function useStoreCodeInUrl(){
        static $st_store_code_in_url;
        if( $st_store_code_in_url===null ){
            $v = Icommerce_Db::getValue( "SELECT value FROM core_config_data WHERE path='web/url/use_store' AND scope_id=0" );
            $st_store_code_in_url = (bool)$v;
        }
        return $st_store_code_in_url;
    }

    /** @var var cache the looked up store code / ID */
    static $st_url_store_code;
    static $st_url_store_id;

    /**
     * If site is using option 'use store code in URL:s' this function will extract it out and return it
     * @static
     * @return string
     */
    static function getStoreCodeFromUrl( ){
        if( self::$st_url_store_code!==null ) return self::$st_url_store_code;

        // Are we encoding store in URL ?
        if( self::useStoreCodeInUrl() ){
            // Will not cause recursion
            $uri = self::getCurrentSiteUrl( false, false );

            if( $store = preg_replace( "@/([^/]+)/.*@", "$1", $uri ) ){
                // If it really is a store code, then strip it
                $store_id = Icommerce_Db::getValue("SELECT store_id FROM core_store WHERE code=? AND is_active=1", array($store));
                if( $store_id ){
                    self::$st_url_store_id = $store_id;
                    self::$st_url_store_code = $store;
                    return $store;
                }
            }
        }

        // Case of different domains per store - want them in order stores, websites, default
        // We happen to get that ordering on scope character 5 :-)
        $domain = $_SERVER["SERVER_NAME"];
        if( $scopes = Icommerce_Db::getAssociative( "SELECT scope, scope_id FROM core_config_data WHERE path='web/unsecure/base_url' AND value LIKE ?
                                                     ORDER BY SUBSTRING(scope,5,1)", array('%'.$domain.'%') ) ){
            // Get the scope
            foreach( $scopes as $scope => $scope_id ){
                break;
            }
            $store_id = null;
            if( $scope=="websites" ){
                // We have website scope, unresolved if multiple active store fronts for this one.
                // Take first.
                $store_id = Icommerce_Db::getValue( "SELECT sg.default_store_id FROM core_website as w INNER JOIN core_store_group as sg ON w.website_id=sg.website_id AND w.website_id=? ",
                                                   array($scope_id) );
            } else if( $scope=="stores" ){
                // We have it
                $store_id = $scope_id;
            } else {
                // Default scope
                // Get the default store_id, globally
                $store_id = Icommerce_Db::getValue( "SELECT sg.default_store_id FROM core_website as ws INNER JOIN core_store_group as sg ON ws.is_default=1 AND
                                                     ws.website_id=sg.website_id" );
            }

            // Got something ?
            if( $store_id ){
                self::$st_url_store_id = $store_id;
                self::$st_url_store_code = Icommerce_Db::getValue( "SELECT code FROM core_store WHERE store_id=?", $store_id );
                return self::$st_url_store_code;
            }
        }

        // Got nothing - remeber that
        self::$st_url_store_code = "";
        return "";
    }

    /**
     * As above, but return the store ID (without repeated DB lookup)
     * @static
     * @return string
     */
    static function getStoreIdFromUrl( ){
        self::getStoreCodeFromUrl();
        return self::$st_url_store_id;
    }


   /**
    * Returns current URL, with option to strip off leading store code (if active)
    * Also automatically peels off local instance codes.
    * @static
    * @param bool $with_get
    * @param bool $strip_store
    * @return string
    */
    static function getCurrentSiteUrl( $with_get=false, $strip_store=false ){
        static $st_r = array();
        if( isset($st_r[$with_get][$strip_store]) ) return $st_r[$with_get][$strip_store];

        $uri = $_SERVER["REQUEST_URI"];
        // Get this working locally
        $inst = self::getInstanceName();
        if( substr($uri,0,strlen($inst)+2)=="/$inst/" ){
            $uri = substr($uri,strlen($inst)+1);
        }
        if( !$with_get ){
            $pos = strpos($uri,'?');
            if( $pos ) $uri = substr($uri,0,$pos);
        }

        if( $strip_store ){
            // Are we encoding store in URL ?
            if( self::useStoreCodeInUrl() && ($store = self::getStoreCodeFromUrl()) ){
                $uri = substr( $uri, 1+strlen($store) );
            }
        }

        $st_r[$with_get][$strip_store] = $uri;
        return $uri;
    }


    static $_website_code;
    static function getWebsiteCode(){
        if( !self::$_website_code ){
            self::$_website_code = Mage::app()->getWebsite()->getData("code");
        }
        return self::$_website_code;
    }

    static $_website_id;
    // Translate a store code to integer store ID
    static function getWebsiteId( $key=null ){
        if( !self::$_website_id ){
            self::$_website_id = Mage::app()->getWebsite()->getId();
        }
        return self::$_website_id;
    }

    static $_store_code;
    static function getStoreCode(){
        if( !self::$_store_code ){
            self::$_store_code = Mage::app()->getStore()->getData("code");
        }
        return self::$_store_code;
    }

    static $_store_ids;
    static $_store_id;
    // Translate a store code to integer store ID
    static function getStoreId( $key=null ){
        if( $key===null ){
            if( !self::$_store_id ){
                self::$_store_id = Mage::app()->getStore()->getId();
            }
            return self::$_store_id;
        }
        if( is_int($key) || !strcmp($key,(int)$key) ){ //("/^[0-9]+$/",$key) ){
            return $key;
        }
        if( !self::$_store_ids || !array_key_exists($key,self::$_store_ids) ){
           if( !self::$_store_ids ) self::$_store_ids = array();
           $id = Icommerce_Db::getDbSingleton( "SELECT store_id FROM core_store WHERE code='" . $key . "'" );
           self::$_store_ids[$key] = $id;
           return $id;
        }
        return self::$_store_ids[$key];
    }

    static function getDbRead(){
        return Icommerce_Db::getDbRead();
    }

    static function getDbWrite(){
        return Icommerce_Db::getDbWrite();
    }

    // Append to log file in Magento directory
    static function logAppend( $msg, $file="var/icommerce.log" ){
        if( $file[0]!=="/" ){
            $file = Mage::getBaseDir()."/".$file;
        }
        return Icommerce_Log::append( $file, $msg );
    }

    // This function now live in Utils module
    static function logAppendBT( $msg, $file="var/icommerce-bt.log" ){
        if( $file[0]!=="/" ){
            $file = Mage::getBaseDir()."/".$file;
        }
        return Icommerce_Utils::logAppendBT( $msg, $file );
    }

    static function logPerRequest( $msg, $log_path=null, $log_suffix="log" ){
        // If no log path, include request_uri in file name
        if( $log_path==null ){
            $log_path = "var/r_log/r-" . str_replace( "/", ";;", $_SERVER["REQUEST_URI"] );
        }
        if( $log_path[0]!=='/' ){
            $log_path = self::getSiteRoot(true) . $log_path;
        }
        return Icommerce_Log::requestLog( $msg, $log_path, false, $log_suffix );
    }


    static function getAttributeId( $attrib_code, $entity_type_id=null ){
        return Icommerce_Db::getAttributeId( $attrib_code, $entity_type_id );
    }

    static function getAttributeCode($attrib_id){
        return Icommerce_Db::getAttributeCode($attrib_id);
    }

    static function getEntityTypeId( $entity_type_code ){
        return Icommerce_Db::getEntityTypeId( $entity_type_code );
    }

    static function setOptionValue( $prod, $attrib, $vals, $store_id=null ){
        $aid = Icommerce_Eav::getAttributeId( $attrib );
        if( !$aid ) return null;
        $eid = $prod->getId();
        if( !($sid = self::getStoreId( $store_id )) ) $sid = 0;

        // Prepare values
        $etid = Icommerce_Eav::getEntityTypeId("catalog_product");
        if( !is_array($vals) ){
            $vals = array( $vals );
        }

        // Get the right backend table
        $tbl = Icommerce_Db::getValue( "SELECT backend_type FROM eav_attribute WHERE attribute_id=$aid" );
        if( $tbl=="int" ){
            if( count($vals)>1 ){
                throw new Exception( "Icommerce_Default::setOptionValue - Single select attribute: $attrib" );
            }

            // Clean old options
            Icommerce_Db::write( "DELETE FROM catalog_product_entity_int WHERE entity_id=$eid AND attribute_id=$aid AND store_id=$sid" );
            if( count($vals) ){
                $val = $vals[0];
                $values = "($etid,$aid,$sid,$eid,$val)";
                $sql = "INSERT INTO catalog_product_entity_int (entity_type_id,attribute_id,store_id,entity_id,value) VALUES " . $values;
                Icommerce_Db::write( $sql );
            }
        } else if( $tbl=="varchar" ){
            Icommerce_Db::write( "DELETE FROM catalog_product_entity_varchar WHERE entity_id=$eid AND attribute_id=$aid AND store_id=$sid" );
            $val = implode(",",$vals);
            $values = "($etid,$aid,$sid,$eid,'$val')";
            $sql = "INSERT INTO catalog_product_entity_varchar (entity_type_id,attribute_id,store_id,entity_id,value) VALUES " . $values;
            Icommerce_Db::write( $sql );
        } else {
            throw new Exception( "Icommerce_Default::setOptionValue - Unknown backend type: $tbl" );
        }

        return true;
    }


    static $_store_id_current;
    static function prepareStoreId( $store_id ){
        if( $store_id===null ){
            if( !self::$_store_id_current ){
                $store = Mage::app()->getStore();
                if( !$store ) return null;
                self::$_store_id_current = $store->getData("store_id");
            }
            return self::$_store_id_current;
        }
        else if( strcmp($store_id,(int)$store_id) ){
            if( $store_id=="admin" ){
                $store_id = Mage_Core_Model_App::ADMIN_STORE_ID;
            } else {
                $store_id = Icommerce_Db::getSingleton( "SELECT store_id FROM core_store WHERE code='$store_id'" );
            }
        }
        return $store_id;
    }

    static protected function checkLoadAttribOptions( $attrib, $store_id ){
        // Do we have values for this attrib loaded already?
        if( array_key_exists($attrib,self::$_attr_lut) &&
            array_key_exists($store_id,self::$_attr_lut[$attrib]) ){
            return true;
        }

        // Need to load the attributes
        $read = Icommerce_Db::getDbRead();

        // We do both admin and store specific values, since many times
        // only admin values are available
        $av = array();
        if( $store_id!==Mage_Core_Model_App::ADMIN_STORE_ID ){
            $admin_id = Mage_Core_Model_App::ADMIN_STORE_ID;
            $sql = "";
            $sql .= "SELECT eao.option_id, eaov.value ";
            $sql .= "FROM eav_attribute AS ea ";
            $sql .= "INNER JOIN eav_attribute_option AS eao ";
            $sql .= "ON eao.attribute_id = ea.attribute_id ";
            $sql .= "INNER JOIN eav_attribute_option_value AS eaov ";
            $sql .= "ON eao.option_id = eaov.option_id ";
            $sql .= "WHERE ea.attribute_code = '$attrib' AND eaov.store_id = $admin_id ";
            $sql .= "ORDER BY eao.sort_order, eaov.value;";

            $rows = $read->query($sql);
            if( !$rows ) return null;

            foreach( $rows as $row ){
                $av[$row['option_id']] = $row['value'];
            }
            // Store raw form of attrib values
            self::$_attr_lut[$attrib][$admin_id] = $av;
        }

        // Get option ids and values (improved sql)
        $sql = "";
        $sql .= "SELECT eao.option_id, eaov.value ";
        $sql .= "FROM eav_attribute AS ea ";
        $sql .= "INNER JOIN eav_attribute_option AS eao ";
        $sql .= "ON eao.attribute_id = ea.attribute_id ";
        $sql .= "INNER JOIN eav_attribute_option_value AS eaov ";
        $sql .= "ON eao.option_id = eaov.option_id ";
        $sql .= "WHERE ea.attribute_code = '$attrib' AND eaov.store_id = $store_id ";
        $sql .= "ORDER BY eao.sort_order, eaov.value;";

        $rows = $read->query($sql);
        if( !$rows ) return null;

        foreach( $rows as $row ){
            $av[$row['option_id']] = $row['value'];
        }
        self::$_attr_lut[$attrib][$store_id] = $av;

        return true;
    }

    // One can pass a product or an attribute option ID as the first argument.
    // The 3rd argument is an optional store ID/code (or "admin")
    static $_attr_lut = array();
    static function getOptionValue( $prod, $attrib, $store_id=null, $entity_type="catalog_product" ){
        $store_id = self::prepareStoreId( $store_id );
        if( $prod==null ) return "";

        if( is_int($prod) || is_string($prod) || is_array($prod) ){
            $id = $prod;
        }
        else {
            $id = self::getLoadModelData( $prod, $attrib );
        }
        if( !$id ) return null;

        // Catch the multiple option case
        if( is_string($id) ){
            if( strstr($id,",") ){
                $id = explode(",",$id);
            } else if( strcmp($id,(int)$id) && (is_object($prod) || is_numeric($prod)) ){
                // If text junk was stored here
                $id = Icommerce_Eav::readValue( $prod, $attrib, $entity_type, $store_id );
            }
        }

        if(is_array($id)){
            $res = array();
            foreach( $id as $the_id ){
                if (!is_array($the_id) && !is_object($the_id) && !is_numeric($the_id)) {
                    continue;
                }
                $res[] = self::getOptionValue($the_id, $attrib, $store_id);
            }
            return $res;
        }

        // Make sure we have loaded this attribute
        $r = self::checkLoadAttribOptions( $attrib, $store_id );
        if( $r===null ){
            return null;
        }

        // This will throw an exception if the ID val is not in the array
        if( isset(self::$_attr_lut[$attrib][$store_id][$id]) ){
            return self::$_attr_lut[$attrib][$store_id][$id];
        } else {
            return ":undef:";
        }
    }

    static $_attr_opt_rev_lut = array();
    static function getOptionValueId( $attrib, $value, $store_id=null ){
        // Sort out store ID
        $store_id = self::prepareStoreId( $store_id );
        $r = self::checkLoadAttribOptions( $attrib, $store_id );
        if( $r===null ){
            return null;
        }

        // Cached?
        $key = "$attrib:$store_id:$value";
        if( array_key_exists($key,self::$_attr_opt_rev_lut) ){
            return self::$_attr_opt_rev_lut[$key];
        }

        // Iterate options until we found requested one...
        $value = strtolower(trim($value));
        $options = self::$_attr_lut[$attrib][$store_id];
        foreach( $options as $id => $opt_label ){
            if( strtolower(trim($opt_label))===$value ){
                self::$_attr_opt_rev_lut[$key] = $id;
                return $id;
            }
        }
        return null;
    }

    static function createAttributeOption( $attrib, $opt_val, /*$store_id=null,*/ $sort_order=0, $entity_code="catalog_product" ){
        //$store_id = self::prepareStoreId( $store_id );
        // # Hard code value to admin value
        $store_id = 0;
        if( !($aid = Icommerce_Eav::getAttributeId( $attrib )) ){
            throw new Exception( "Icommerce_Default::createAttributeOption - Attribute does not exist" );
        }
        $wr = Icommerce_Db::getWrite();
        $r = $wr->query( "INSERT INTO eav_attribute_option (attribute_id,sort_order) VALUES ($aid,$sort_order)" );
        $opt_id = Icommerce_Db::getSingleton( "SELECT LAST_INSERT_ID()" );
        $r = $wr->query( "INSERT INTO eav_attribute_option_value (option_id,store_id,value) VALUES (?,?,?)",
                         array($opt_id, $store_id, $opt_val) );

        // Store in attribute option cache:
        self::checkLoadAttribOptions( $attrib, $store_id );
        self::$_attr_lut[$attrib][$store_id][$opt_id] = $opt_val;

        // Return ID of created option
        return $opt_id;
    }

    /**
	 * Returns option ID if it exists on given attribute. If option not yet exists, it creates the option.
	 * @param string $attrib Attribute code
     * @param string $value Attribute value
     * @param string $entity_code The code of the entity type
	 * @param int $opt_id
	 */
    static function getCreateOptionValueId( $attrib, $value, $entity_code="catalog_product" ){
        $opt_id = self::getOptionValueId( $attrib, $value );
        if( !$opt_id ){
            $opt_id = self::createAttributeOption( $attrib, $value, 0, $entity_code );
        }
        return $opt_id;
    }


    /**
	 * Returns option ID if it exists on given attribute. If option not yet exists, it creates the option.
	 * @param string $attrib Attribute code
     * @param int $opt_id
     * @param string $value Attribute value
     * @param string $store_id Store ID to change value for
     * @param string $entity_code The code of the entity type
	 */
    static function setAttributeOptionValue( $attrib, $opt_id, $opt_val, $store_id=null, $entity_code="catalog_product" ){
        $store_id = self::prepareStoreId( $store_id );

        if( !($aid = Icommerce_Eav::getAttributeId( $attrib )) ){
            throw new Exception( "Icommerce_Default::setAttributeOptionValue - Attribute does not exist" );
        }

        $r = Icommerce_Db::write( "DELETE FROM eav_attribute_option_value WHERE option_id=? AND store_id=?", array($opt_id,$store_id) );

        $r = Icommerce_Db::write( "INSERT INTO eav_attribute_option_value (option_id,store_id,value) VALUES (?,?,?);",
                                   array($opt_id, $store_id, $opt_val) );

        // Store in attribute option cache:
        self::checkLoadAttribOptions( $attrib, $store_id );
        self::$_attr_lut[$attrib][$store_id][$opt_id] = $opt_val;

        // Return ID of option
        return $opt_id;
    }


    static function inlineTranslate( $word, $store_id=null ){
        if( $store_id===null ){
            $store_id = Mage::app()->getStore()->getData("store_id");
        }
        $word = str_replace( "'", "\'", $word );
        $transl = Icommerce_Db::getSingleton( "SELECT translate FROM core_translate WHERE string LIKE '%::$word' AND store_id='$store_id';" );
        return $transl ? $transl : $word;
    }

    // This function looks up an attribute label directly from DB.
    // It works also for configurable products (where otherwise
    // super attributes define their own translations (per product)).
    static function getAttribLabel( $attrib_code, $store_id=null, $entity_type="catalog_product" ){
        // Sort out store ID
        if( ($store_id = self::prepareStoreId($store_id))==null ) return null;

        // Look up attribute:
        $entity_type_id = Icommerce_Eav::getEntityTypeId( $entity_type );
        $read = Icommerce_Db::getDbRead();
        if( gettype($attrib_code)=="integer" || preg_match("/^[0-9]+$/",$attrib_code) ){
            $sql = "SELECT frontend_label FROM eav_attribute WHERE attribute_id=$attrib_code ";
        }
        else {
            $sql = "SELECT frontend_label FROM eav_attribute WHERE attribute_code='$attrib_code' ";
        }
        $sql .= "AND entity_type_id=$entity_type_id";
        $r = $read->query( $sql );
        $attr_label = null;
        foreach( $r as $rr ){
            $attr_label = $rr['frontend_label'];
        }
        if( !$attr_label ){
            return null;
        }

        // Try to translate it
        if( Icommerce_Db::tableExists("eav_attribute_label") ){
            $aid = Icommerce_Db::getAttributeId( $attrib_code, $entity_type );
            $label = Icommerce_Db::getValue( "SELECT value FROM eav_attribute_label WHERE attribute_id=$aid AND store_id=$store_id" );
        } else {
            if( $store_id ){
                $label = self::inlineTranslate( $attr_label, $store_id );
            }
        }
        return $label ? $label : $attr_label;
    }

    // For configurable option, knowing admin label, extract store specific label
    static function getConfigLabelForStore( $label, $acode=null ){
        return Icommerce_Db::getValue( "SELECT sal2.value FROM catalog_product_super_attribute_label as sal JOIN catalog_product_super_attribute_label as sal2
                                        ON sal2.product_super_attribute_id=sal.product_super_attribute_id AND sal2.store_id IN (?,0) AND sal.value=?
                                        ORDER BY sal2.store_id DESC LIMIT 1", array(self::getStoreId(),$label) );
    }


    /**
	 * Returns option ID if it exists on given attribute. If option not yet exists, it creates the option.
	 * @param string $val Attribute value
     * @param string $attrib Attribute code
     * @param string $store_id The store it applies to
     * @param string $entity_code The code of the entity type
	 */
    static function formatValue( $val, $attrib, $store_id=null, $entity_type="catalog_product" ){

        // Be forgiving for undefined attributes
        if( !($ainfo = Icommerce_Eav::getAttributeInfo( $attrib, $entity_type )) ){
            // Static data goes here as well
            return "";
        }

        if( $ainfo["frontend_input"]=="select" ){
            return self::getOptionValue( $val, $attrib, $store_id );
        } else if( $ainfo["frontend_input"]=="multiselect" ){
            $vals = explode(",",$val);
            $val = "";
            foreach( $vals as $v ){
                if( $val ) $val .= ", ";
                $val .= self::getOptionValue( $v, $attrib, $store_id );
            }
        }
        else if( $ainfo["frontend_input"]=="date" ){
            static $st_locale;
            static $st_format;
            if( !$st_locale ){
                $st_locale = Mage::getModel("core/locale");
                $st_format = Mage::app()->getLocale()->getDateFormat(  Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM );
            }
            // Special case, will get converted to todays date otherwise
            if( !$val ) return "";
            $date = $st_locale->date( $val );
            //$val = $date->toString("dd MMMM yyy");
            $val = $date->toString( $st_format );
        }
        else if( $ainfo["frontend_input"]=="price" ){
            static $st_core_hlp;
            if( !$st_core_hlp ){
                $st_core_hlp = Mage::helper('core');
            }
            $val = $st_core_hlp->currencyByStore($val, $store_id, true, true);
        }

        // ## Could be other things here
        return $val;
    }


    static function formatData( $product_or_id, $attrib, $store_id=null, $entity_type="catalog_product" ){

        $pid = $product_or_id instanceof Varien_Object ? $product_or_id->getId() : $product_or_id;
        if( !$pid ) return "";

        // Be forgiving for undefined attributes
        if( !($ainfo = Icommerce_Eav::getAttributeInfo( $attrib, $entity_type )) ){
            // Static data goes here as well
            return "";
        }

        $val = Icommerce_Eav::getValue( $pid, $attrib, $entity_type, $store_id );
        return self::formatValue( $val, $attrib, $store_id, $entity_type );
    }


    static function cmsBlockExists( $identifier, $title=null ){
        return Icommerce_Cms::cmsBlockExists( $identifier, $title );
    }

    static function cmsBlockIsActive( $identifier ){
        return Icommerce_Cms::cmsBlockIsActive( $identifier );
    }

    static function cmsPageExists( $identifier, $title=null ){
        return Icommerce_Cms::cmsPageExists( $identifier, $title );
    }


    //  This function takes a product as argument and if the product is configurable it
    //  returns an array with it's associated products //Simon
    static function getAssociatedProducts( $configurableProduct ){
        return Icommerce_Products::getAssociatedProducts( $configurableProduct );
    }

    // Function to extract linked products (relation, bundle, super, up_sell, cross_sell)
    static function getLinkedProducts( $product, $link_type="relation", $attribs=array("name","sku","image","description","price") ){
        return Icommerce_Products::getLinkedProducts( $product, $link_type, $attribs );
    }

    // Function to retrieve quote from checkout - 1.4.1 compatible
    static $_quote;
    static function getCheckoutQuote( ){
        if( !self::$_quote ){
            // Adapted to Magento 1.4.1
            $checkout = Mage::getSingleton('checkout/session');
            $quote_id = $checkout->getQuoteId();
            $quote = Mage::getModel('sales/quote')
                    ->setStoreId(Mage::app()->getStore()->getId());
            if( $quote_id ){
                $quote->load( $quote_id );
            }
            self::$_quote = $quote;
        }
        return self::$_quote;
    }

    static function snippetInclude( $file ){
        $params = array(
            "_relative" => TRUE );
        $file = Mage::getDesign()->getTemplateFilename($file, $params);;
        if( $file ){
            $path = "app/design/$file";
            include( $path );
        }
    }


    // This returns the difference between current time zone and GMT
    static $_ts_offset;
    static function getCurrentTimeZoneOffset( ){
        if( !self::$_ts_offset ){
            $ts_now = time();
            $dt = date( 'D M j H:i:s' );
            $ts1 = strtotime($dt);
            $tz = date_default_timezone_get();
            $tz_org = self::getStoreConfig( "general/locale/timezone" );
            if( !$tz_org ) $tz_org = "Europe/Berlin";
            date_default_timezone_set($tz_org);
            $ts2 = strtotime($dt);
            date_default_timezone_set($tz);
            self::$_ts_offset = $ts2 - $ts1;
        }
        return self::$_ts_offset;
    }

    static function removeTimeZoneFromTimeStamp( $ts ){
        // To remove time zone effect rdduce remove the time zone offset
        return $ts + self::getCurrentTimeZoneOffset();
    }

    static function getBrowserInfo()
    {
        return Icommerce_Utils::getBrowserInfo();
    }

    static function urlify($url){

        return Icommerce_Utils::urlify($url);
    }

    static function siteFileExists( $offset_path ){
        $path = self::getSiteRoot(true) . $offset_path;
        if( !file_exists( $path ) ) return "";
        return $offset_path;
    }

    static function resortAttributeOptions( $attrib, &$options )
    {
        if ( !$options ){
            return false;
        }
        // Need to load the attributes
        $read = Icommerce_Db::getDbRead();

        $opt_ids = implode( ",", $options );
        if ( !$opt_ids ){
            return false;
        }

        $av = array();
        $sql = "";
        $sql .= "SELECT eao.option_id, eaov.value ";
        $sql .= "FROM eav_attribute AS ea ";
        $sql .= "INNER JOIN eav_attribute_option AS eao ";
        $sql .= "ON eao.attribute_id = ea.attribute_id ";
        $sql .= "INNER JOIN eav_attribute_option_value AS eaov ";
        $sql .= "ON eao.option_id = eaov.option_id ";
        $sql .= "WHERE ea.attribute_code = '$attrib' AND eaov.option_id IN ( $opt_ids ) ";
        $sql .= "ORDER BY eao.sort_order, eaov.value;";

        $rows = $read->query($sql);
        if ( !$rows ) return false;

        foreach ( $rows as $row ) {
            $av[$row['value']] = $row['option_id']; // hmmm, correct for where it is used, vice versa for similar functions in this file...
        }

        foreach ( $options as $id => $option ) {
            if (!isset($av[$id])) {
                $av[$id] = $option;
            }
        }

        $options = $av;

        return true;
    }

}
