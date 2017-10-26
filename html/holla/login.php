<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
include "/opt/hollaback/config.php";
include "/opt/hollaback/sql.php";

# only accept POST requests for login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	if ($DEBUG)
		echo "MUST be a post request to login\n";
	http_response_code(401);
	exit(0);
}

$user = $_POST["user"];
$pass = $_POST["pass"];

if (! (isset( $user ) && isset( $pass ))){
	if ($DEBUG)
		echo "Need credentials\n";
	http_response_code(401);
	exit(0);
}

$login = check_creds($user, $pass); 
$msg = array("Success" => False);

if ( $login  === True){
	$msg["Success"] = True;
	$msg["msg"] = "Signed in as ". $_SESSION["name"];
}else if ($DEBUG){
	$msg["msg"] = $ret;
	http_response_code(401);
	$_SESSION["name"] = NULL;
	$_SESSION["uid"] = NULL;
}
echo json_encode($msg);
