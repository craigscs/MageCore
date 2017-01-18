<?php

class Icommerce_Utils
{
    const TRIGGER_STATUS_NONE = 0;
    const TRIGGER_STATUS_SUCCEEDED = 1;
    const TRIGGER_STATUS_FAILED = 2;
    const TRIGGER_STATUS_EXCEPTIONS = 3;
    const TRIGGER_STATUS_NOTHING_TO_DO = 4;

    /**
     * Test whether the parameter specifies an integer. If the parameter is given as a string, the integer representation of the string is tested
     *
     * @static
     * @param  string|int $num A number, either as string or int
     * @return boolean
     */
    static function isInteger($num)
    {
        // On Linux, array() becomes an integer otherwise
        if( is_array($num) || is_object($num) ) return false;
        return !strcmp($num, (int)$num);
    }


    static function urlify($url){
       	$url = preg_replace("/Å/i",'a',$url);
        $url = preg_replace("/Ä/i",'a',$url);
        $url = preg_replace("/Ö/i",'o',$url);
        $url = preg_replace("/å/i",'a',$url);
        $url = preg_replace("/ä/i",'a',$url);
        $url = preg_replace("/ö/i",'o',$url);
        $url = preg_replace("/[^a-z0-9\-]/i",'-',$url);
        $url = preg_replace('/--+/','-',$url);
        $url = preg_replace('/^-/','',$url);
        $url = preg_replace('/-$/','',$url);

        return strtolower($url);
    }

