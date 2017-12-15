<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");

include "/opt/hollaback/sql.php";

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

# only accept POST requests for login
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	fail("MUST be a GET request", $verbose=$DEBUG);
}


$uid = $_SESSION["uid"];
$name = $_SESSION["name"];
if ( !( isset( $uid ) && isset( $name ) ) ){
	fail("Not logged in", $verbose=$DEBUG);
}

$token = $_GET["token"];
if (! isset($token) ){
	fail("Need token", $verbose=True, $code=403);
}

$db = new DB();
$res = $db->token_check($token);
if ( ! is_array($res) )
    fail("return value was not an array???", $verbose=True, $code=403);
else if ( $res["Success"] !== True )
	http_response_code(400);

echo json_encode($res);
