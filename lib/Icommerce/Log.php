<?php

class Icommerce_Log
{

    /**
     * End-Of-Line constant
     */
    const EOL = "\n";

    /**
     * Log file used by the logRequestBegin and logRequestEnd functions
     */
    const LOG_REQUESTS_FILE = 'var/request-log.log';

    /**
     * Log file used by the logExecutionState function
     */
    const LOG_EXECUTION_STATE_FILE = 'var/execution-state.log';

    /**
     * Wait period before function logExecutionState starts logging
     * in seconds
     */
    const LOG_EXECUTION_STATE_WAIT_BEFORE_LOGGING = 10;

    /**
     * Interval between log outputs from function logExecutionState
     * in seconds
     */
    const LOG_EXECUTION_STATE_INTERVAL = 1;

    /**
     * @var float Time when logRequestBegin was called. -1 means never
     */
    static $_logRequestsStartTime = -1;

    /**
     * @var float Time when logExecutionState was called the first time. -1 means never
     */
    static $_logExecutionStateStartTime = -1;

    /**
     * @var float Time when logExecutionState last output anything to the log. -1 means never
     */
    static $_logExecutionStateLastTime = -1;

    static function checkCreateDir( $log_dir ){
        $sl = strlen($log_dir);
        if( $sl && $log_dir[$sl-1]=='/' ){
            $log_dir = substr($log_dir,0,$sl-1);
        }
        if( !is_dir($log_dir) ){
            if( is_file($log_dir) ){
                @unlink($log_dir);
            }
            if( !@mkdir( $log_dir ) ){
                return null;
            }
        }
        return true;
    }

    // Return incrementing sequence number for a directory
    static function getNextSeqNo( $path, $ext="" ){
        if( !self::checkCreateDir($path) ){
            return null;
        }
        $full_path = $path."/seq_no".($ext?"-":"").$ext;
        $s = @file_get_contents( $full_path );
        //if( $s===FALSE ) return null;
        $no = (int)$s;
        if( !$no ) $no = 0;
        $r = @file_put_contents( $full_path, (int)(++$no) );
        if( $r===FALSE ) return null;
        return $no;
    }

    static function append( $log_path, $msg, $suppress_info = false){
        return self::appendToLog( $log_path, $msg, $suppress_info );
    }

    static function appendToLog( $log_path, $msg, $suppress_info = false ){
        try {
            if( file_exists($log_path) ){
                $fp = fopen( $log_path, "a" );
            } else {
                // Directory exists?
                $p = strrpos( $log_path, "/" );
                if( $p!==FALSE ){
                    $log_dir = substr( $log_path, 0, $p );
                    if( !self::checkCreateDir($log_dir) ){
                        return null;
                    }
                }
                $fp = fopen( $log_path, "w" );
            }
        } catch( Exception $e ) {
            return null;
        }
        if( !$fp ) return null;

        // Convert object / array to string
        if (is_array($msg) || is_object($msg)) {
            $msg = print_r($msg, true);
        }

        $sl = strlen($msg);
        if( !$sl || $msg[$sl-1]!=="\n" ){
            $msg .= "\n";
        }
        if ($suppress_info == false){
            fwrite( $fp, "--- " . @date("r") . " - pid(".getmypid().") ---\n" );
        }
        fwrite( $fp, $msg );
        fclose( $fp );
        return true;
    }

    static function writeSeqFile( $log_path, $ext, $item ){
        $sl = strlen($log_path);
        if( $sl>0 && $log_path[$sl-1]!='/' ) $log_path .= "/";
        $no = self::getNextSeqNo( $log_path, $ext );
        if( $no===FALSE ) return null;

        $fp = null;
        try {
            $fp = fopen( $log_path.$ext."-".$no.".log", "w" );
        } catch( Exception $e ){  }
        if( !$fp ) return null;

        $msg = $item;
        if( is_array($item) ){
            $msg = print_r($item,true);
        }
        fwrite( $fp, $msg );
        fclose( $fp );

        return true;
    }

    static function requestCounterGet( ){
        if( isset($_SERVER["REQUEST_COUNTER"]) ) return $_SERVER["REQUEST_COUNTER"];

        $r_ctr_file = str_replace("index.php","",$_SERVER["SCRIPT_FILENAME"])."var/r_ctr";
        $r_ctr = file_exists($r_ctr_file) ? file_get_contents($r_ctr_file) : 0;
        $_SERVER["REQUEST_COUNTER"] = $r_ctr;
        file_put_contents($r_ctr_file,$r_ctr+1);
        return $r_ctr;
    }


    static function requestCounterGetOpenLog( $log_path, $only_if_opened=true, $log_path_suffix="" ){
        if( $only_if_opened && !isset($_SERVER["REQUEST_COUNTER"]) ) return null;

        $r_ctr = self::requestCounterGet();
        if( $log_path_suffix && $log_path_suffix[0]!=="." ) $log_path_suffix = ".$log_path_suffix";
        $log_path .= ".$r_ctr" . $log_path_suffix;
        $path = array();
        if( preg_match("@^(.*)/[^/]*$@",$log_path,$path)>0 ){
            self::checkCreateDir($path[1]);
        }
        $fp = fopen( $log_path, "a" );
        return $fp;
    }

    static function requestLog( $msg, $log_path, $only_if_opened=true, $log_path_suffix="" ){
        $fp = self::requestCounterGetOpenLog( $log_path, $only_if_opened, $log_path_suffix );
        if( $fp ){
            fputs( $fp, "$msg\n" );
            fclose( $fp );
        }
    }


