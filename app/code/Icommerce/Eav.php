<?php
/**
 * Class that manage Eav database tables.
 *
 * Eav (Entity–attribute–value model) database tables
 * @link http://en.wikipedia.org/wiki/Entity-attribute-value_model
 * @copyright (C) Icommerce 2011
 * @package Icommerce_Eav
 * @version
 */

require_once("Icommerce/Utils/StrUtils.php");

class Icommerce_Eav {

    /**
     * Define static entity codes for Magento 1.3.x and Magento 1.4.x
     *
     * $_entity_type_code_last, $_entity_type_id_last2;
     * These are not static in M 1.7+ !!!
     * @static
     * @var array $_entity_codes_M13x_M14x
     * @todo FIX
     */
    static $_entity_codes_M13x_M14x = array(
        "customer" => 1,
        "customer_address" => 2,
        "customer_payment" => 3,
        "order" => 4,
        "order_status" => 5,
        "order_address" => 6,
        "order_item" => 7,
        "order_payment" => 8,
        "catalog_category" => 9,
        "catalog_product" => 10,
        "quote" => 11,
        "quote_address" => 12
    );

    /**
     * Internal cache of entity type id's.
     *
     * so that the database does not have to be queried more than once for the same entity
     * @static array
     */
    static $_entity_codes = array( );


    /**
     * Set data on object, which we know cause no change in database - to avoid model save at later time.
     * (Magento by default does not care if anything really change, after any setData the object will be
     * tagged for full save operation)
     *
     * @static
     * @param Varien_Object $model The model to store data on
     * @param string $key The attribute
     * @param string $value The value
     * @return null
     */
    static protected function setDataWithoutChange( Varien_Object $model, $key, $value ){
        $changed = $model->hasDataChanges();
        $model->setData( $key, $value );
        if( !$changed ){
            $model->setDataChanges( false );
        }
    }

    /**
     * Get the entity_type_id for a given EAV entity type.
     *
     * The string in entity_type_code can be either an
     * entity_type_code (e.g. "catalog_product") or an entity_type_id (e.g. "10")
     * If an entity_type_id is used, that same id will be returned unaltered
     *
     * @static
     * @param string $entity_type_code Entity code of the EAV entity
     * @return int|null entity_type_id (null if not found)
     */
    static function getEntityTypeId( $entity_type_code ){
        if( !$entity_type_code ) {
            return null;
        }

        if( Icommerce_Utils::isInteger($entity_type_code) ){
            return $entity_type_code;
        }

        if( array_key_exists($entity_type_code,self::$_entity_codes) ){
            return self::$_entity_codes[$entity_type_code];
        }

        $id = Icommerce_Db::getValue( 'SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code=?', array($entity_type_code) );
        self::$_entity_codes[$entity_type_code] = $id;

        return $id;
    }


    /**
     * Get the entity_type for either an object, or explicitely from an ID + given type
     *
     * @static
     * @param string|int|Varien_Object $id_or_object The object ID or the Varien_Object itself
     * @return string|null entity_type (null if not found)
     */
    static function getEntityTypeOf( $id_or_object, $entity_type, &$obj, &$entity_id ){
        if( $id_or_object instanceof Varien_Object ){
            $obj = $id_or_object;
            $entity_id = $obj->getId();
            if( !$entity_type ){
                // Try from data array
                if( !($entity_type_id=$obj->getData("entity_type_id")) ){
                    // Go by object type
                    if( $obj instanceof Mage_Catalog_Model_Product ) $entity_type = "catalog_product";
                    else if( $obj instanceof Mage_Catalog_Model_Category ) $entity_type = "catalog_product";
                    else if( $obj instanceof Mage_Customer_Model_Customer ) $entity_type = "customer";
                    else if( $obj instanceof Mage_Customer_Model_Customer_Address ) $entity_type = "customer_address";
                } else {
                    static $st_rev_typeid_lookup = array();
                    if( !isset($st_rev_typeid_lookup[$entity_type_id]) ){
                        $st_rev_typeid_lookup[$entity_type_id] = Icommerce_Db::getValue( "SELECT entity_type_code FROM eav_entity_type WHERE entity_type_id=?", array($entity_type_id) );
                    }
                    $entity_type = $st_rev_typeid_lookup[$entity_type_id];
                }
            }
        } else {
            // We didn't get an object so must be happy with what we have
            // $id_or_object is an ID
            $entity_id = $id_or_object;
        }
        return $entity_type;
    }


    /**
     * Get the entity_type ID for either an object, or explicitely from an ID + given type
     *
     * @static
     * @param string|int|Varien_Object $id_or_object The object ID or the Varien_Object itself
     * @return string|null entity_type_id (null if not found)
     */
    static function getEntityTypeIdOf( $id_or_object, $entity_type, &$obj, &$entity_id ){
        return self::getEntityTypeId( self::getEntityTypeOf( $id_or_object, $entity_type, $obj, $entity_id ) );
    }


    /**
     * Internal cache of all loaded additional_attribute_tables per entity, so that the database does not have to be queried more than once per entity
     * @static array
     */
    static $_entity_additional_table = array();


