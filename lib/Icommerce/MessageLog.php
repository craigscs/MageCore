<?php

require_once( "lib/Icommerce/Log.php" );

class Icommerce_MessageLog {

    static function logMessage( $msg, $component, $msg_full="", $instance="" ){
        self::logAny( "message", $msg, $component, $msg_full, $instance );
    }

    static function logWarning( $msg, $component, $msg_full="", $instance="" ){
        self::logAny( "warning", $msg, $component, $msg_full, $instance );
    }

    static function logError( $msg, $component, $msg_full="", $instance="" ){
        self::logAny( "error", $msg, $component, $msg_full, $instance );
    }

    static function logAny( $type, $msg, $component, $msg_full, $instance ){
        // Try connect to sp_local database
        $db = @mysql_connect("localhost", "ic_msg", "Th370t");
        if( $db ) {
            // See if DB exists
            if( !mysql_select_db( "ic_msg", $db ) ){
                self::logAndThrowException("Failed selecting ic-msg database");
            }
            // Insert into DB
            $time = date("YmdHis");
            $msg = str_replace("'","\'",$msg);
            $msg_full = str_replace("'","\'",$msg_full);
            $sql = "INSERT INTO ic_msg (type,instance,component,message,fullmessage,time) VALUES ('$type','$instance','$component','$msg','$msg_full',$time)";
            try {
                $r = mysql_query( $sql );
            }

            // These are to catch any mistake in MySQL character escaping
            catch( Exception $e ){
                // Maybe wrong in SQL char substitution...?
                $msg_full = "Failed substite in msg_full";
                $sql = "INSERT INTO ic_msg (type,instance,component,message,fullmessage,time) VALUES ('$type','$instance','$component','$msg','$msg_full',$time)";
                try {
                    $r = mysql_query( $sql );
                }
                catch( Exception $e ){
                    // Last attempt...?
                    $msg = "Failed substitute in msg";
                    $sql = "INSERT INTO ic_msg (type,instance,component,message,fullmessage,time) VALUES ('$type','$instance','$component','$msg','$msg_full',$time)";
                    try {
                        $r = mysql_query( $sql );
                    }
                    catch( Exception $e ){
                        self::logAndThrowException("Icommerce_MessageLog - logAny - Repeated exception writing to ic_msg");
                    }
                }
            }
        } else {
            self::logAndThrowException("Icommerce_MessageLog - logAny - Failed to open message database");
        }
    }

    static function logAndThrowException($msg){
        Icommerce_Log::appendToLog( "/tmp/icommerce.log", $msg );
        throw new Icommerce_Exception_MessageLog($msg);
    }
}
