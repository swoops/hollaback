<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");

include "/opt/hollaback/sql.php";

function fail($msg, $verbose=False, $code=401){
	if ( $verbose ){
		$json = array( 
			"success" => False, 
			"msg" => $msg 
		);
		echo json_encode($json);
	}
	http_response_code($code);
	exit(0);
}

function get_token(){
	if (function_exists("random_int"))
		return sha1(uniqid(random_int(PHP_INT_MIN, PHP_INT_MAX), TRUE));
	else
		fail("I need random_int to create a token", $verbose=True);
}

# only accept POST requests for login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	fail("MUST be a post request", $verbose=$DEBUG);
}


$uid = $_SESSION["uid"];
$name = $_SESSION["name"];
if ( !( isset( $uid ) && isset( $name ) ) ){
	fail("Not logged in", $verbose=$DEBUG);
}

$default_ttl = 60*60*24;
$que_json = array(
	"uid"			=>  $uid,
	"comment"       =>  isset($_POST["comment"]) ? $_POST["comment"] : NULL,
	"test_name"     =>  isset($_POST["test_name"]) ? $_POST["test_name"] : NULL,
	"cust_name"     =>  isset($_POST["cust_name"]) ? $_POST["cust_name"] : NULL,
	"consume"       =>  isset($_POST["consume"]) ? intval( $_POST["consume"] ) : NULL,
	"reply_method"  =>  isset($_POST["reply_method"]) ? intval( $_POST["reply_method"] ) : 0,
	"ttl"           =>  isset($_POST["ttl"]) ? intval( $_POST["ttl"] ) : $default_ttl,
	"token"         =>  get_token()
);

if ( $que_json["ttl"] === 0 ){
    $que_json["ttl"] = $default_ttl;
}else if ( $que_json["ttl"] > 7776000	 || $que_json["ttl"] < 0){
	fail("Invalid ttl", $verbose=True, $code=500);
}

if ( isset($que_json["comment"]) ){
	if ( strlen($que_json["comment"]) > 1000 ){
		fail("Comment too big", $verbose=True);
	}
}

$ret = enque($que_json);

if ( $ret === True ){
	$schema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://"; 
	$que_json["Success"] = True;
	$que_json["url"] = $schema . $SERVERNAME . "/?t=" . $que_json["token"];
	echo json_encode($que_json);
}else{
	fail($ret, $verbose=True, $code=500);
}
