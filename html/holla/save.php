<?php
include "/opt/hollaback/api.php";
include "/opt/hollaback/sql.php";
include "/opt/hollaback/payloads.php";

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


$default_ttl = 60*60*24;
$que_json = array(
	"uid"           =>  $_SESSION["uid"],
	"comment"       =>  isset($_POST["comment"])      ? $_POST["comment"] : NULL,
	"test_name"     =>  isset($_POST["test_name"])    ? $_POST["test_name"] : NULL,
	"cust_name"     =>  isset($_POST["cust_name"])    ? $_POST["cust_name"] : NULL,
	"consume"       =>  isset($_POST["consume"])      ? intval( $_POST["consume"] ) : NULL,
	"payid"         =>  isset($_POST["payid"])        ? intval( $_POST["payid"] ) : NULL,
	"payparam"      =>  isset($_POST["payparam"])     ? $_POST["payparam"] : NULL,
	"reply_method"  =>  isset($_POST["reply_method"]) ? intval( $_POST["reply_method"] ) : 0,
	"ttl"           =>  isset($_POST["ttl"])          ? intval( $_POST["ttl"] ) : $default_ttl,
	"token"         =>  get_token()
);

if ( $que_json["ttl"] === 0 ){
    $que_json["ttl"] = $default_ttl;
}else if ( $que_json["ttl"] > 7776000	 || $que_json["ttl"] < 0){
	fail("Invalid ttl", $verbose=True, $code=500);
}

$paytest = validate_payload($que_json["payid"], $que_json["payparam"]);
if ( $paytest !== True ){
    if ( $paytest === False  )
        fail("invalid payload", $verbose=True, $code=500);
    else
        fail("invalid payload: " . $paytest, $verbose=True, $code=500);
}

if ( isset($que_json["comment"]) ){
	if ( strlen($que_json["comment"]) > 1000 ){
		fail("Comment too big", $verbose=True);
	}
}

$db = new DB();
$ret = $db->enque($que_json);

if ( $ret === True ){
	$schema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://"; 
	$que_json["Success"] = True;
	$que_json["url"] = $schema . $_SERVER["SERVER_NAME"] . "/?t=" . $que_json["token"];
	echo json_encode($que_json);
}else{
	fail($ret, $verbose=True, $code=500);
}
