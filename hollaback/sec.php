<?php
	/* this tests some secruity sanity checks */
	if ( ! isset($SERVERNAME) ) include "/opt/hollaback/config.php";

	$evil_srv = $_SERVER["HTTP_HOST"];
	if ( ! isset( $evil_srv ) || $evil_srv !== $SERVERNAME ){
		echo var_dump($DEBUG);
		if ($DEBUG === true) {
			echo "Server fails to validate\n";
			echo "Host header: \n";
			echo var_dump($evil_srv);
			echo "SERVERNAME: ";
			echo var_dump($SERVERNAME);
		}
		exit(0);
	} 
