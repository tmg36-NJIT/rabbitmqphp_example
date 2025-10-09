#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


function doLoginDB($username, $password) {
         $mysqli - new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
        if($mysqli->connect_errno) {return['success' => false, 'message' => 'the database connection failed']; }
        $stmt = $mysqli->prepare("SELECT * FROM users WQHERE username=? AND password=?");
        if(!$stmt) {
        $ mysqli->close();
        return ['success' => flase, 'message' => 'query prep failed']; }

	$stmt->bind_param("ss", $username, $password);
        $success = $stmt ->execute();

function requestProcessor($request) {
	echo "Received request:";
	 var_dump($request);
	return ['success' => true, 'message' => 'Received request'];
}
echo "Database Listener starting\n";
$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
$server->process_requests('requestProcessor');
echo "Database Listener started\n";

?>