    /**
     * Get the name of the additional EAV attribute info table for a given EAV entity type
     *
     * @static
     * @param string $entity_type_code Entity code of the EAV entity
     * @return string|null Name of additional_attribute_table (null if entity does not have an additional attribute table)
     */
    static function getEntityAdditionalTable( $entity_type_code ){
        if (Icommerce_Default::getMagentoVersion()<1400) { //Enterprise always had this column, but CE < 1.4 did not
            return null;
        }
        $eid = self::getEntityTypeId($entity_type_code);

        if( array_key_exists($eid,self::$_entity_additional_table) ){
            return self::$_entity_additional_table[$eid];
        }

        $tbl = Icommerce_Db::getValue( 'SELECT additional_attribute_table FROM eav_entity_type WHERE entity_type_id=?', array($eid) );
        if( is_null($tbl) ){
            // throw new Exception('Icommerce_Eav - Failed looking up EAV additional table type'); //self::createEavAttribute depends on this to return null not throw!
        } else {
            $tbl = str_replace( '/', '_', $tbl );
        }
        self::$_entity_additional_table[$eid] = $tbl;
        return $tbl;
    }

    /**
     * Internal cache of all loaded attributes of a given entity, so that the database does not have to be queried more than once per attribute
     * @static array
     */
    static $_attr_info_lut = array();

    /**
     * Get info about one EAV attribute of a given EAV entity type
     *
     * @static
     * @param string $attrib_code Attribute code to look up info for
     * @param null|string $entity_type_code optional Entity code of the EAV entity
     * @return array|null Arrays of info about attribute
     */
    static function getAttributeInfo( $attrib_code, $entity_type_code=null ){
        $entity_type_id = self::getEntityTypeId( $entity_type_code );

        if ($entity_type_code && !$entity_type_id) return null;

        // Cached?
        if( array_key_exists($entity_type_id,self::$_attr_info_lut) &&
            array_key_exists($attrib_code,self::$_attr_info_lut[$entity_type_id]) ){
            return self::$_attr_info_lut[$entity_type_id][$attrib_code];
        }

        $sql = 'SELECT attribute_id, backend_type, entity_type_id, frontend_input, source_model FROM eav_attribute WHERE attribute_code=?';
        $bind = array($attrib_code);
        if( $entity_type_id && $entity_type_id != 0){
            $sql .= ' AND entity_type_id=?';
            $bind[] = $entity_type_id;
        }

        $attr_info = Icommerce_Db::getRow($sql,$bind);
        if( $attr_info ){
            // Check if attribute is per website or global (catalog / product)
            if( $attr_info['entity_type_id']==self::getEntityTypeId('catalog_category') || $attr_info['entity_type_id']==self::getEntityTypeId('catalog_product') ){
                $eav_tbl = Icommerce_Default::getMagentoVersion()>=1400 ? 'catalog_eav_attribute' : 'eav_attribute';
                $attr_info['is_global'] = Icommerce_Db::getValue( 'SELECT is_global FROM ' . $eav_tbl . ' WHERE attribute_id=?', array($attr_info["attribute_id"]) );
            } else {
                $attr_info['is_global'] = 1;
            }
        }

        self::$_attr_info_lut[$entity_type_id][$attrib_code] = $attr_info;
        return $attr_info;
    }

    /**
     * Get a list of all attribute codes for a given entity type
     *
     * @static
     * @param $entity_type_code
     * @return array|null
     */
    static function getEntityAttributes($entity_type_code){
        if (!$entity_type_id = self::getEntityTypeId( $entity_type_code )) {
            return null;
        }

        $read = Icommerce_Db::getDbRead();
        $select = $read->select()
                ->from('eav_attribute', 'attribute_code')
                ->where('entity_type_id=?', $entity_type_id);

        return $read->fetchCol($select);
    }

    /**
     * Get the attribute_id of one EAV attribute of a given EAV entity type
     *
     * @static
     * @param string $attrib_code attribute code of the attribute
     * @param string $entity_type_code Entity code of the EAV entity
     * @return int|null attribute_id (null if not found)
     */
    static function getAttributeId( $attrib_code, $entity_type_code=null ){
        $attr_info = self::getAttributeInfo( $attrib_code, $entity_type_code );
        return $attr_info ? $attr_info['attribute_id'] : null;
    }

    /**
     * Get the backend data type of one EAV attribute of a given EAV entity type
     *
     * @static
     * @param string $attrib_code attribute code of the attribute
     * @param string $entity_type_code Entity code of the EAV entity
     * @return string|null data type (null if not found)
     */
    static function getAttributeType($attrib_code,$entity_type_code)
    {
        $attrInfo = self::getAttributeInfo( $attrib_code, $entity_type_code );
        return $attrInfo ? $attrInfo['backend_type'] : null;
    }


    /**
     * Give attribute_id and get attribute_code
     *
     * Extra attributes are found on for instance products.
     * @static
     * @throws Exception
     * @param string|int $attrib_id as found in eav_attribute database table
     * @return null|int attribute_code as found in eav_attribute database table
     */
    static function getAttributeCode($attrib_id){
        if( $attrib_id != null && $attrib_id != '' ){
            $attrib_code = Icommerce_Db::getValue( 'SELECT attribute_code FROM eav_attribute WHERE attribute_id=?', array($attrib_id) );
            if( is_null($attrib_code) ){
                throw new Exception('Icommerce_Default - Failed looking up EAV attribute code');
            }
            return $attrib_code;
        }
        return null;
    }

