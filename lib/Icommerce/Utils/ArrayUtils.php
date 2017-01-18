<?php 

    // Removes all keys in $needles from $haystack
    function array_remove_elems( $haystack, $needles ){
        foreach( $needles as $v ){
        	$ix = array_search( $haystack, $v );
        	if( $ix!==FALSE ){
            	unset( $haystack[$ix] );
        	}
        }
        return $haystack;
    }
    
    // Removes all $keys in $haystack from $haystack
    function array_remove_keys( $haystack, $keys ){
        foreach( $keys as $n ){
            unset( $haystack[$n] );
        }
        return $haystack;
    }
    
    // Insert an element first in an array (in place)
    function array_move_first( &$arr, $key ){
        if( !array_key_exists($key,$arr) ){
            throw new Exception("array_move_first - key does not exist");
        }
        $arr_tmp = array();
        foreach( $arr as $k => $v ){
            $arr_tmp[$k] = $v;
            unset( $arr[$k] );
        }
        
        // Put first
        $arr[$key] = $arr_tmp[$key];

        // Re-insert all others
        foreach( $arr_tmp as $k => $v ){
            if( $k!==$key ){
                $arr[$k] = $arr_tmp[$k];
            }
        }
    }
    
    // Move an element first in an array (in place)
    function array_insert_first( &$arr, $key, $val ){
        $arr[$key] = $val;
        array_move_first( $arr, $key );
    }    
    