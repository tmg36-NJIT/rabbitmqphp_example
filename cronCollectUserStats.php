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

if(!$data){
file_put_contents($logFile,"[$time] Query failed: ".$conn->error."\n",FILE_APPEND);
$conn->close();
exit;
}

$count=$data->fetch_assoc(){'total']??0;
$entry="[$time] Users in table: $count\n";
file_put_contents($logFile,$entry,FILE_APPEND);
echo $entry;
$conn->close();

?>
