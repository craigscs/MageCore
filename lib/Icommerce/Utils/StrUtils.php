<?php 
 	// Does a string start with $suffix?
    function strstartswith( $str, $prefix ){
        $plen = strlen($prefix); 
        $slen = strlen($str); 
        if( $plen>$slen )
            return false;
        return substr($str,0,$plen)==$prefix;
    } 

	// Does a string end with $suffix?
    function strendswith( $str, $suffix ){
        $plen = strlen($suffix); 
        $slen = strlen($str); 
        if( $plen>$slen )
            return false;
        return substr($str,$slen-$plen)==$suffix;
    } 
    
	// Find a string inside another one, bounded by $sep (or at the beginning or end of the string)
    function strstrsep( $haystack, $needle, $sep="," ){ 
        if( !$needle ) return 0;
        if( !$haystack ) return FALSE;
        if( !$sep ){
            return strstr( $haystack, $needle );
        }
        $p = strpos( $haystack, $needle );
        if( $p===FALSE ) return FALSE;
        $hl = strlen($haystack);
        $nl = strlen($needle);
		// Check if OK at beginning
		if( $p && $haystack[$p-1]!=$sep ) return FALSE;
		// Check if OK at end
        if( $p+$nl<$hl && $haystack[$p+$nl]!=$sep ) return FALSE;
		// Yes!
        return $p;
    }

	// If start on $comma, drop it, otherwise return the same
    function dropFirstComma($s, $comma=","){
        // No comment
    	if( strlen($s)>0 && $s[0]==$comma )
    		return substr( $s, 1 );
    	else 
    		return $s;	
    }
    
    // Append a suffix to a string if not already there
    function suffix( &$s, $suf ){
        $ls = strlen($s);
        $lsuf = strlen($suf);
        if( $lsuf>$ls || substring($s,$ls-$lsuf)!==$suf )
            $s .= $suf;
        return $s;
    }

	// Convert character to uppercase, with Swedish support
	function chartoupper( $ch ){
		$ch_r = str_replace( array('å','ä','ö'), array('Å','Ä','Ö'), $ch );
		if( $ch_r!==$ch ) return $ch_r;
		return strtoupper( $ch );
    }

	// Convert character to uppercase, with Swedish support
	function chartolower( $ch ){
		$ch_r = str_replace( array('Å','Ä','Ö'), array('å','ä','ö'), $ch );
		if( $ch_r!==$ch ) return $ch_r;
		return strtoupper( $ch );
    }

	// Convert character to uppercase, with Swedish support
	function strtoupper_se( $s ){
		$ch_r = str_replace( array('å','ä','ö'), array('Å','Ä','Ö'), $s );
		return strtoupper( $ch_r );
    }
	
	// Convert character to lowercase, with Swedish support
	function strtolower_se( $s ){
		$ch_r = str_replace( array('Å','Ä','Ö'), array('å','ä','ö'), $s );
		return strtolower($ch_r);
    }	
	
	// Convert swedish ÅÄÖ to AAO
	function strtoiso8( $s ){
		$s2 = str_replace( array('å','ä','ö','Å','Ä','Ö'), array('a','e','o','A','E','O'), $s );
		return $s2;
	}
	
	function valueIsTrue( $s ){
        if( !$s ) return false;
        // 0, no, false, - 
        if( strpos("0nNfF-",$s[0])!==FALSE ) return false;
        return true;
    }
    