    /**
     * Returns whether a given EAV stores its own entity_type_id in its master entity table
     *
     * @static
     * @param string $entity_type_code entityCode of the EAV entity
     * @return boolean true if EAV type stores its own entity_type_id in its entity table
     */
    static function entityStoresTypeId($entity_type_code)
    {
        static $st_ent_types = array(
            'customer' => true,
            'customer_address' => true,
            'catalog_category' => true,
            'catalog_product' => true
        );
        return isset($st_ent_types[$entity_type_code]);
    }

    /**
     * Get the name of the master entity table for a given EAV type
     *
     * @static
     * @param string $entity_type_code entityCode of the EAV entity
     * @return string table name
     */
    static function getEntityMainTable($entity_type_code)
    {
        if (!self::getEntityTypeId($entity_type_code)) {
            return $entity_type_code;
        }
        switch ($entity_type_code) //TODO: Should really be looked up! or made version dependent (e.g. 'order' table can be 'sales_flat_order' or sales_order
        {
            case 'order':
                return 'sales_flat_order';
            case 'quote':
                return 'sales_flat_quote';
            //... more to come
            default:
                return $entity_type_code . '_entity';
        }
    }

    /**
     * Determines whether a given table name is a Detail table (has foreign key relationships to a master table)
     * This function is only meant to be used for tables where the Master/Detail role cannot be determined automatically
     *
     * @static
     * @param string $tableName name of database table
     * @param null|string $backend_type data type of the data to be stored in the table
     * @return bool true if $tableName refers to a detail table, false otherwise
     */
    static function isDetailTable($tableName,$backend_type=null)
    {
        if ($backend_type!='' && $backend_type!='static') {
            return true;
        }
        switch ($tableName) {
            case 'enterprise_giftregistry_data':
            case 'enterprise_giftregistry_item':
            case 'enterprise_giftregistry_person':
            case 'enterprise_giftregistry_type_info':
            case 'enterprise_giftregistry_label':
                return true;
        }
        return false;
    }

    static $_entities = array();

    /**
     * @static
     * @param $entity_type
     * @return array|null
     */
    static function getEntityMetaData( $entity_type ){
        $entity_type = self::getEntityTypeId($entity_type);
        if( !array_key_exists($entity_type,self::$_entities) ){
            $data = array();
            if( !($tbl = Icommerce_Db::getValue('SELECT entity_table FROM eav_entity_type WHERE entity_type_id=?', array($entity_type))) ){
                return null;
            }
            $data["table"] = str_replace("/","_",$tbl);
            $data['backend_types'] = Icommerce_Db::getColumn('SELECT DISTINCT(backend_type) FROM `eav_attribute` WHERE entity_type_id=?',array($entity_type));

            // Can this type have local/store fron values?
            $data["only_global"] = ($entity_type==self::getEntityTypeId("catalog_category") || $entity_type==self::getEntityTypeId("catalog_product")) ? 0 : 1;

            self::$_entities[$entity_type] = $data;
            return $data;
        } else {
            return self::$_entities[$entity_type];
        }
    }


    static protected $_write_log;
    static function activateWriteLog( $activate ){
        self::$_write_log = $activate;
    }

    /**
     * Function to create a new EAV attribute in Magento, based on
     * a template attribute (copying existsing attribute, modifying and pasting).
     *
     * @static
     * @param $new_attr_code Attribute code of new attribute
     * @param $entity_type The type of the new attribute
     * @param $template_attr_code The attribute to copy from
     * @param array $special_vals All values of the new attribute that are different for the new attribute, compared to the template one
     * @param null $sort_order The sort order (used for position in admin pages)
     * @return int|null
     */
    static function createAttribute(
            $new_attr_code, $entity_type, $template_attr_code,
            $special_vals=array(), $sort_order=null ){
        return self::createEavAttribute( $new_attr_code, $entity_type,
                                         $template_attr_code, $special_vals, $sort_order=null );
    }

    /**
     * @var int
     */
    static $_sort_order = 1001;

