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

$req=[
'type'=>'register',
'username'=>$user,
'password' ->$pass
];

$res=$mq->send_request($req); //not sure yet but might switch to publish function if apache hangs during testing

if (isset($res['success']) && $res['success'] === true {
$okMsg=htmlspecialchars($res['message']); }
else {
$errs[]=htmlspecialchars($res['message'] ??  'Unfortunately, Your registration has failed. Try again please!')
}

//some additional error logging
} catch(Exception $e) {
$errs[]='Hello!! Try this again later. Something unexpected occurred."
error_log('register.php-> ' . $e->getMessage());
?>

