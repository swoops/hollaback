<?php
if (php_sapi_name() !== "cli"){
	exit(0);
}

include "/opt/hollaback/sql.php";

$len = count($argv);
if ( $len !== 2 ){
	echo "$argv[0] <username>\n";
	exit(0);
}

$user = $argv[1];
$pass = bin2hex(random_bytes(16));
	
$db = new DB();
$ans = $db-> add_user($user, $pass);

if ($ans){
	echo "Created user\n";
	echo "hollapy config should be:\n";
	echo " {\n";
	echo "	\"default\" : {\n";
	echo "		\"serv\" : \"<server location>\",\n";
	echo "		\"creds\" : {\n";
	echo "			\"user\" : \"". $user . "\",\n";
	echo "			\"pass\" : \"" . $pass . "\"\n";
	echo "		}\n";
	echo "	}\n";
	echo "}\n";
}else{
	echo "Something broke";
}
