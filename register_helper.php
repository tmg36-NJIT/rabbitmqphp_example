// To store the main logic for registration form data to send to the msg broker. - tmg
<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start()
require_once('/var/www/html/path.inc');
require_once('/var/www/html/get_host_info.inc');
require_once('/var/www/html/rabbitMQLib.inc');

$mq=new rabbitMQClient("testRabbitMQ.ini","testServer");
echo "Attempting to connect to Queue....<br>";


?>
