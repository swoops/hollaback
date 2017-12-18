<?php
if ( ! isset($DB_HOST) ) include "/opt/hollaback/config.php";

function get_req(){
	$ret = "";
	$ret .= $_SERVER["REQUEST_METHOD"] . " " . $_SERVER["REQUEST_URI"] . " " . $_SERVER["SERVER_PROTOCOL"] . "\n" ;
	foreach (getallheaders() as $key => $value) {
		$ret .= $key . ": " . $value . "\n";
	}
	$ret .= "\n";
	$ret .= file_get_contents("php://input");
	return $ret;
}

class DB {
	private $conn = False;
	private function get_conn(){
		if ( $this->conn ) 
			return $this->conn;
		$conn = new mysqli( 
			$GLOBALS["DB_HOST"], $GLOBALS["DB_USER"],
			$GLOBALS["DB_PASS"], $GLOBALS["DB_NAME"]
		);

		// Check connection
		if ($conn->connect_error) {
			throw new Exception("SQL Login Failed");
			exit(0);
		} 
		$this->conn = $conn;
		return $this->conn;
	}

	public function getvisit($token, $num){
		/*
		 * returns information for a visit, num coresponding to the visit
		 * number sorted by date 
		 */

		$er = array(
			"Success" => False,
			"msg" => "don't know"
		);
		if ( ! is_int($num) ){
			$er["msg"] = "num is not int\n" . var_dump($num) . "\ntest";
			return $er;
		}
		$conn = $this->get_conn();
		$query = $conn->prepare("SELECT * FROM visits WHERE token = ? ORDER BY date ASC LIMIT ?,1;");
		$query->bind_param("si", $token, $num);

		if(! $query->execute() ){
			$query->close();
			return $er;
		}
		$res = $query->get_result();
		if ( $res->num_rows !== 1 ){
			$er = sprintf("Improper number of rows: %d", $res->num_rows);
			$query->close();
			return $er;
		}
	   
		$ret = $res->fetch_array(MYSQLI_ASSOC);
		$ret["Success"] = True;
		$this->clean(); // clean up the old junk
		$query->close();
		return $ret;
	}

