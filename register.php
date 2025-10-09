// This is going to be the official file where Users can register.
<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);
session_start();
require_once('/var/www/html/path.inc');
require_once('/var/www/html/get_host_info.inc');
require_once('/var/www/html/rabbitMQLib.inc');

$errs = [];
$okMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$user=trim($_POST['username'] ?? '');
$pass=trim($_POST['password'] ?? '');


if ($user===''){
$errs[] = 'There needs to be a username. Try again!';

}
if ($pass==''){
$errs[]="There needs to be a password. Try again!"
}

if(empty($errs)) {
try{
$mq = new rabbitMQClient('/var/www/html/testRabbitMQ.ini', 'testServer'); //mq for the client
?>

