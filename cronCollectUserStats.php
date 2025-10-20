<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

$time=date('Y-m-d H:i:s');
$logFile = '/var/www/html/game_watchlist_cron.log';

$conn=new mysqli("localhost","authuser","StrongPassword123!","testdb");
if($conn->connect_errno){file_put_contents($logFile, "[$time] Connection error: ".$conn->connect_error."\n", FILE_APPEND);
exit;}

//api url+key
$rawgKey= 'e92e94964e714d64aa425f8c11d0996e';
$rawgUrl= 'https://api.rawg.io/api/games?key=' . $rawgKey;
$cheapsharkUrl= 'https://www.cheapshark.com/api/1.0/games?title=';



$query= "SELECT id, username, game_name, last_known_price, last_release_date, last_updated FROM watchlist";
$result= $conn->query($query);

if (!$result){ file_put_contents($logFile, "[$time] Query failed: " . $conn->error . "\n", FILE_APPEND);
	$conn->close();
	exit;}



$userChanges= [];
 while ($row= $result->fetch_assoc(){
	$gameName= urlencode($row['game_name']);
	 $changes= [];
	$newReleaseDetected= false;
	$patchUpdateDetected= false;
	$genreString= "";


?>
