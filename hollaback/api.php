<?php
/*
 * this should be included in all api files except login
*/
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");

function fail($msg, $verbose=False, $code=401){
	if ( $verbose ){
		$json = array( 
			"Success" => False, 
			"msg" => $msg 
		);
		echo json_encode($json);
	}
	http_response_code($code);
	exit(0);
}

if ( !array_key_exists("uid", $_SESSION) || !array_key_exists("name", $_SESSION)  ){
	fail("Not logged in", $verbose=$DEBUG);
}

