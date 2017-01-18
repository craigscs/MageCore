<?php 

function breakupFullName( $nm, $empty_name="-" ){ 
    $parts = explode( " ", $nm );
    if( count($parts)<2 )
        return array( 'first' => $nm, 'middle'=>"", 'last'=>$empty_name );
    if( count($parts)==2 )
        return array( 'first' => $parts[0], 'middle'=>"", 'last'=>$parts[1] );
    if( count($parts)==3 )
        return array( 'first' => $parts[0], 'middle'=>$parts[1], 'last'=>$parts[2] );
    
    // Merge two adjacent parts until we have only 3
    while( count($parts)>3 ) {
        $ix_best1 = $ix_best2 = -1;
        $l_best = 1000;
        $ix_last = -1;
        foreach( $parts as $ix => $p ){
            if( $ix_last>=0 && strlen($parts[$ix_last])+strlen($p)<$l_best ){
                $l_best = strlen($parts[$ix_last]) + strlen($p);
                $ix_best1 = $ix_last;
                $ix_best2 = $ix;
            }
            $ix_last = $ix;
        }
        // Merge two
        $parts[$ix_best1] = $parts[$ix_best1] . " " . $parts[$ix_best2];
        unset( $parts[$ix_best2] );
    }
    
    // Renumber the array
    $ix = 0; 
    $parts2 = array();
	foreach( $parts as $k => $v ){
		$parts2[$ix++] = $v;
	}
    return array( 'first' => $parts2[0], 'middle' => $parts2[1], 'last' => $parts2[2] );
}

// Drop in replacement for mageParseCsv. On testing this function, I found that the
// Magento variant has problems with this string (generates 1 too much entry, and wrong value on last real entry):
// '"abc",222"22,d"ef,"de,",123,"4ssss579,ab,cd,",'
function csvSplit( $s, $delim=",", $enc='"' ){
    $parts = explode( $delim, $s );
    $r = array();
    $cnt = count($parts);
    for( $ix=0; $ix<$cnt; $ix++ ){
        $part = $parts[$ix];
        if( strpos($part,$enc)!==FALSE ){
            if( ($n = substr_count($part,$enc))&1 ){
                for( $jx = $ix+1;$jx<$cnt; $jx++ ){
                    $part .= $delim.$parts[$jx];
                    if( substr_count($parts[$jx],$enc)&1 ){
                        break;
                    }
                }
                $ix = $jx;
            }
            $r[] = str_replace( $enc, '', $part );
        } else {
            $r[] = $part;
        }
    }
    return $r;
}