    /**
     * @static
     * @param $new_attr_code
     * @param $entity_type
     * @param $template_attr_code
     * @param array $special_vals
     * @param null $sort_order
     * @return int|null
     * @todo Extend description
     */
    static function createEavAttribute(
            $new_attr_code, $entity_type, $template_attr_code,
            $special_vals=array(), $sort_order=null ){

        if( !is_string($new_attr_code) || !is_string($template_attr_code) ||
            is_null($entity_type) ){
            return null;
        }

        // Get entity type ID
        if( is_string($entity_type) && !preg_match("/^[0-9]+$/",$entity_type) ){
            $entity_type = self::getEntityTypeId( $entity_type );
            if( is_null($entity_type) ){
                return null;
            }
        }

        // Does attribute already exist?
        // Template attribute must exist
        if( self::getAttributeId($new_attr_code,$entity_type)!==null ||
            ($tmpl_aid = self::getAttributeId($template_attr_code,$entity_type))===null ){
            return null;
        }

        // Have new attribute ID and template ID... continue
        $attrs = Icommerce_Db::getRow( 'SELECT * FROM eav_attribute WHERE attribute_id=?', array($tmpl_aid) );
        if( !$attrs ){
            return null;
        }

        // Have the template row... correct it
        unset( $attrs["attribute_id"] );
        $attrs["attribute_code"] = $new_attr_code;
        $attrs_extra = array();
        // Always user attrib
        $special_vals["is_user_defined"] = true;
        foreach( $special_vals as $k => $v ){
            if( isset($attrs[$k]) ){
                $attrs[$k] = $v;
            } else {
                $attrs_extra[$k] = $v;
            }
        }

        // Create the new attribute
        $wr = Icommerce_Db::getDbWrite();
        $vals = implode( ",",Icommerce_Db::wrapQueryValues($attrs) );
        $sql = "INSERT INTO eav_attribute (" .
               implode(",",array_keys($attrs)) .
               ") VALUES ( $vals );";
        try{
            $r = $wr->query( $sql );
        } catch( Exception $e ){ return null; }

        //invalidate cache for new attribute, to force reloading on next info query
        unset(self::$_attr_info_lut[$entity_type][$new_attr_code]);

        // Get the new attribute ID:
        $new_attr_id = self::getAttributeId( $new_attr_code, $entity_type );
        if( !$new_attr_id ){
            return null;
        }

        // On Magento 1.4 we may have to update a side eav attribute table
        if( Icommerce_Default::getMagentoVersion()>=1400 &&
            ($add_tbl=self::getEntityAdditionalTable($entity_type)) ){
            $attrs = Icommerce_Db::getRow( "SELECT * FROM $add_tbl WHERE attribute_id=$tmpl_aid" );
            if( !$attrs ){
                return null;
            }

            // Have the template row... correct it
            $attrs["attribute_id"] = $new_attr_id;
            foreach( $attrs_extra as $k => $v ){
                if( isset($attrs[$k]) ){
                    $attrs[$k] = $v;
                } else {
                    $x = 1;
                }
            }

            // Create the new attribute
            $vals = implode( ",",Icommerce_Db::wrapQueryValues($attrs) );
            $sql = "INSERT INTO $add_tbl (" .
                   implode(",",array_keys($attrs)) .
                   ") VALUES ( $vals );";
            try{
                $r = $wr->query( $sql );
            } catch( Exception $e ){ return null; }
        }

        // ...and do the eav_entity_attribute table
        $attrs = Icommerce_Db::getRow( "SELECT * FROM eav_entity_attribute WHERE attribute_id=$tmpl_aid" );
        if( !$attrs ){
            return null;
        }

        // Have the template row... correct it
        unset( $attrs["entity_attribute_id"] );
        $attrs["attribute_id"] = $new_attr_id;
        if( $sort_order!==null ){
            $attrs["sort_order"] = $sort_order;
        } else {
            $attrs["sort_order"] = self::$_sort_order++;
        }

        // Create the new attribute
        $wr = Icommerce_Db::getDbWrite();
        $vals = implode( ",",Icommerce_Db::wrapQueryValues($attrs) );
        $sql = "INSERT INTO eav_entity_attribute (" .
               implode(",",array_keys($attrs)) .
               ") VALUES ( $vals );";
        try {
            $r = $wr->query( $sql );
        } catch( Exception $e ){ return null; }

        // Done
        return $new_attr_id;
    }

    static function writeValue( $entity_id, $key, $val, $entity_type=null, $store_id=null ){
        return self::writeEavValue( $entity_id, $key, $val, $entity_type, $store_id );
    }

    static function setValue( $entity_id, $key, $val, $entity_type=null, $store_id=null ){
        return self::writeEavValue( $entity_id, $key, $val, $entity_type, $store_id );
    }

    /**
     * @var
     */
    static $st_did_warning;

    /**
     * @var
     */
    static $st_did_message;

    /**
     * @static
     * @throws Exception
     * @param $entity_id
     * @param $key
     * @param $val
     * @param null $entity_type
     * @param null $store_id
     * @return bool
     * @todo Extend description
     */
    static function writeEavValue( $entity_id, $key, $val, $entity_type=null, $store_id=null ){
        $obj = null;
        $entity_type = self::getEntityTypeIdOf( $entity_id, $entity_type, $obj, $entity_id );
        if( !$entity_type ){
            throw new Exception( "writeEavValue: Have no entity_type" );
        }

        // Do the database operation
        $wr = Icommerce_Db::getDbWrite();
        $data = self::getEntityMetaData( $entity_type );
        if( !$data  ){
            throw new Exception( "writeEavValue: No result calling - getEntityMetaData" );
        }

        $tbl = strendswith($data["table"],"_entity") ? $data["table"] : $data["table"] . "_entity";
        $info = self::getAttributeInfo( $key, $entity_type );
        if( !$info ){
            // Is it a static column?
            if( !Icommerce_Db::columnExists($tbl,$key) ){
                throw new Exception( "writeEavValue: No result calling - getAttributeInfo [and not static]" );
            }
        }

        // Prepare store ID
        if( $store_id===null && $info && $info["is_global"]==1 ){
            $sid = 0; // admin/global value
        } else {
            $sid = Icommerce_Default::prepareStoreId( $store_id );
        }
        if( is_null($sid) ){
            throw new Exception( "readEavValue: Coult not resolve store - $store_id" );
        }

        $wval = Icommerce_Db::wrapQueryValues( $val );
        try {
            if( !$info || $info["backend_type"]=="static" ){
                // Static case
                $sql = "UPDATE $tbl SET $key=$wval WHERE entity_id=$entity_id";
            } else {
                // EAV case
                $tbl_eav = $tbl . "_".$info["backend_type"];
                $aid = $info["attribute_id"];
                if( $data["only_global"] ){
                    $sql = "INSERT INTO $tbl_eav (entity_type_id,attribute_id,entity_id,value) VALUES
                            ($entity_type,$aid,$entity_id,$wval)
                            ON DUPLICATE KEY UPDATE value=$wval";
                } else {
                    $sql = "INSERT INTO $tbl_eav (entity_type_id,attribute_id,entity_id,value,store_id) VALUES
                            ($entity_type,$aid,$entity_id,$wval,$sid)
                            ON DUPLICATE KEY UPDATE value=$wval";
                }
            }
            // We get PHP / PCRE errors for long strings here. Increment until it works
            // (see http://php.net/manual/en/function.preg-replace.php)
            $n_tries = 0;
            while( true ){
                try {
                    $wr->query( $sql );
                    break;
                } catch( Exception $e ){
                    $n_tries++;
                    $err = preg_last_error();
                    if( $err== PREG_BACKTRACK_LIMIT_ERROR ){
                        if( $n_tries<50 ){
                            ini_set( 'pcre.backtrack_limit', (int)ini_get( 'pcre.backtrack_limit' )+15000 );
                            continue;
                        } else {
                            if( !self::$st_did_warning ){
                                self::$st_did_warning = true;
                                Icommerce_Messages::logWarning("Attempt to fix backtrack_limit for preg_replace: failed after $n_tries", "PHP" );
                            }
                        }
                        throw $e;
                    }
                }
            }
            if( $n_tries>0 ){
                if( !self::$st_did_message ){
                    self::$st_did_message = true;
                    Icommerce_Messages::logMessage("Attempt to fix backtrack_limit for preg_replace: success after $n_tries", "PHP" );
                }
            }
        } catch( Exception $e ){
            $x = 1;
            throw new Exception( "writeEavValue: Failed writing to EAV database.\nsql:" . $sql . "\n. base exception: " . $e->getMessage() .".\n" );
        }

        // Update Magento object if have one
        if( $obj ){
            self::setDataWithoutChange( $obj, $key, $val );
        }
        return true;
    }