	public function check_creds($user, $pass){
		$ret = False;
		$conn = $this->get_conn();
		$query = $conn->prepare("SELECT uid,name,pass FROM users WHERE name=?");
		$query->bind_param("s", $user);
		if(!$query->execute() ){
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
		return $ret;
	}

	public function clean_token($token){
		$affected = 0;
		$conn = $this->get_conn();
		$query = $conn->prepare("delete from que where token=?");
		$query->bind_param("s", $token);
		if(!$query->execute() ){
			return False;
		}
		$affected += $query->affected_rows;
		$query->close();

		$query = $conn->prepare("delete from visits where token=?");
		$query->bind_param("s", $token);
		if(!$query->execute() ){
			return False;
		}
		$affected += $query->affected_rows;
		$query->close();

		if ($affected)
			$ret = True;
		else
			$ret = False;
		return $ret;
	}
	public function clean($consume=False){
		/* 
		 * careful using clean($consume=True) because it could delete an event
		 * before it is observed by the client 
		*/
		$conn = $this->get_conn();
		if ( $consume )
			$conn->query("DELETE from que where end_que < NOW() or (consume != 0 and visited >= consume );");
		else
			$conn->query("DELETE from que where end_que < NOW();");
		$conn->query("DELETE FROM visits WHERE token NOT IN (SELECT TOKEN FROM que);");
	}

	public function enque($q){
		// if something that should be an int isn't set it to 0
		foreach(array("consume", "payid", "reply_method") as $i){
			if ( !is_int($q[$i]) ){
				$q[$i] = 0;
			}
		}
		$conn = $this->get_conn();
		$query = $conn->prepare("INSERT INTO que (token, uid, comment, test_name, cust_name, consume, payid, payparam, reply_method, start_que, end_que) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW() + INTERVAL ? SECOND )");

		$ttl = intval($q["ttl"]);
		$query->bind_param("sssssiisii", 
			$q["token"], $q["uid"], $q["comment"], $q["test_name"],
			$q["cust_name"], $q["consume"], $q["payid"],
			$q["payparam"], $q["reply_method"], $ttl
		);

		if(! $query->execute() ){
			$er = $query->error_list[0]["error"] . "\n";
			$query->close();
			return $er;
		}

		$query->close();
		return True;
	}

	public function token_visit($token){
		/*
		 * Someone visited the callback:
		 * Inc the visited count in que
		 * save the details in visits
		*/
		$conn = $this->get_conn();

		// first update visited count
		$query = $conn->prepare("UPDATE que SET visited = visited+1 WHERE token=?");
		$query->bind_param("s", $token); 
		$query->execute(); // TODO: log a failure here somehow
		$query->close();

		// Now save the good stuff in the visited table.
		$query = $conn->prepare(
			"INSERT INTO visits " .
				"(token, user_agent, host_header, remote_address,".
					"forward_for,client_ip, request_method,".
					"request_scheme, port, req)".
			"VALUES ".
				"(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
		);
		$user_agent      =  array_key_exists("HTTP_USER_AGENT",       $_SERVER)  ?  $_SERVER["HTTP_USER_AGENT"]       :  "";
		$host_header     =  array_key_exists("HTTP_HOST",             $_SERVER)  ?  $_SERVER["HTTP_HOST"]             :  "";
		$remote_address  =  array_key_exists("REMOTE_ADDR",           $_SERVER)  ?  $_SERVER["REMOTE_ADDR"]           :  "";
		$forward_for     =  array_key_exists("HTTP_X_FORWARDED_FOR",  $_SERVER)  ?  $_SERVER["HTTP_X_FORWARDED_FOR"]  :  "";
		$client_ip       =  array_key_exists("CLIENT_IP",             $_SERVER)  ?  $_SERVER["CLIENT_IP"]             :  "";
		$request_method  =  array_key_exists("REQUEST_METHOD",        $_SERVER)  ?  $_SERVER["REQUEST_METHOD"]        :  "";
		$request_scheme  =  array_key_exists("REQUEST_SCHEME",        $_SERVER)  ?  $_SERVER["REQUEST_SCHEME"]        :  "";
		$port            =  array_key_exists("REMOTE_PORT",           $_SERVER)  ?  $_SERVER["REMOTE_PORT"]           :  "";
		$req             =  get_req();



		$query->bind_param("ssssssssss", 
			$token, $user_agent, $host_header, $remote_address, $forward_for,
			$client_ip,  $request_method, $request_scheme, $port, $req
		);

		if(! $query->execute() ){
			$er = $query->error_list[0]["error"] . "\n";
			$query->close();
			return $er;
		}

		$query->close();
		return True;
	}

	public function token_check($token){
		/*
		 * Returns a dictionary of information assosiated to a token. The Success
		 * attribute will be set to True if all goes well.
		*/
		$conn = $this->get_conn();
		$query = $conn->prepare("select * from que where token=?");
		$query->bind_param("s", $token);

		if(! $query->execute() ){
			$er = array(
				"Success" => False,
				"msg" => $query->error_list[0]["error"]
			);
			$query->close();
			return $er;
		}
		$res = $query->get_result();
		if ( $res->num_rows !== 1 ){
			$er = array(
				"Success" => False,
				"msg" => sprintf("Improper number of rows: %d", $res->num_rows)
			);
			$query->close();
			return $er;
		}
	   
		$ret = $res->fetch_array(MYSQLI_ASSOC);
		$ret["Success"] = True;
		$this->clean(); // clean up the old junk
		$query->close();
		return $ret;
	}

	public function add_user($user, $pass){
		if ( strlen($user) >= 20 ){
			echo "username too long\n";
			return false;
		} else if ( strlen($pass) < 8 ){
			echo "Need a longer username";
			return false;
		}
		$hash = password_hash($pass, PASSWORD_BCRYPT);
		$conn = $this->get_conn();

		$query = $conn->prepare("INSERT INTO users (uid, name, pass) values (uuid(),?,?);");
		$query->bind_param("ss", $user, $hash);
		$ret = $query->execute();

		if (! $ret ){
			echo "Error: " . $query->error_list[0]["error"] . "\n";
		}
		return $ret;
	}
	public function __destruct() {
		if ( $this->conn )
			mysqli_close($this->conn);
	}

}
