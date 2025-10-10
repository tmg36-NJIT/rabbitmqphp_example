#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function createSession($user_id) {
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno){return false;}

	$sessionKey =bin2hex(random_bytes(16));
	$stmt = $mysqli->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES(?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
	if(!$stmt) {
	$mysqli->close();
	return false;}

	$stmt->bind_param("is", $user_id, $sessionKey);
	$success = $stmt->execute();
	$stmt->close();
	$mysqli->close();
	if($success){return $sessionKey;
	} else {return false;}
}

function doLoginDB($username, $password) {
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno) {return['success' => false, 'message' => 'the database connection failed']; }
	$stmt = $mysqli->prepare("SELECT * FROM users WHERE username=? AND password=?");
	if(!$stmt) {
	$mysqli->close();
	return ['success' => false, 'message' => 'query prep failed']; }

	$stmt->bind_param("ss", $username, $password);
	$stmt ->execute();
	$result = $stmt->get_result();
	$found=($result && $result->num_rows > 0);
	$stmt->close();
	$mysqli->close();
	 if($found) {return ['success' => true, 'message' => 'Login successful'];
	} else{return['success'=> false, 'message' => 'Invalid username and/or password']; }
}

function doRegisterDB($username, $password){
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno) {return['success' => false, 'message' => 'the database connection failed'];}
	$stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
	if(!$stmt) {
	$mysqli->close();
	return ['success' => false, 'message' => 'query prep failed']; }


	$stmt->bind_param("ss", $username, $password);
	$success = $stmt ->execute();
	$stmt->close();
	$mysqli->close();
	if ($success) {return['success' => true, 'message' => 'You have been registered'];
	} else { return['success' => false, 'message'=> 'registration failed'];}
}



function requestProcessor($request) {
	echo "Received request:";
	 var_dump($request);
        if(!isset($request['type'])){ return['success' => false, 'message' => 'Invalid request'];}


	switch($request['type']){
		case 'login':
		 return doLoginDB($request['username'], $request['password']);
		case 'register':
		 return doRegisterDB($request['username'], $request['password']);
		default:
		 return['success' => false, 'message' => 'invalid request'];}
}




echo "Database Listener starting\n";
$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
$server->process_requests('requestProcessor');
echo "Database Listener started\n";

?>