    static function readValue( $entity_id, $key, $entity_type=null, $store_id=null ){
        return self::readEavValue( $entity_id, $key, $entity_type, $store_id );
    }

    static function getValue( $entity_id, $key, $entity_type=null, $store_id=null ){
        return self::readEavValue( $entity_id, $key, $entity_type, $store_id );
    }

    /**
     * @static
     * @throws Exception
     * @param $entity_id
     * @param $key
     * @param null $entity_type
     * @param null $store_id
     * @return null
     * @todo Extend description
     */
    static function readEavValue( $entity_id, $key, $entity_type=null, $store_id=null ){
        $obj = null;
        $entity_type = self::getEntityTypeIdOf( $entity_id, $entity_type, $obj, $entity_id );
        if( !$entity_type ){
            throw new Exception( "readEavValue: Have no entity_type" );
        }

        // Do the database operation
        $data = self::getEntityMetaData( $entity_type );
        if( !$data ){
            throw new Exception( "readEavValue: No result calling ".(!$data?"getEntityMetaData":"getAttributeInfo") );
        }

        $tbl = strendswith($data["table"],"_entity") ? $data["table"] : $data["table"] . "_entity";
        $info = self::getAttributeInfo( $key, $entity_type );
        if( !$info ){
            // Is it a static column?
            if( !Icommerce_Db::columnExists($tbl,$key) ){
                throw new Exception( "readEavValue: No result calling - getAttributeInfo [and not static]" );
            }
        }

        // Prepare store ID
        $sid = Icommerce_Default::prepareStoreId( $store_id );
        if( is_null($sid) ){
            throw new Exception( "readEavValue: Coult not resolve store - $store_id" );
        }

        $val = null;
        try {
            if( !$info || $info["backend_type"]=="static" ){
                // Static case
                $sql = "SELECT $key FROM $tbl WHERE entity_id=$entity_id";
                $val = Icommerce_Db::getValue( $sql );
            } else {
                // EAV case
                $tbl_eav = $tbl . "_".$info["backend_type"];
                $aid = $info["attribute_id"];
                if( $data["only_global"] ){
                    $sql = "SELECT value FROM $tbl_eav WHERE entity_id=$entity_id AND attribute_id=$aid";
                } else {
                    $sql_sid = $sid ? "AND store_id IN ($sid,0)" : "AND store_id=$sid";
                    $sql = "SELECT store_id,value FROM $tbl_eav WHERE entity_id=$entity_id AND attribute_id=$aid $sql_sid ORDER BY store_id DESC";
                }
                $r = Icommerce_Db::getRead()->query( $sql );
                foreach( $r as $rr ){
                    $val = $rr["value"];
                    break;
                }
            }
        } catch( Exception $e ){
            $x = 1;
            throw new Exception( "readEavValue: Failed reading from EAV database" );
        }

        // Update Magento object if we have one
        if( $obj ){
            self::setDataWithoutChange( $obj, $key, $val );
        }
        return $val;
    }

