<?php
/**
 * Created by JetBrains PhpStorm.
 * User: arne
 * Date: 2013-01-29
 * Time: 11.19
 * To change this template use File | Settings | File Templates.
 */

class Icommerce_Html {

    const HTML_FIRST_LINE_V4 = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';

    /**
     * The function will look forwards in HTMl after the opening and matched close of a given tag
     * @static
     * @param $html Html string to search
     * @param $tag The tag (div / span / ul / ...)
     * @return array|bool An array with start position and length of the recursively (or singly) closed tag
     */
    static function findTagStartStopNested( $html, $tag ){
        $mv = array();
        $r = preg_match( "@<\\s*${tag}(\\s[^>]*)?>@", $html, $mv );
        if( !$r ) return false;
        $p_start = strpos($html,$mv[0]);
        $l_match = strlen($mv[0]);
        $p_inner = $p_start + $l_match;
        $l_inner = 0;

        // Tag closes immediately?   -  <div id='abc' ... />
        if( preg_match("@/\\s*>@",substr($html,$p_start,$l_match)) ){
            return array( $p_start, $l_match, null );
        }

        // ### This is not quite correct as we don't take into account, whether next "start tag" comes before or after the "close tag"

        // Is there a nested tag ?
        $r = self::findTagStartStopNested( substr($html,$p_inner), $tag );
        if( $r ) {
            $l_inner = $r[0]+$r[1];
            $l_match += $l_inner;
        }

        // Find the end tag
        $html_tail = substr($html,$p_inner+$l_inner);
        $r = preg_match("@<\\s*/\\s*${tag}\\s*>@",$html_tail,$mv);
        if( !$r ) return false;
        $l_match += strpos($html_tail,$mv[0]) + strlen($mv[0]);

        // Also return the position+length of the inner HTML
        return array($p_start,$l_match,array($p_inner,$l_inner));
    }

    /**
     * The function will look forwards in HTMl after the opening and matched close of a given tag
     * @static
     * @param $html Html string to search
     * @param $tag The tag (div / span / ul / ...)
     * @return array|bool An array with start position and length of the recursively (or singly) closed tag
     */
    static function findTagStartStop( $html, $tag ){
        $r = self::findTagStartStopNested( $html, $tag );
        return $r ? array( "start"=>$r[0], "stop"=>$r[1], "inner"=>$r[2] ) : false;
    }

    /**
     * The function will look forwards in HTMl for a tag with the given ID
     * @param $html Html string to search
     * @param $id The ID to search for
     * @param $attr The attribute that should equal this (defaults to "id")
     * @return array|bool Array with start position, length of whole match, and description of inner html (start+length)
     */
    static function findIdTag( $html, $id, $attr="id" ){

        // Reg ex search, for: "<sometag ... id='theid' ...>
        $mv = array();
        $r = preg_match( "@<\\s*([\\w_:]+)(\\s[^>]*)?\\s${attr}=['\"]${id}['\"][^>]*>@", $html, $mv );
        if( !$r ) return false;

        // Extract out the tag
        $tag = $mv[1];
        $p_start = strpos( $html, $mv[0] );

        // Recursively find end of tag
        return self::findTagStartStop( substr($html,$p_start), $tag );
    }


    /**
     * The function will look backwards in given HTML, for the start of the tag (matching nested tags between)
     * @static
     * @param $html Html string to search
     * @param $tag The tag (div / span / ul / ...)
     * @return bool|int
     */
    static function findReverse( $html, $tag ){
        // Consume HTML in reverse directon
        $mv = array();
        while( true ){
            // Text ?
            if( preg_match("@[^>]+$@",$html,$mv) ){
                $html = substr($html,0,-strlen($mv[0]));
            }
            // Comment ?
            else if( preg_match("@<!--.*-->$@U",$html,$mv) ){
                $html = substr($html,0,-strlen($mv[0]));
            }
            // Self closed tag ?
            else if( preg_match("@<\\s*(\\w+)(\\s+[^>]*)*/>$@",$html,$mv) ){
                $html = substr($html,0,-strlen($mv[0]));
                if( $mv[1]==$tag ){
                    return strlen($html);
                }
            }
            // Closing tag ?
            else if( preg_match("@<\\s*/\\s*(\\w+)\\s*>$@",$html,$mv) ){
                $p = self::findReverse( substr($html,0,-strlen($mv[0])), $mv[1] );
                if( $p===false ) return false;
                $html = substr($html,0,$p);
            }
            // Opening tag ?
            else if( preg_match("@<\\s*(\\w+)(\\s+[^>]*)*>$@",$html,$mv) ){
                $html = substr($html,0,-strlen($mv[0]));
                if( $tag==$mv[1] ) return strlen($html);
            }
            else {
                // Nothing to do... failed
                return false;
            }
        }
    }

}

