#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function createSession($user_id) {
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno){
	return false;}

	$sessionKey =bin2hex(random_bytes(16));
	$stmt = $mysqli->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES(?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
	if(!$stmt) {
	$mysqli->close();
	return false;}

	$stmt->bind_param("is", $user_id, $sessionKey);
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	return $sessionKey;
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
	$row = $result->fetch_assoc();
	$stmt->close();
	$mysqli->close();
	 if($row) { $sessionKey = createSession($row['id']);
		if($sessionKey) {
			return [
				'success' =>true,
				'message' =>"Login successful", 
				'session_key' =>$sessionKey ];
		} else {return ['success' => true, 'message' => "Login successful (no key)"];}
	} else { return ['success' => false, 'message' => "Invalid username and/or password"];}
}

function doRegisterDB($username, $password){
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno) {return['success' => false, 'message' => 'the database connection failed'];}
	try {
	$stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
	if(!$stmt) {
	$mysqli->close();
	return ['success' => false, 'message' => 'query prep failed']; }


	$stmt->bind_param("ss", $username, $password);
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	return ['success' => true, 'message' => "User Registered!"];}
	catch(mysqli_sql_exception $e) {
	 if (str_contains($e->getMessage(), 'Duplicate entry')) { return ['success' => false, 'message' => "Username already exists"];}
	return ['success' => false, 'message' => "Registration error: " . $e->getMessage()]; }


}


function getGameRecommendations($query = null) {
	$apiKey='e92e94964e714d64aa425f8c11d0996e';
	$baseUrl = 'https://api.rawg.io/api/games?key=' . $apiKey;
	if($query){$baseUrl.= '&search'. urlencode($query);}
	
	$response= @filegetcontents($baseUrl);
	if($response==false){return ['success'  => false 'message' => 'unable to rach the rawg api'];}
	


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
echo "connected to broker: ". $server->BROKER_HOST. "\n";
echo "database listener started\n";

$server->process_requests(function($request) {
	 $response = requestProcessor($request);
	echo "Response: \n";
	var_dump($response);
	return $response; } );


?>