    /**
     * @static
     * @throws Exception
     * @param $obj_id_arr
     * @param $key
     * @param null $entity_type
     * @param null $store_id
     * @return null
     * @todo Extend description
     */
    static function readEavValues( $obj_id_arr, $key, $entity_type=null, $store_id=null ){
        if( !is_array($obj_id_arr) ) throw new Exception( "readEavValues - ids not array" );
        if( !count($obj_id_arr) ) return array();
        
        $entity_ids = array();
        $obj_ixs = array();
        foreach( $obj_id_arr as $ix => $entity_id ){
            if( $entity_id instanceof Varien_Object ){
                $obj = $entity_id;
                if( !$entity_type ){
                    $entity_type = $obj->getData("entity_type_id");
                }
                $entity_id = $entity_id->getId();
                $obj_ixs[] = $ix;  // Store index of objects, so we can insert read value afterwards
            }
            $entity_ids[$ix] = $entity_id;
        }
        $entity_type = self::getEntityTypeId( $entity_type );
        if( !$entity_type ){
            throw new Exception( "readEavValues: Have no entity_type" );
        }

        // Do the database operation
        $data = self::getEntityMetaData( $entity_type );
        if( !$data ){
            throw new Exception( "readEavValue: No result calling ".(!$data?"getEntityMetaData":"getAttributeInfo") );
        }

        $tbl = strendswith($data["table"],"_entity") ? $data["table"] : $data["table"] . "_entity";
        $info = self::getAttributeInfo( $key, $entity_type );
        if( !$info ){
            // Is it a static column?
            if( !Icommerce_Db::columnExists($tbl,$key) ){
                throw new Exception( "readEavValue: No result calling - getAttributeInfo [and not static]" );
            }
        }

        // Prepare store ID
        $sid = Icommerce_Default::prepareStoreId( $store_id );
        if( is_null($sid) ){
            throw new Exception( "readEavValue: Could not resolve store - $store_id" );
        }

        $vals = array();
        try {
            $eid_str = implode( $entity_ids, "," );
            if( !$info || $info["backend_type"]=="static" ){
                // Static case
                $sql = "SELECT entity_id, $key FROM $tbl WHERE entity_id IN ($eid_str)";
            } else {
                // EAV case
                $tbl_eav = $tbl . "_".$info["backend_type"];
                $aid = $info["attribute_id"];
                if( $data["only_global"] ){
                    $sql = "SELECT value FROM $tbl_eav WHERE entity_id IN ($eid_str) AND attribute_id=$aid";
                } else {
                    $sql_sid = $sid ? "AND store_id IN ($sid,0)" : "AND store_id=$sid";
                    $sql = "SELECT entity_id,value FROM $tbl_eav WHERE entity_id IN ($eid_str) AND attribute_id=$aid $sql_sid ORDER BY entity_id, store_id ASC";
                }
            }
            $vals = Icommerce_Db::getAssociative( $sql );
        } catch( Exception $e ){
            throw new Exception( "readEavValues: Failed reading from EAV database" );
        }

        // Update Magento object if we have any
        foreach( $obj_ixs as $ix ){
            $eid = $entity_ids[$ix];
            $val = isset($vals[$eid]) ? $vals[$eid] : null;
            self::setDataWithoutChange( $obj_id_arr[$ix], $key, $val );
        }
        
        return $vals;
    }


    /**
     * Function to create a new EAV object in Magento, based on a template object.
     *
     * @static
     * @param $entity_type
     * @param $tmplt_entity
     * @param array $special_vals
     * @return null
     */
    static function createEavObject(
            $entity_type, $tmplt_entity,
            $special_vals=array() ){

        if( !$tmplt_entity || is_null($entity_type) ){
            return null;
        }

        if( $tmplt_entity instanceof Varien_Object ){
            $tmplt_entity = $tmplt_entity->getId();
        }

        // Get entity type ID
        $entity_type = self::getEntityTypeId( $entity_type );
        if( is_null($entity_type) ){
            return null;
        }

        // Get metadata about object type
        $data = self::getEntityMetaData( $entity_type );
        if( !$data ){
        }

        // First create a new entry from the base table
        $tbl = strendswith($data["table"],"_entity") ? $data["table"] : $data["table"] . "_entity";
        $obj = Icommerce_Db::getRow( "SELECT * FROM $tbl WHERE entity_id=$tmplt_entity" );
        if( !$obj ){
            return null;
        }

        // Reinsert to get a new entity_id
        unset( $obj["entity_id"] );
        $wr = Icommerce_Db::getDbWrite();
        $rd = Icommerce_Db::getDbRead();
        $vals = implode( ",",Icommerce_Db::wrapQueryValues($obj) );
        $sql = "INSERT INTO $tbl (" .
               implode(",",array_keys($obj)) .
               ") VALUES ( $vals );";
        try{
            $r = $wr->query( $sql );
        } catch( Exception $e ){
            $x = 1;
            return null;
        }
        $entity_id = Icommerce_Db::getValue("SELECT LAST_INSERT_ID()");

        // Now do all the other tables
        foreach( $data["backend_types"] as $btype ){
            if( $btype=="static" ){
                continue;
            }
            $eav_tbl = $tbl . "_" . $btype;
            $rows = $rd->query( "SELECT * FROM $eav_tbl WHERE entity_id=$tmplt_entity;" );
            // And insert for new value
            foreach( $rows as $row ){
                $row["entity_id"] = $entity_id;
                unset( $row["value_id"] );
                $vals = implode( ",",Icommerce_Db::wrapQueryValues($row) );
                $sql = "INSERT INTO $eav_tbl (" .
                       implode(",",array_keys($row)) .
                       ") VALUES ( $vals );";
                try{
                    $r = $wr->query( $sql );
                } catch( Exception $e ){
                    continue;
                }
            }
        }

        // Finally set the special vals
        if( $special_vals ){
            foreach( $special_vals as $k => $v ){
                self::writeEavValue( $entity_id, $k, $v, $entity_type );
            }
        }

        // Done
        return $entity_id;
    }

