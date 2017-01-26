<?php

class Icommerce_Db {

    static $_read, $_write;

    /**
     * Returns a connection to the default database for reading
     *
     * @static
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    static function getRead(){
        return self::getDbRead( );
    }

    /**
     * Returns a connection to the default database for reading
     *
     * @static
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    static function getDbRead(){
        if( !self::$_read ){
            self::$_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        }
        return self::$_read;
    }

    /**
     * Returns a connection to the default database for writing
     *
     * @static
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    static function getWrite(){
        return self::getDbWrite( );
    }

    /**
     * Returns a connection to the default database for writing
     *
     * @static
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    static function getDbWrite(){
        if( !self::$_write ){
            self::$_write = Mage::getSingleton('core/resource')->getConnection('core_write');
        }
        return self::$_write;
    }

    /**
     * Sends a "null operation" to the database, to keep the connection alive
     *
     * @static
     * @return void
     */
    static function dbKeepalive() {
        $conn_r = self::getDbRead();
        $conn_w = self::getDbWrite();
        if ($conn_r) $conn_r->query("DO 0"); //Note: MySQL specific!
        if ($conn_r!=$conn_w) $conn_w->query("DO 0"); //Note: MySQL specific!
    }

    /**
     * Function to quote all values in the query
     *
     * @static
     * @param mixed $v
     * @return string|int|array
     */
    static $mysql_connected;
    static function wrapQueryValues( $v ){
        $rd = self::getDbRead();

        if( is_array($v) ){
            $r = array();
            foreach( $v as $vv ){
                if( is_string($vv) || is_bool($vv) ){
                    //$vv = str_replace('"','\\"',$vv);
                    //$vv = mysql_real_escape_string($vv);
                    //$r[] = "\"$vv\"";
                    $r[] = $rd->quote( $vv );
                }
                else if( $vv===null ){
                    // Good? "NULL"?
                    $r[] = "NULL";
                    //$r[] = "\"\"";
                }
                else {
                    $r[] = $vv;
                }
            }
            return $r;
        }
        else {
            if( is_string($v) ){
                //$v = str_replace( '"', '\\"', $v );
                //$v = mysql_real_escape_string($v);
                //return "\"$v\"";
                return $rd->quote( $v );
            }
            else if( $v===null ){
                // Good? "NULL"?
                return "NULL";
                //return "\"\"";
            }
            else {
                return $v;
            }
        }
    }

    /**
     * Function to ensure we have an entitry ID after this call
     *
     * @static
     * @param mixed $obj A Varien_Object or already an ID
     * @return string|int
     */
    static function toId( $obj ){
        if( $obj instanceof Varien_Object ){
            return $obj->getId();
        } else if( !strcmp((int)$obj,$obj) ){
            return $obj;
        } else if( !$obj ){
            return "";
        } else {
            throw new Exception( "Icommerce_Db::toId - Unrecognized object:".$obj );
        }
    }

    /**
     * Function to convert an array or comma separated list of values to an array of entity ID:s
     *
     * @static
     * @param array|string $objs The list of objects
     * @return array|string|int
     */
    static function toIdList( $objs ){
        if( is_string($objs) ){
            $objs = explode( ",", $objs );
        } else if( $objs instanceof Varien_Object ){
            $objs = array( $objs->getId() );
        } else if( is_array($objs) ){
            $objs2 = array();
            foreach( $objs as $k => $v ){
                $vv = self::toId( $v );
                if( $vv ){
                    $objs2[] = $vv;
                }
            }
            $objs = $objs2;
        } else if ( !$objs ){
            $objs = array();
        } else {
            throw new Exception( );
        }
        return $objs;
    }

    /**
     * Perform a query against the default database using the write connection
     *
     * @static
     * @param string|Zend_Db_Select $sql The SQL statement with placeholders.
     * @param array $bind An array of data to bind to the placeholders.
     * @return Zend_Db_Pdo_Statement
     */
    public static function write( $sql, $bind=array() ){
        return self::getWrite()->query( $sql, $bind );
    }

    /**
     * Perform a query against the default database using the read connection
     *
     * @static
     * @param string|Zend_Db_Select $sql The SQL statement with placeholders.
     * @param array $bind An array of data to bind to the placeholders.
     * @return Zend_Db_Pdo_Statement
     */
    public static function read( $sql, $bind=array() ){
        return self::getRead()->query( $sql, $bind );
    }

    /**
     * Perform a query and return the first value of it (first row, first column)
     *
     * @static
     * @param string|Zend_Db_Select $sql The SQL statement with placeholders.
     * @param array $bind An array of data to bind to the placeholders.
     * @return string|null
     */
    public static function getValue( $sql, $bind=array() ){
        return self::getDbSingleton( $sql, $bind );
    }

    /**
     * Alias for getValue
     */
    public static function getSingleton( $sql, $bind=array() ){
        return self::getDbSingleton( $sql, $bind );
    }

