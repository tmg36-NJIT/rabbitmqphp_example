// To store the main logic for registration form data to send to the msg broker. - tmg
<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();
require_once('/var/www/html/path.inc');
require_once('/var/www/html/get_host_info.inc');
require_once('/var/www/html/rabbitMQLib.inc');

$mq=new rabbitMQClient("testRabbitMQ.ini","testServer");
echo "Attempting to connect to Queue....<br>";
if (!isset($_POST['username']) || !isset($_POST['password'])) {
echo "Theres Some Missing fields.";
exit;
}
$user = trim($_POST['username']);
$pass = trim($_POST['password']);
if($user==='' || $pass==='') {
echo "Dont leave field empty. Both username & password are required.";
exit; }

$req = [
'type'=>'register',
'username'=>$user,
'password'=>$pass
];

try {
$res = $mq->send_request($req);
if (!empty($res['success'])) {
echo htmlspecialchars($res['message']);
} else {
echo htmlspecialchars($res['message'] ?? 'Unfortunately, Could not register.');
}
} catch (Exception $e) {
echo "Sorry! Looks like we had an error wile sending request: ".$e->getMessage();
exit;
}
?>