    /**
     * Deletes an object from EAV database
     * @static
     * @throws Exception
     * @param string $entity_id entity to delete
     * @param null|string $entity_type the eav type of object (if not given as Varien_Object)
     * @return bool true if successful object delete
     * @todo Are the description accurate?
     */
    static function exists( $entity_id, $entity_type=null ){
        $obj = null;
        $entity_type = self::getEntityTypeIdOf( $entity_id, $entity_type, $obj, $entity_id );
        if( !$entity_type ){
            throw new Exception( "deleteEavObject: Have no entity_type" );
        }

        // Do the database operation
        $data = self::getEntityMetaData( $entity_type );
        if( !$data ){
            throw new Exception( "deleteEavObject: No result calling ".(!$data?"getEntityMetaData":"getAttributeInfo") );
        }

        $tbl = strendswith($data["table"],"_entity") ? $data["table"] : $data["table"] . "_entity";

        return Icommerce_Db::getValue( "SELECT entity_id FROM $tbl WHERE entity_id=$entity_id" )!==null;
    }

    /**
     * Deletes an object from EAV database
     *
     * @static
     * @throws Exception
     * @param string $entity_id entity to delete
     * @param null|string $entity_type the eav type of object (if not given as Varien_Object)
     * @return bool true if successful object delete
     */
      static function deleteEavObject( $entity_id, $entity_type=null ){
        $obj = null;
        $entity_type = self::getEntityTypeIdOf( $entity_id, $entity_type, $obj, $entity_id );
        if( !$entity_type ){
            throw new Exception( "deleteEavObject: Have no entity_type" );
        }

        // Do the database operation
        $data = self::getEntityMetaData( $entity_type );
        if( !$data ){
            throw new Exception( "deleteEavObject: No result calling ".(!$data?"getEntityMetaData":"getAttributeInfo") );
        }

        // First create a new entry from the base table
        $tbl = strendswith($data["table"],"_entity") ? $data["table"] : $data["table"] . "_entity";

        $wr = Icommerce_Db::getWrite();
        $wr->query( "DELETE FROM $tbl WHERE entity_id=$entity_id; " );

        // Delete the entity from each table
        // Now do all the other tables
        foreach( $data["backend_types"] as $btype ){
            if( $btype=="static" ){
                continue;
            }
            $eav_tbl = $tbl . "_" . $btype;
            $wr->query( "DELETE FROM $eav_tbl WHERE entity_id=$entity_id;" );
        }

        return true;
    }

    /**
     * Get attribute set from attribute set name and entity type
     *
     * You can have custom attributes on any kind of entity, see dbtable: eav_entity_type
     * Attributes are bundled into sets of attributes. You can then use a set of
     * attributes on an entity like product or order.
     *
     * @static
     * @param string $aset_name Name of attribute set
     * @param string $entity_type Name of entity type, example: "catalog_product"
     * @return null|int
     */
    static function getAttributeSetId( $aset_name, $entity_type="catalog_product" ){
        $entity_type = self::getEntityTypeId( $entity_type );
        if( !$entity_type ) return null;
        return Icommerce_Db::getValue( 'SELECT attribute_set_id FROM eav_attribute_set WHERE attribute_set_name LIKE ? AND entity_type_id=?', array($aset_name,$entity_type) );
    }

    /**
     * Create an attribute set based on an existing one.
     *
     * @static
     * @param string $aset_name Name of attribute set
     * @param string $template_aset_name Name of attribute set to be used as "skeleton" (copy tabs/groups from there)
     * @param string $entity_type Name of entity type, example: "catalog_product"
     * @return null|int
     */
    static function createAttributeSet( $aset_name, $template_aset_name, $entity_type="catalog_product" ){
        if( !($entity_type_id = self::getEntityTypeId($entity_type)) ){
            Mage::throwException( "Icommerce_Eav::createAttributeSet -  Unknown entity type: " . $entity_type );
        }
        if( self::getAttributeSetId($aset_name,$entity_type) ){
            Mage::throwException( "Icommerce_Eav::createAttributeSet -  Attribute set by this name already exists." . $aset_name );
        }
        if( !($template_aset_id = self::getAttributeSetId($template_aset_name,$entity_type)) ){
            Mage::throwException( "Icommerce_Eav::createAttributeSet -  Failed looking up temaple attribute set: " . $template_aset_name );
        }
        $model = Mage::getModel('eav/entity_attribute_set')->setEntityTypeId($entity_type_id);
        $model->setAttributeSetName($aset_name);
        $model->save();
        $model->initFromSkeleton($template_aset_id);
        $model->save();
        return $model->getId();
    }

    /**
     * Checks if an attribute exists in a specific attribute set, 4 is default for products in 1.4 and up.
     * The 4 should be looked up as well... perhaps another function :)
     *
     * @static
     * @param string $attribute_code
     * @param string $attribute_set
     * @param string $entity_type
     * @return bool true if it exists
     */
    static function isAttributeInSet($attribute_code,$attribute_set,$entity_type = "catalog_product" )
    {
        $entity_type_id = self::getEntityTypeId( $entity_type );
        $attribute_set_id = self::getAttributeSetId( $attribute_set, $entity_type_id );
        $read = Icommerce_Db::getRead();
        $select = $read->select()
                        ->from(array('set'=>'eav_entity_attribute'))
                        ->join(array('attr'=>'eav_attribute'),
                               'set.attribute_id = attr.attribute_id',
                               array())
                        ->where('set.attribute_set_id=?',$attribute_set_id)
                        ->where('attr.attribute_code=?',$attribute_code)
                        ->where('attr.entity_type_id=?',$entity_type_id);
        $result = $read->fetchRow($select);
        if (!$result) return false;
        return true;
    }


