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



