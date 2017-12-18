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

$db = new DB();
$ret = $db->clean_token($token);
if ( $ret )
    http_response_code(200);
else
	fail("Clean failed", $verbose=True, $code=403);
