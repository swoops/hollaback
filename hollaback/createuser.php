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
$pass = readline("Password for " . $user . ": ");
	
$db = new DB();
$ans = $db-> add_user($user, $pass);

if ($ans)
	echo "Created user\n";