    /**
     * Alias for getValue
     */
    public static function getDbSingleton( $sql, $bind=array() ){
        $rd = self::getDbRead();
        $r = $rd->query( $sql, $bind );
        if( !$r ) return null;

        foreach( $r as $rr ){
            foreach( $rr as $val ){
                return $val;
            }
        }
        return null;
    }

    /**
     * Perform a query and return the first (and only?) row of the result
     *
     * @static
     * @param string|Zend_Db_Select $sql The SQL statement with placeholders.
     * @param array $bind An array of data to bind to the placeholders.
     * @return array|null
     */
    public static function getRow( $sql, $bind=array() ){
        $rd = self::getDbRead();
        $r = $rd->query( $sql, $bind );
        if( is_null($r) ) return null;

        foreach( $r as $rr ){
            return $rr;
        }
        return null;
    }

    /**
     * Perform a query and return all rows of the result
     *
     * @static
     * @param string|Zend_Db_Select $sql The SQL statement with placeholders.
     * @param array $bind An array of data to bind to the placeholders.
     * @return array|null
     */
    public static function getRows( $sql, $bind=array() ){
        $rd = self::getDbRead();
        $r = $rd->query( $sql, $bind );
        if( is_null($r) ) return null;

        $all_rows = array();
        foreach( $r as $rr ){
            $all_rows[] = $rr;
        }
        return $all_rows;
    }

    /**
     * Perform a query and return the first (and only?) column of the result
     *
     * @static
     * @param string|Zend_Db_Select $sql The SQL statement with placeholders.
     * @param array $bind An array of data to bind to the placeholders.
     * @return array|null
     */
    public static function getColumn( $sql, $bind=array() ){
        $rd = self::getDbRead();
        $r = $rd->query( $sql, $bind );
        if( is_null($r) ) return null;
        if( !$r ) return array();

        // Collect first column from each row
        $arr = array();
        foreach( $r as $rr ){
            foreach( $rr as $el ){
                $arr[] = $el;
                break;
            }
        }

        return $arr;
    }

    /**
     * Perform a query and return an array where the the first selected column is used as key.
     * I.e. "SELECT email, firstname, lastname FROM customer" would return:
     *      array(
     *        'bob@it.se' => ('firstname'=>'Bob', 'latname'=>'Brown')
     *        'joe@home.se' => ('firstname'=>'Johannes', 'latname'=>'Greene')
     *           );
     * or   "SELECT email, age FROM customer" would return:
     *      array(
     *        'bob@it.se' => "28"
     *        'joe@home.se' => "39"
     *           );
     * or   "SELECT email" would return:
     *      array(
     *        'bob@it.se' => true
     *        'joe@home.se' => true
     *           );
     *
     * @static
     * @param string|Zend_Db_Select $sql The SQL statement with placeholders.
     * @param array $bind An array of data to bind to the placeholders.
     * @return array|null
     */
    public static function getAssociativeArray( $sql, $bind=array() ){
        $r = self::read( $sql, $bind );
        $key_column = null;
        $keys = array();
        foreach( $r as $rr ){
            foreach( $rr as $k => $v ){
                if( !$key_column ) $key_column = $k;
                else $keys[] = $k;
            }
            break;
        }
        if( !$key_column ) return array();

        // A very strange behaviour of the result returned by 'read' above is that above iterator loop
        // consumes the first element of the collection, so that it has disappeared in the next foreach loop.
        // Compensate that by building an array in memory instead.
        $R = array( $rr );
        foreach( $r as $rr ){
            $R[] = $rr;
        }

        $rv = array();
        if( count($keys)>1 ){
            foreach( $R as $rr ){
                $row = array();
                foreach( $rr as $k => $v ){
                    $row[$k] = $v;
                }
                $rv[$rr[$key_column]] = $row;
            }
        } else if( count($keys)==1 ) {
            foreach( $R as $rr ){
                $rv[$rr[$key_column]] = $rr[$keys[0]];
            }
        } else {
            foreach( $R as $rr ){
                $rv[$rr[$key_column]] = true;
            }
        }
        
        return $rv;
    }

    /**
     * Alias for getAssociativeArray
     */
    public static function getAssociative( $sql, $bind=array() ){
        return self::getAssociativeArray( $sql, $bind );
    }

    /**
     * Duplicate a row in a table, modifying given columns and accomodating for auto increments.
     * Essentially selects a row, and modifies certain values and store it as a new row in the DB.
     * @static
     * @param string|Zend_Db_Select $sql_select The SQL statement to identify a row and all columns to be copied.
     * @param array $new_vals An array of keys=>values of columns to be modified, in the selected row
     * @param array $bind An array of data to bind to the placeholders.
     * @param $auto_inc_column The name of the auto increment ID column, if any
     * @return true|null
     */
    public static function copyRow( $sql_select, $new_vals, $auto_inc_column=null, $bind=array() ){
        $row = self::getRow($sql_select,$bind);
        if( !$row ) return null;

        // Extract the table name
        $ma = array();
        if( !preg_match("/.* FROM ([a-zA-Z\._]+) /",$sql_select,$ma) ) return null;
        $tbl = $ma[1];

        // Prepare the values
        unset($row[$auto_inc_column]);
        foreach( $new_vals as $col => $v ){
            $row[$col] = $v;
        }

        // Prepare SQL
        $cols = implode( ",", array_keys($row) );
        $args = str_repeat("?,", count($row)-1) . "?";
        $sql = "INSERT INTO $tbl ($cols) VALUES ($args)";
        self::write( $sql, array_values($row) );

        return true;
    }

