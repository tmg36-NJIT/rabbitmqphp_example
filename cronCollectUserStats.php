<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

$time=date('Y-m-d H:i:s');
$logFile='/var/log/user_stats.log';

$conn=new mysqli("localhost","authuser","StrongPassword123!","testdb");
if($conn->connect_errno){
file_put_contents($logFile, "[$time] Connection error: ".$conn->connect_error."\n", FILE_APPEND);
exit;

}

$query="SELECT COUNT(*) AS total FROM users";
$data=$conn->query($query);

?>
