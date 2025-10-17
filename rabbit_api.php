<?php
declare(strict_types=1);
error_reporting(E_ALLL);
ini_set("display_errors, '1');
header("Content type: application/json')
session_start();
$user = isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'Guest';

//some local paths 

$lib = __DIR__ . '/rabbitMQLib.inc';
$ini = __DIR__ . '/testRabbitMQ.ini';
$setion = 'testServer';

if(!file_exists($lib)){
http_response_code(500);
 echo json_encode(['success'=>false,'message'=>'Missing rabbitMQLib.inc'
]);
exit;}

if(!file_exists($ini))
{
http_response_code(500); echo json_encode([
'success'=>false,'message'=>'Missing testRabbitMQ.ini'
]);  
exit;}

require_once $lib;

if(isset($_GET['genre'])){
$genre = trim($_GET['genre']);
}else{
$genre = '';
}
if(isset($_GET['platform'])){
$platform = trim($_GET['platform']);
}
else{
$platfrm = '';
}
  if(isset($_GET['year'])){
$year = trim($_GET['year']);
}else{
$year = '';
}
//uiser  input sanitization
$genre = preg_replace('/[^a-z0-9\- ]/i','',$genre);
$platform = preg_replace('/[^0-9]/','',$platform);
$year = preg_replace('/[^0-9]/','',$year);

$query = trim("$genre $platform $year");
$client = new rabbitMQClient($ini,$section);
$input = file_get_contents('php://input');
$json = json_decode($input,true);

if(is_array($json) && isset($json['type'])){
$request = $json;
}
elseif($genre || $platform || $year){
$request = ['type'=>'get_recommendations','query'=>$query];
}
else{
echo json_encode(['success'=>false,'message'=>'Empty or invalid request']);exit;
}