    /**
     * Change a number of key=>value pairs for a given row in a table.
     * @static
     * @param string $table The table to modify
     * @param array $columns An array key=>values saying what to modify on the row
     * @param string $where Condition saying which row to modify
     * @param array $bind An array of data to bind to the placeholders.
     * @return true|false
     */
    public static function updateRow( $table, $columns, $where, $bind=array() ){
        $sql_sets = array();
        $sql_binds = array();
        foreach( $columns as $c => $v ){
            $sql_sets[] = " $c = ? ";
            $sql_binds[] = $v;
        }
        if( $bind ){
            $sql_binds = array_merge( $sql_binds, $bind );
        }
        $sql = "UPDATE $table SET ".implode(",",$sql_sets) . " WHERE $where " ;
        return self::write( $sql, $sql_binds );
    }

    /**
     * Returns whether a table with given name exists.
     * @static
     * @param string $table The table check for
     * @return true|false
     */
    public static function tableExists( $table ){
        $rd = self::getDbRead();
        $r = $rd->query( "SHOW TABLES LIKE '$table'" );
        foreach( $r as $rr ){
            return true;
        }
        return false;
    }

    /**
     * Returns whether a column in a given table exists.
     * @static
     * @param string $table The table to investigate
     * @param string $column The column check for
     * @return true|false
     */
    public static function columnExists( $table, $column ){
        $rd = self::getDbRead();
        $r = $rd->query( "SHOW COLUMNS FROM `$table` LIKE '$column'" );
        foreach( $r as $rr ){
            return true;
        }
        return false;
    }

    /**
     * Adds a column to a table.
     * @static
     * @param string $table The table to modify
     * @param string $column The name of new column
     * @param string $type The type of the new column
     * @param string|null $length The max length (VARCHAR/...) of the new column values
     * @param string|null $default_val The default value for this column (if any)
     * @param string|null $default_val The default value for this column (if any)
     * @param array|null $enums For enumerable type, the array of enumerable values
     * @return true|false
     */
    public static function addColumn( $table, $column, $type, $length=null, $default_val=null, $enums=null ){
        return self::insertTableColumn( $table, $column, $type, $length, $default_val, $enums );
    }

    /**
     * Alias for addColumn
     */
    public static function insertTableColumn( $table, $column, $type, $length=null, $default_val=null, $enums=null ){
        $wr = self::getDbWrite();
        $sql = "ALTER TABLE `$table` ADD `$column` ";
        if( $type=="enum" ){
            if( is_array($enums) ){
                $enums = self::wrapQueryValues($enums);
                $enums = implode(",",$enums);
            }
            $sql .= "ENUM(" . $enums . ") ";
        }
        else {
            $sql .= $type . " ";
            if( $length ){
                $sql .= "($length) ";
            }
        }
        $sql .= "NOT NULL ";
        if( $default_val ) {
            if( is_string($default_val) ){
                $default_val = "'$default_val'";
            }
            $sql .= "DEFAULT $default_val ";
        }

        $r = $wr->query( $sql );
    }

    /**
     * Returns the next auto increment value for a table
     * @static
     * @param string $table The table to investigate
     * @return sting|null
     */
    public static function getTableNextAutoInc( $table ){
        return Icommerce_Db::getValue( "SELECT auto_increment
                    FROM information_schema.tables
                    WHERE table_name=?
                    AND table_schema = DATABASE();", array($table) );
    }

    /**
     * Returns increment ID for last inserted row
     * @static
     * @return int|null
     */
    public static function getLastInsertId( ){
        return self::getValue( "SELECT LAST_INSERT_ID()" );
    }

    /**
     * Alias for Icommerce_Eav::getAttributeId(...)
     */
    static function getAttributeId( $attrib_code, $entity_type_id=null ){
        return Icommerce_Eav::getAttributeId( $attrib_code, $entity_type_id );
    }

    /**
     * Alias for Icommerce_Eav::getAttributeCode(...)
     */
    static function getAttributeCode($attrib_id){
        return Icommerce_Eav::getAttributeCode($attrib_id);
    }

    /**
     * Alias for Icommerce_Eav::getEntityTypeId(...)
     */
    static function getEntityTypeId( $entity_type_code ){
        return Icommerce_Eav::getEntityTypeId( $entity_type_code );
    }

}
