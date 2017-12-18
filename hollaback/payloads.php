<?php
$PAYLOADS = array(
    array( "name" => "None",     "desc" => "Don't do anything"),
    array( "name" => "Redirect", "desc" => "302 Redirect to the payparam location")
);

function validate_payload($payid, $param){
    /* 
     * returns True if the payload and parameter looks good, otherwise return
     * false
    */
    if ( !is_int($payid) )
        return False;
    if ( strlen($param) > 99 )
        return False;

    $payloads = $GLOBALS["PAYLOADS"];
    if (!array_key_exists($payid, $payloads))
        return False;

    $pay = $payloads[$payid];
    
    if ( $pay["name"] === "None" )
        return True;

    if ( $pay["name"] === "Redirect" ){
        // TODO: better URL veririfaction
        if ( strstr($param, "\n") )
            return False;
        else
            return True;
    }
}

