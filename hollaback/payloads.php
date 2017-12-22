<?php
$PAYLOADS = array(
    array( "name" => "None",     "desc" => "Don't do anything"),
    array( "name" => "Redirect", "desc" => "302 Redirect to the payparam location"),
    array( "name" => "fileserv", "desc" => "responds with a file to download from /opt/hollaback/files")
);

function validate_payload($payid, $param){
    /* 
     * returns True if the payload and parameter looks good, otherwise return
     * false
    */
    if ( !is_int($payid) )
        return "payid must be int";
    if ( strlen($param) > 99 )
        return "param too big";

    $payloads = $GLOBALS["PAYLOADS"];
    if (!array_key_exists($payid, $payloads))
        return "Can't find payload";

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
    if ( $pay["name"] === "fileserv" ){
		$base = "/opt/hollaback/files/";
		foreach (array("\n", "/", "..", " ") as $bad){
			if ( strstr($param, $bad) )
				return sprintf("Bad char: (%c)", $bad);
		}
		$parent = realpath($base);
		$path = realpath($base . $param);
		if ( $path === False ){
			return "Filed DNE";
		}
		if (strpos($path, $parent) !== 0) {
			return "Path traversal";
		}
		return True;
    }
}

