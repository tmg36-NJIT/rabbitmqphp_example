<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

$time=date('Y-m-d H:i:s');
$logFile='/var/log/user_stats.log';

$conn=new mysqli("localhost","authuser","StrongPassword123!","testdb")

?>
