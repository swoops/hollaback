<?php
include "/opt/hollaback/payloads.php";
function get_token(){
	// TODO: token in more places
	if ( isset($_GET["t"]) ){
		return $_GET["t"];
	}else if ( isset($_GET["token"]) ){
		return $_GET["token"];
	}else {
		return False;
	}
}

$token  = get_token();

// no token
if ( $token === False ){
	// not a holla back so maybe just a short XSS request
	echo "alert('XSS executing in' + document.domain);";
	exit(0);
}

include "/opt/hollaback/sql.php";
$db = new DB();
$toke_info = $db->token_check($token);
if ( ! is_array($toke_info) || $toke_info["Success"] !== True){
	if ($DEBUG ) {
		printf("[%s:%d] token failed\n", __FILE__, __LINE__);
	}
	exit(0);
}

// so this is a valid token visit we need to update the database
$db->token_visit($token);

$payid = $toke_info["payid"];
$payparam = $toke_info["payparam"];
if ( validate_payload($payid, $payparam) !== True )
	exit(0);

/* if the payload has been consumed don't run it */
if ($toke_info["consume"] > 0 && $toke_info["consume"] <= $toke_info["visited"])
	exit(0);

// do this with the payload.php lib
switch ($toke_info["payid"]){
	case 0:
		/* no payload */
		exit(0);
		break;
	case 1: 
		/* 302 to the param value */
		header('Location: ' . $toke_info["payparam"]);
		break;
	case 2: 
		/* send them a file from /opt/hollaback/files */
		/* no reason to mess with getfile */
		$f = "/opt/hollaback/files/" . $toke_info["payparam"];

		header("Content-Type: " .  mime_content_type($f) );
		$fp = fopen($f, "r");
		if ( ! $fp ) exit(0);
		fseek($fp, 0, SEEK_END);
		$s = ftell($fp);
		fseek($fp, 0);
		echo fread($fp, $s);
		fclose($fp);
		break;
	default:
		exit(0);
}
