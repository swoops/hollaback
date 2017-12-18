<?php
include "/opt/hollaback/api.php";
include "/opt/hollaback/sql.php";

# only accept POST requests for login
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	fail(__FILE__ . " MUST be a GET request", $verbose=$DEBUG);
}

$token = $_GET["token"];
if (! isset($token) ){
	fail(__FILE__ . " Need token", $verbose=True, $code=403);
}

$db = new DB();
$res = $db->token_check($token);
if ( ! is_array($res) )
    fail(__FILE__ . " return value was not an array??? ", $verbose=True, $code=403);
else if ( $res["Success"] !== True )
	http_response_code(400);

echo json_encode($res);
