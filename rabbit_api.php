<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json');
session_start();

$sessionUser = isset($_SESSION['username']) && !empty($_SESSION['username'])
? $_SESSION['username']
: 'Guest';

//some local paths 

$lib = __DIR__ . '/rabbitMQLib.inc';
$ini = __DIR__ . '/testRabbitMQ.ini';
$section = 'testServer';

if(!file_exists($lib)){
http_response_code(500);
 echo json_encode(['success'=>false,'message'=>'Missing rabbitMQLib.inc'
]);
exit;}

if(!file_exists($ini))
{
http_response_code(500);
echo json_encode([
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
$platform = '';
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


try {
if (is_array($json) && isset($json['type'])) {
$request = $json;

if (!isset($request['username']) || $request['username'] === '') {
$request['username'] = $sessionUser;
    }
if (in_array($request['type'], [
'personalized_recommendations',
'add_watchlist', 'get_watchlist',
'submit_review', 'get_reviews', 'delete_review',
'update_email', 'check_email',
'get_notifications', 'mark_notification_read',
'get_details'
    ], true)) {
error_log("rabbit_search passthru: " . json_encode($request));
$rawResp = $client->send_request($request);
echo json_encode($rawResp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
}
} elseif (isset($_GET['genre']) || isset($_GET['platform']) || isset($_GET['year'])) {
$query = trim(($genre ? $genre . ' ' : '') . ($platform ? $platform . ' ' : '') . $year);
$request = [
'type' => 'get_recommendations',
'query' => $query,
'username' => $sessionUser
];
} else {
echo json_encode(['success' => false, 'message' => 'Invalid or empty request.']);
exit;
  }
error_log("rabbit_search send: " . json_encode($request));
$rawResp = $client->send_request($request);

if (empty($rawResp)) {
throw new Exception('Empty response from DB listener');
  }

} 
catch (Throwable $e) {
error_log('rabbit_search: MQ error: ' . $e->getMessage());
http_response_code(502);
echo json_encode(['success' => false, 'message' => 'RabbitMQ error: ' . $e->getMessage()]);
exit;
}

if (is_string($rawResp)) {
$decoded = json_decode($rawResp, true);
if (json_last_error() === JSON_ERROR_NONE) {
$rawResp = $decoded;
 }
}

if (isset($rawResp['success']) || isset($rawResp['reviews'])) {
echo json_encode($rawResp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); 
exit;
}

$results = $rawResp['results'] ?? (is_array($rawResp) ? $rawResp : []);
function utf8ize($x) {
if (is_array($x)) { 
foreach ($x as $k => $v) $x[$k] = utf8ize($v); 
return $x; 
  }
if (is_string($x)) return mb_convert_encoding($x, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
return $x;
}
$results = utf8ize($results);

$clean = [];
foreach ($results as $g) {
$clean[] = [
'id'       => $g['id'] ?? 0,
'name'     => $g['name'] ?? 'Unknown',
'rating'   => $g['rating'] ?? 'N/A',
'released' => $g['released'] ?? 'N/A',
'image'    => $g['background_image'] ?? ($g['image'] ?? 'https://via.placeholder.com/250x150?text=No+Image'),
  ];
}

echo json_encode(['success' => true, 'results' => $clean], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;


















