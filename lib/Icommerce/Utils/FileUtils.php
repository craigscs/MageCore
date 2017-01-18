<?php

// NOTE: We depend on www-data having write permissions in the 
// target directory. 

function toUtf8( $path, $file=null, $src_encoding="WINDOWS-1252" ){
	if( strlen($path)>1 ){
		$path = rtrim( $path, "/");
		if( substr($path,0,7)=="file://" )
			$path = substr($path,7);
	}
    if( !$file ){
    	// Get file as last component of path
        $pa = explode( "/", $path );
        if( !$pa ) return null;
        $file = $pa[count($pa)-1];
        unset( $pa[count($pa)-1]);
        $path = implode( "/", $pa );
    }
    // Get encoding
    $fullpath = $path."/".$file;
    $r = shell_exec( "file ".$fullpath );
    if( !$r ) return null;
    
    // Remove filename echo
    $r = substr( $r, strlen($fullpath)+1 );
    // Error ?
    if( strpos("ERROR:",$r)>0 )
    	return FALSE;
    
    // Already UTF8?
    if( strpos($r,"UTF-8")!==FALSE || strpos($r,"UTF8")!==FALSE )
    	return TRUE;
    
    #$cmd = "ls ".$fullpath;
    #$r = shell_exec( $cmd );	
    
    // We need to encode it - backup first
    $bu_path = $path."/".$file.".backup";
    $cmd = "cp ".$fullpath." ".$bu_path;
    $r = shell_exec( $cmd );
    if( !file_exists($bu_path) )
    	return FALSE;
    
    // Do conversion
    //$cmd = "iconv -f=LATIN1 -t=UTF8 ".$fullpath." >".$fullpath.".tmp";
    $cmd = "iconv -f=".$src_encoding." -t=UTF8 ".$fullpath." >".$fullpath.".tmp";
    $r = shell_exec( $cmd );
    if( !file_exists($fullpath.".tmp") )
    	return FALSE;
    
    // Overwrite original
    $cmd = "mv ".$fullpath.".tmp " .$fullpath;
    $r = shell_exec( $cmd );
    if( file_exists($fullpath.".tmp") )
    	return FALSE;
    
    // Change permissions
    $r = shell_exec( "chmod 777 ".$fullpath );
    
    return TRUE;
}


// Same as above, but store in a .utf8 extended filename
function toUtf8_2( $path, $file=null, $src_encoding="cp437" ){
	if( strlen($path)>1 ){
		$path = rtrim( $path, "/");
		if( substr($path,0,7)=="file://" )
			$path = substr($path,7);
	}
    if( !$file ){
    	// Get file as last component of path
        $pa = explode( "/", $path );
        if( !$pa ) return null;
        $file = $pa[count($pa)-1];
        unset( $pa[count($pa)-1]);
        $path = implode( "/", $pa );
    }
    $fullpath = $path."/".$file;
    
    // Do conversion
    //$cmd = "iconv -f=LATIN1 -t=UTF8 ".$fullpath." >".$fullpath.".tmp";
    $cmd = "iconv -f=".$src_encoding." -t=UTF8 ".$fullpath." >".$fullpath.".utf8";
    $r = shell_exec( $cmd );
    if( !file_exists($fullpath.".utf8") )
    	return FALSE;
    
    return TRUE;
}


function file2str( $file ){
	ob_start();
	$r = readfile( $file );
	$s = ob_get_contents();
	ob_end_clean();
	return $r===FALSE ? $r : $s;
}

?>