    /**
     * Truncates string to given length (can add a suffix, works with html tags as well)
     *
     * @static
     * @param  string $string String to truncate
     * @param  int $length Hom many characters to include
     * @param  string $suffix What to add after string has been truncated
     * @param  boolean $isHtml If text contains html or not
     * @return string
     */
    static function truncate($string, $length, $isHtml = false, $suffix = '...', $encoding = 'utf-8')
    {
        $i = 0;
        $tags = array();

        if ($isHtml) {

            preg_match_all('/<[^>]+>([^<]*)/', $string, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

            foreach ($m as $o) {
                if ($o[0][1] - $i >= $length)
                    break;
                $t = substr(strtok($o[0][0], " \t\n\r\0\x0B>"), 1);
                if ($t[0] != '/')
                    $tags[] = $t;
                elseif (end($tags) == substr($t, 1))
                    array_pop($tags);
                $i += $o[1][1] - $o[0][1];
            }
        }

        return mb_substr($string, 0, $l = min(mb_strlen($string, $encoding), $length + $i), $encoding) . (mb_strlen($string, $encoding) > $length ? $suffix : '') . (count($tags = array_reverse($tags)) ? '</' . implode('></', $tags) . '>' : '');
    }

    static public function truncateHtml($html, $length, $ending = '...', $fix_html = true)
    {
        $encoding = 'UTF-8';

        $html = html_entity_decode($html, ENT_QUOTES, $encoding);
        if (mb_strlen($html, $encoding) <= $length) {
            return $html;
        }
        $sanitizedInput = str_replace(array('<BR>', '<BR/>', '<BR />', '<br>', '<br/>', '<br />'), "\n", str_replace(array("\r\n", "\n"), '' , $html));

        do {
            $cnt = preg_match_all('/(<span([^>]*)>)([^<]*)<\/span>/i', $sanitizedInput, $matches);
            if ($cnt && !empty($matches[0])) {
                $newText = $sanitizedInput;
                foreach ($matches[0] as $idx => $item) {
                    $is_bold = false;
                    $is_underline = false;
                    $is_italic = false;
                    if ($style = stristr($matches[2][$idx], 'style')) {
                        $is_bold = strstr($style, 'bold') != false;
                        $is_underline = strstr($style, 'underline') != false;
                        $is_italic = strstr($style, 'italic') != false;
                    }
                    $sub_text = $matches[3][$idx];
                    if ($is_bold) $sub_text = '<b>' . $sub_text . '</b>';
                    if ($is_underline) $sub_text = '<u>' . $sub_text . '</u>';
                    if ($is_italic) $sub_text = '<i>' . $sub_text . '</i>';
                    $newText = str_replace($item, $sub_text, $newText);
                }
                $sanitizedInput = $newText;
            }
        } while($cnt > 0);


        $sanitizedInput = str_replace('</b><b>', '', $sanitizedInput);
        $sanitizedInput = preg_replace('/<\/p>(\n)?<p>/i', "\n\n", $sanitizedInput);

        $sanitizedInput = (mb_substr($sanitizedInput, 0, $length, $encoding));

        // check if last tag is closed
        $right_lt = mb_strrpos($sanitizedInput, '<', null, $encoding);
        $right_gt = mb_strrpos($sanitizedInput, '>', null, $encoding);
        if ($right_lt > $right_gt) {
            $len = mb_strlen($sanitizedInput, $encoding);
            $left_chars = mb_substr($sanitizedInput, $right_lt, $len, $encoding);
            $check_chars = substr($left_chars, 0, 2);
            if ($check_chars != '< ') {
                $sanitizedInput = mb_substr($sanitizedInput, 0, $right_lt, $encoding);
            }
        }

        $sanitizedInput = str_replace("\n\n", '</p><p>', $sanitizedInput);
        $sanitizedInput = str_replace("<p>\n", '<p>', $sanitizedInput);
        $sanitizedInput = str_replace("\n", '<br />', $sanitizedInput);

        $sanitizedInput .= $ending;
        if ($fix_html) {
            $sanitizedInput = self::fixXhtml($sanitizedInput);
        }
        $sanitizedInput = str_replace(
            array('<b>', '</b>', '<i>', '</i>', '<u>', '</u>'),
            array('<strong>', '</strong>', '<em>', '</em>', '<span style="text-decoration:underline;"', '</span>'),
            $sanitizedInput
        );

        return $sanitizedInput;
    }

    static protected function fixXhtml($content)
    {
        $xhtml = '';
        if (class_exists('tidy', false)) {
            $tidy = new tidy;
            $config = array(
                'output-xhtml' => true,
                'show-body-only' => true,
                'quote-nbsp' => false
            );
            $tidy->parseString($content, $config, 'utf8');
            $tidy->cleanRepair();
            $xhtml = (string) $tidy;
        } elseif (@class_exists('Icommerce_HTMLTidy')) {
            $config = array(
                'tidy'=>1,
                // Not allowing elements 'script' and 'object'
                'elements'=>'* -script -object',
                // Not allowing attributes 'id' and 'style'
                'deny_attribute'=>'id',
                'keep_bad' => 0,
                'valid_xhtml'=>1,
            );
            $html_tidy = new Icommerce_HTMLTidy();
            $xhtml = $html_tidy->htmLawed($content, $config);
        } else {
            $content = str_replace(array('<BR>', '<BR/>', '<BR />', '<br>', '<br/>', '<br />'), "\n", str_replace(array("\r\n", "\n"), '' , $content));
            $xhtml = nl2br(strip_tags($content));
        }

        return $xhtml;
    }

    static function getProcessedRequestUri( ){
        $uri = $_SERVER["REQUEST_URI"];
        $script_path = explode( "/", $_SERVER["SCRIPT_NAME"] );
        for( $ix=0; $ix<count($script_path); $ix++ ){
            $sp = $script_path[$ix];
            if( substr($uri,0,strlen($sp)+1)=="$sp/" ){
                $uri = substr($uri,strlen($sp)+1);
                continue;
            }
            break;
        }
        return $uri;
    }

    static function getBrowserInfo()
    {
        $name = "unknown";
        $version = "0.0";
        $browsers = array("firefox", "msie", "opera", "chrome", "safari",
                            "mozilla", "seamonkey",    "konqueror", "netscape",
                            "gecko", "navigator", "mosaic", "lynx", "amaya",
                            "omniweb", "avant", "camino", "flock", "aol");

        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        foreach($browsers as $browser)
        {
            if (preg_match("#($browser)[/ ]?([0-9.]*)#", $agent, $match))
            {
                $name = $match[1] ;
                $version = $match[2] ;
                break ;
            }
        }
        return array("browser" => $name, "version" => $version);
    }


    /**
     * This is support for a request counter, an integer icreased by one on each call to index.php
     * @return int
     */
    static function requestCounterGet( ){
        return Icommerce_Log::requestCounterGet();
    }

    /**
     * Open a numbered logfile, based on request counter,
     *
     * @static
     * @param  string $log_path Name of log file - purpose specific
     * @param  boolean $only_if_opened Set to true if only opened if request counter already initialized
     * @return file
     */
    static function requestCounterGetOpenLog( $log_path, $only_if_opened=true, $log_path_suffix="" ){
        return Icommerce_Log::requestCounterGetOpenLog( $log_path, $only_if_opened, $log_path_suffix );
    }

    static function logAppendBT( $msg, $file ){
        return Icommerce_Log::logAppendBT( $msg, $file );
    }

    /**
     * Use this function to output trigger log in a format that Scheduler understands.
     * When Scheduler recognizes this format, then it creates records to scheduler/message table.
     *
     * @static
     * @param int $status
     * @param string $message
     * @return string
     */
    public static function getTriggerLine($status, $message)
    {
        return date('Y-m-d H:i:s') . "\t" . $status . "\t" . $message;
    }

    /**
     * This function returns xml with trigger result.
     * Use this to tell Scheduler how your task completed.
     *
     * @static
     * @param int $status
     * @param string $message
     * @return string
     */
    public static function getTriggerResultXml($status, $message, $parameters = array())
    {
        $xml = '<trigger_result>'
             . '<status>' . (int)$status . '</status>'
             . '<message>' . htmlentities($message) . '</message>'
             . '<memory_usage>' . memory_get_usage() . '</memory_usage>';

        if (count($parameters)) {
            $xml .= '<parameters>';
            foreach ($parameters as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                if ($value) {
                    $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
                } else {
                    $xml .= '<' . $key . ' />';
                }
            }
            $xml .= '</parameters>';
        }

        $xml .= '</trigger_result>';

        return $xml;
    }

    /**
     * Checks if line is trigger line.
     * Returns true if $line is result xml and updates $line to result array.
     *
     * @static
     * @param string $line
     * @return bool
     */
    public static function parseTriggerLine(&$line)
    {
        $parts = explode("\t", $line, 3);

        if (is_array($parts) && count($parts) == 3) {
            $line = array(
                'created_at' => trim($parts[0]) ? trim($parts[0]) : '0000-00-00 00:00:00',
                'status' => (int)$parts[1],
                'message' => trim($parts[2]),
            );

            return true;
        }

        return false;
    }

    /**
     * Checks if line is trigger result.
     * Returns true if $line is result xml and updates $line to result array.
     *
     * @static
     * @param $line
     * @return bool
     */
    public static function parseTriggerResultXml(&$line)
    {
        $result = trim($line);

        if (substr($result, 0, 16) == '<trigger_result>' && substr($result, -17) == '</trigger_result>') {
            $xml = simplexml_load_string($result);

            $line = array(
                'status' => (int)$xml->status,
                'message' => (string)$xml->message,
                'memory_usage' => (int)$xml->memory_usage,
            );

            if (isset($xml->parameters)) {
                foreach ((array)$xml->parameters as $key => $value) {
                    $line['parameters'][$key] = (string)$value;
                }
            } else {
                $line['parameters'] = array();
            }

            return true;
        }

        return false;
    }

    /**
     * Perform an async web request
     * Credit to: http://w-shadow.com/blog/2007/10/16/how-to-run-a-php-script-in-the-background/
     *
     * @static
     * @param  string $url Url to fetch
     * @param  array $params GET / POST params
     * @param  $string $type GET / POST request
     * @return file
     */
    static function curlAsync($url, $params, $type="GET" )
    {
        $post_params = array();
        foreach ($params as $key => &$val) {
          if (is_array($val)) $val = implode(',', $val);
            $post_params[] = $key.'='.urlencode($val);
        }
        $post_string = implode('&', $post_params);

        $parts=parse_url($url);
        if($type == 'GET' ) $parts['path'] .= '?'.$post_string;

        $fp = fsockopen($parts['host'],
            isset($parts['port'])?$parts['port']:80,
            $errno, $errstr, 30);
        if( !$fp ) return false;

        $out = "$type ".$parts['path']." HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out.= "Connection: Close\r\n";
        $out.= "Content-Length: ".strlen($post_string)."\r\n\r\n";
        if (isset($post_string)) $out.= $post_string;

        fwrite($fp, $out);
        fclose($fp);

        return true;
    }

    static public function create_guid($namespace = '') {
        $guid = '';
        $uid = uniqid('', true);
        $data = $namespace;
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['LOCAL_ADDR'];
        $data .= $_SERVER['LOCAL_PORT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $data .= $_SERVER['REMOTE_PORT'];
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = '{' .
                substr($hash,  0,  8) .
                '-' .
                substr($hash,  8,  4) .
                '-' .
                substr($hash, 12,  4) .
                '-' .
                substr($hash, 16,  4) .
                '-' .
                substr($hash, 20, 12) .
                '}';
        return $guid;
    }
}
