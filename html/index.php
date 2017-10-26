<?php
include "/opt/hollaback/sec.php";
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
$toke_info = token_check($token);
if ( ! is_array($toke_info) || $toke_info["Success"] !== True){
	if ($DEBUG ) {
		printf("[%s:%d] token failed\n", __FILE__, __LINE__);
	}
	exit(0);
}

// so this is a valid token visit we need to update the database
token_visit($token);
