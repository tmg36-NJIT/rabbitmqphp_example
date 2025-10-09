#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function requestHandler($request) {
	echo "Received request:";
 	 var_dump($request);
	return ['success' => true, 'message' => 'Received request'];
}

echo "Database Listener starting\n";
$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
$server->process_requests('requestHandler');
echo "Database Listener started\n";

?>

