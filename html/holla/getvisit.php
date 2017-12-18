<?php
include "/opt/hollaback/api.php";
include "/opt/hollaback/sql.php";
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	fail("MUST be a GET request", $verbose=$DEBUG);
}

$token = $_GET["token"];
if (! isset($token) ){
	fail("Need token", $verbose=True, $code=403);
}

if ( !is_numeric($_GET["num"]) ){
	fail("Need valid number", $verbose=True, $code=403);
}
$num = intval( $_GET["num"], $base=10 );

$db = new DB();
$res = $db->getvisit($token, $num);
if ( ! is_array($res) )
    fail("return value was not an array???", $verbose=True, $code=403);
else if ( $res["Success"] !== True )
	http_response_code(400);

echo json_encode($res);
