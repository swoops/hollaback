<?php
if ( ! isset($SERVERNAME) ) include "/opt/hollaback/config.php";

function get_conn(){
	$DB_HOST = "127.0.0.1";
	$DB_USER = "holla_user";
	$DB_PASS = "DaysqzreXrHmUKThxhnn8rICmY";
	$DB_NAME = broken_on_purpose
	$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

	// Check connection
	if ($conn->connect_error) {
		if ($DEBUG){
			echo "Connection failed: " . $conn->connect_error;
		}
		http_response_code(401);
		exit(0);
	} 
	return $conn;
}

function check_creds($user, $pass){
	$ret = False;
	$conn = get_conn();
	$query = $conn->prepare("SELECT uid,name,pass FROM users WHERE name=?");
	$query->bind_param("s", $user);
	if(! $query->execute() ){
		return False;
	}
	$query->bind_result($uid, $name, $hash);
	$query->fetch();

	if ( password_verify($pass, $hash) ){
		$_SESSION["uid"] = $uid;
		$_SESSION["name"] = $name;
		$ret = True;
	}

	$query->close();
	$conn->close();
	return $ret;
}

function clean($conn){
	$conn->query("DELETE from que where end_que < NOW();");
	$conn->query("DELETE FROM visits WHERE token NOT IN (SELECT TOKEN FROM que);");
}

function enque($q){
	$conn = get_conn();
	$query = $conn->prepare("INSERT INTO que (token, uid, comment, test_name, cust_name, consume, reply_method, start_que, end_que) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW() + INTERVAL ? SECOND )");

	$ttl = intval($q["ttl"]);
	$query->bind_param("sssssiii", 
		$q["token"], $q["uid"], $q["comment"], $q["test_name"],
		$q["cust_name"], $q["consume"],$q["reply_method"], $ttl
	);

	if(! $query->execute() ){
		$er = $query->error_list[0]["error"] . "\n";
		$conn->close();
		$query->close();
		return $er;
	}
    clean($conn);

	$query->close();
	$conn->close();
	return True;
}

function token_visit($token){
	/*
	 * Someone visited the callback, save all the good stuff
	*/
	$conn = get_conn();

	// first update visited count
	$query = $conn->prepare("UPDATE que SET visited = visited+1 WHERE token=?");
	$query->bind_param("s", $token); 
	$query->execute(); // TODO: log a failure here somehow
	$query->close();

	// Now save the good stuff in the visited table.
	$query = $conn->prepare(
		"INSERT INTO visits " .
			"(token, user_agent, host_header, remote_address,".
				"forward_for,client_ip, server_protocol, request_method,".
				"request_scheme, port, get, post)".
		"VALUES ".
			"(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
	);
	$user_agent = array_key_exists("HTTP_USER_AGENT", $_SERVER) ? $_SERVER["HTTP_USER_AGENT"] : "";
	$host_header = array_key_exists("HTTP_HOST", $_SERVER) ? $_SERVER["HTTP_HOST"] : "";
	$remote_address = array_key_exists("REMOTE_ADDR", $_SERVER) ? $_SERVER["REMOTE_ADDR"] : "";
	$forward_for = array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : "";
	$client_ip = array_key_exists("CLIENT_IP", $_SERVER) ? $_SERVER["CLIENT_IP"] : "";
	$server_protocol = array_key_exists("SERVER_PROTOCOL", $_SERVER) ? $_SERVER["SERVER_PROTOCOL"] : "";
	$request_method = array_key_exists("REQUEST_METHOD", $_SERVER) ? $_SERVER["REQUEST_METHOD"] : "";
	$request_scheme = array_key_exists("REQUEST_SCHEME", $_SERVER) ? $_SERVER["REQUEST_SCHEME"] : "";
	$port = array_key_exists("REMOTE_PORT", $_SERVER) ? $_SERVER["REMOTE_PORT"] : "";
	$get = json_encode($_GET);
	$post = json_encode($_POST);

	$query->bind_param("ssssssssssss", 
		$token, $user_agent, $host_header, $remote_address, $forward_for,
		$client_ip, $server_protocol, $request_method, $request_scheme, $port,
		$get, $post
	);

	if(! $query->execute() ){
		$er = $query->error_list[0]["error"] . "\n";
		$conn->close();
		$query->close();
		return $er;
	}
    clean($conn);

	$query->close();
	$conn->close();
	return True;
}

function token_check($token){
	/*
	 * Returns a dictionary of information assosiated to a token. The Success
	 * attribute will be set to True if all goes well.
	*/
	$conn = get_conn();
	$query = $conn->prepare("select * from que where token=?");
	$query->bind_param("s", $token);

	if(! $query->execute() ){
		$er = array(
			"Success" => False,
			"msg" => $query->error_list[0]["error"]
		);
		$conn->close();
		$query->close();
		return $er;
	}
    $res = $query->get_result();
    if ( $res->num_rows !== 1 ){
        $er = sprintf("Improper number of rows: %d", $res->num_rows);
        $conn->close();
        $query->close();
        return $er;
    }
   
    $ret = $res->fetch_array(MYSQLI_ASSOC);
	$ret["Success"] = True;
    clean($conn); // clean up the old junk
	$query->close();
	$conn->close();
	return $ret;
}

/*
function list_que($uid){
	$conn = get_conn();
    clean($conn); // clean out the old before asking for current
	$query = $conn->prepare("select * from que where uid='?'");
	$query->bind_param("s", $uid);

	if(! $query->execute() ){
		$er = $query->error_list[0]["error"] . "\n";
		$conn->close();
		$query->close();
		return $er;
	}
    echo $query->fetchAll();
	$query->close();
	$conn->close();
	return True;
}
*/

function add_user($user, $pass){
	if ( strlen($user) >= 20 ){
		echo "username too long\n";
		return false;
	} else if ( strlen($pass) < 8 ){
		echo "Need a longer username";
		return false;
	}
	$hash = password_hash($pass, PASSWORD_BCRYPT);
	$conn = get_conn();

	$query = $conn->prepare("INSERT INTO users (uid, name, pass) values (uuid(),?,?);");
	$query->bind_param("ss", $user, $hash);
	$ret = $query->execute();

	if (! $ret ){
		echo "Error: " . $query->error_list[0]["error"] . "\n";
	}
	return $ret;
}