    /**
     * @static Get attributes in set
     * @param int|string $attribute_set_id
     * @param array $options  example: array('is_configurable' => '1', 'is_global' => '1')
     * @param array $extra_eav_criteria example array('backend_type' => 'int', 'frontend_input' => 'select')
     * @param string $entity_type Name of entity type, example: "catalog_product"
     * @return array
     */
    static function getAttributesInSet($attribute_set_id, $options = array(), $extra_eav_criteria = array(), $entity_type = "catalog_product" )
    {
        $entity_type_id = self::getEntityTypeId($entity_type);
        if (!Icommerce_Utils::isInteger($attribute_set_id)) {
            $attribute_set_id = self::getAttributeSetId($attribute_set_id, $entity_type_id);
        }
        if (empty($entity_type_id) || empty($attribute_set_id)) {
            return array();
        }
        $read = Icommerce_Db::getRead();
        $select = $read->select()
            ->from(array('set'=>'eav_entity_attribute'))
            ->join(array('attr'=>'eav_attribute'),
                'set.attribute_id = attr.attribute_id',
                array('attribute_code'))
            ->where('set.attribute_set_id=?', $attribute_set_id)
            ->where('attr.entity_type_id=?',  $entity_type_id);

        if (!empty($extra_eav_criteria )) {
            foreach ($extra_eav_criteria as $option => $val) {
                $select->where("attr.{$option} = ?", $val);
            }
        }
        if (!empty($options)) {
            $prefix = 'attr';
            if (Icommerce_Default::getMagentoVersion() >= 1400) {
                $select->join(array('cea' => 'catalog_eav_attribute'),
                    'set.attribute_id = cea.attribute_id', array());
                $prefix = 'cea';
            }
            foreach ($options as $option => $val) {
                $select->where("{$prefix}.{$option} = ?", $val);
            }
        }

        $result = (array )$read->fetchAll($select);

        return $result;
    }

    
    /**
     * @static Put the given attribute into a named group, for all attribute sets (where group exists)
     *
     * @param int|string $acode The attribute code
     * @param string $group Name of group to put it in
     * @param string $entity_type Name of entity type, example: "catalog_product"
     * @return array
     */
    static function putAttributeInAllSets( $acode, $group, $entity_type="catalog_product" ){
        if( !($entity_type_id = self::getEntityTypeId($entity_type)) ){
            Mage::throwException( "Icommerce_Eav::putAttributeInAllSets -  Unknown entity type: " . $entity_type );
        }
        if( !($aid = self::getAttributeId($acode,$entity_type_id)) ){
            Mage::throwException( "Icommerce_Eav::putAttributeInAllSets -  Unknown attribute: " . $acode );
        }
        $set_ids = Icommerce_Db::getColumn( "SELECT attribute_set_id FROM eav_attribute_set WHERE entity_type_id=?",
                                             array($entity_type_id) );
        if( !$set_ids ) return;
        $set_id_str = implode(",",$set_ids);
        $groups_ids = Icommerce_Db::getAssociative( "SELECT attribute_set_id, attribute_group_id FROM eav_attribute_group
                                                      WHERE attribute_set_id IN ($set_id_str) AND attribute_group_name=?",
                                                      array($group) );
        // Cannot have duplicates, so remove first
        $gids = implode(",",$groups_ids);
        Icommerce_Db::write( "DELETE FROM eav_entity_attribute WHERE attribute_id=? AND attribute_group_id NOT IN ($gids)", array($aid) );
        foreach( $groups_ids as $set_id => $group_id ){
            // No need to insert if already there
            if( !Icommerce_Db::getValue( "SELECT entity_attribute_id FROM eav_entity_attribute WHERE attribute_id=? AND
                                          attribute_set_id=? AND attribute_group_id=?", array($aid, $set_id,$group_id) ) ){
                Icommerce_Db::write( "INSERT INTO eav_entity_attribute (entity_type_id,attribute_set_id,attribute_group_id,attribute_id,sort_order)
                                      VALUES (?,?,?,?,100)",
                                      array($entity_type_id,$set_id,$group_id,$aid) );
            }
        }
    }

    static function setAttributeLabel( $acode, string $label, $store=0, $entity_type="catalog_product" ){
        if( !($aid = self::getAttributeId( $acode, $entity_type )) ) return false;

        $store_id = Icommerce_Default::getStoreId($store);
        if( $store_id ){
            // For store_ids, the attribute label is stored in separate table.
            // ON DUPLICATE KEY UPDATE does not work in this table, so we have to delete first
            Icommerce_Db::write( "DELETE FROM eav_attribute_label WHERE attribute_id=? AND store_id=?", array($aid,$store_id) );
            Icommerce_Db::write( "INSERT INTO eav_attribute_label (attribute_id, store_id, value)
                                  VALUES (?,?,?)", array($aid,$store_id,$label) );
            //                      ON DUPLICATE KEY UPDATE value=?",
            //                      array($aid,$store_id,$label,$label) );
        } else {
            // For store_ids, the attribute label is stored in other table
            Icommerce_Db::write( "UPDATE eav_attribute SET frontend_label=? WHERE attribute_id=?", array($label,$aid) );
        }
        return true;
    }

}