    static function logAppendBT( $msg, $file ){
        $dbgMsg = "Log point: " . $msg . "\n";
        if (isset($_SERVER['REQUEST_URI'])) {
            $dbgMsg .= "_SERVER['REQUEST_URI'] = " . $_SERVER['REQUEST_URI'] . "\n";
        }
        $dbgfile = "";
        $dbgTrace = debug_backtrace();
        // Ok, recursion might be prettier :D
        foreach($dbgTrace as $dbgIndex => $dbgInfo) {
            $argstr = "";
            $dbgline = "";
            foreach ($dbgInfo['args'] as $arg) {
                if ($argstr!="") $argstr .= ", ";
                if (!$arg) {
                    $argstr .= "NULL";
                } elseif (is_array($arg)) {
                    $argstr .= "array(";
                    $astr = "";
                    foreach ($arg as $a) {
                        if ($astr!="") $astr .=", ";
                        if (!$a) {
                            $astr .= "NULL";
                        } elseif (is_array($a)) {
                            $astr .= "array()";
                        } elseif (is_string($a)) {
                            $astr .= '"' . str_replace("\n"," ",$a) . '"';
                        } elseif (is_object($a)) {
                            $astr .= "object()";
                        } else {
                            $astr .= $a;
                        }
                    }
                    $argstr .= $astr . ")";
                } elseif (is_string($arg)) {
                    $argstr .= '"' . str_replace("\n"," ",$arg) . '"';
                } elseif (is_object($arg)) {
                    $argstr .= "object()";
                } else {
                    $argstr .= $arg;
                }
            }
            if (isset($dbgInfo['file'])) {
                $dbgfile = $dbgInfo['file'];
            }
            if (isset($dbgInfo['line'])) {
                $dbgline = $dbgInfo['line'];
            }
            $dbgfunc = $dbgInfo['function'];
            $dbgMsg .= "\t at $dbgIndex  " . $dbgfile . " (line " . $dbgline . ") -> " . $dbgfunc . "(" . $argstr . ")\n";
        }
        $dbgMsg .= "\n";

        return Icommerce_Log::append( $file, $dbgMsg );
    }

    /**
     * Given a filename, create the directory specified in the path.
     * @static
     * @param $pathToFile Path to the file, including the filename
     * @param bool $unlinkExisting If directory points to an existing file, remove that file to allow directory to be created
     * @return bool True if directory was created, False otherwise
     */
    private static function makeDirForFile($pathToFile, $unlinkExisting = true) {
        if (trim($dir = dirname($pathToFile))!='') {
            if ($unlinkExisting && file_exists($dir) && !is_dir($dir)) {
                @unlink($dir);
            }
            return (bool)@mkdir($dir, 777, true);
        }
        return false;
    }

    /**
     * Record current time and log details about the current request. Intended to be run at the start of a script.
     * @static
     * @param string $logPath Path to the log file to output to
     * @return void
     */
    static function logRequestBegin($logPath = self::LOG_REQUESTS_FILE)
    {
        self::$_logRequestsStartTime = microtime(true);
        self::$_logExecutionStateStartTime = self::$_logRequestsStartTime;
        self::makeDirForFile($logPath);
        @file_put_contents(
            $logPath,
            sprintf(
                '%s :: %s :: %d :: BEGIN :: %s' . self::EOL,
                self::$_logRequestsStartTime,
                $_SERVER['REMOTE_ADDR'],
                getmypid(),
                $_SERVER['REQUEST_URI']
            ),
            FILE_APPEND
        );
    }

    /**
     * Log details about the current request. Intended to be run at the end of a script.
     * @static
     * @param string $logPath Path to the log file to output to
     * @return void
     */
    static function logRequestEnd($logPath = self::LOG_REQUESTS_FILE)
    {
        self::makeDirForFile($logPath);
        @file_put_contents(
            $logPath,
            sprintf(
                '%s :: %s :: %d :: END :: %s (duration = %s)' . self::EOL,
                self::$_logRequestsStartTime,
                $_SERVER['REMOTE_ADDR'],
                getmypid(),
                $_SERVER['REQUEST_URI'],
                microtime(true) - self::$_logRequestsStartTime
            ),
            FILE_APPEND
        );
    }

    /**
     * Log the current REQUEST_URI and back-trace.
     * Logging starts only after an initial wait period (self::LOG_EXECUTION_STATE_WAIT_BEFORE_LOGGING)
     * After logging starts, only every n seconds a log is actually written (n = self::LOG_EXECUTION_STATE_INTERVAL)
     * @static
     * @param string $msg A message to include in the log output
     * @param string $logPath Path to the log file to output to
     * @return
     */
    static function logExecutionState($msg = 'execution-state-log', $logPath = self::LOG_EXECUTION_STATE_FILE)
    {
        if (self::$_logExecutionStateStartTime < 0) {
            self::$_logExecutionStateStartTime = microtime(true);
            return;
        }

        if (microtime(true) < self::$_logExecutionStateStartTime + self::LOG_EXECUTION_STATE_WAIT_BEFORE_LOGGING) {
            return;
        }

        //first time around $_logExecutionStateLastTime < 0, so we will pass by this check
        if (microtime(true) < self::$_logExecutionStateLastTime + self::LOG_EXECUTION_STATE_INTERVAL) {
            return;
        }

        self::$_logExecutionStateLastTime = microtime(true);
        if (self::$_logRequestsStartTime >= 0) {
            $msg = 'RequestTimestamp=' . self::$_logRequestsStartTime . ';' . $msg;
        }
        self::logAppendBT($msg, $logPath);
    }
}
