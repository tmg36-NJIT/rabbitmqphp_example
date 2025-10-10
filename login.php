// ok alr so this gonna be the file for the login page
 <?php
error_reporting(E_ALL);
ini_set('display_errors',1);
session_start();

require_once('/var/www/html/path.inc');
require_once('/var/www/html/get_host_info.inc');
require_once('/var/www/html/rabbitMQLib.inc');

$errs = [];
$okMsg = '';
$error_message='';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$user = trim($_POST['username'] ?? '');
$pass = trim($_POST['password'] ?? '');
if ($user === '') $errs[] = 'You need a username.';
if ($pass === '') $errs[] = 'You need a password.'
if (!$errs) {
try {
$mq = new rabbitMQClient('/var/www/html/testRabbitMQ.ini','testServer');
$req = ['type'=>'login','username'=>$user,'password'=>$pass];
$res = $mq->send_request($req);

if ($res && isset($res['success']) && $res['success'] === true) {
$_SESSION['username'] = $user;
$_SESSION['session_key'] = $res['session_key'] ?? null;
header("Location: home.php");
exit;
} else {
$error_message = $res['message'] ?? "Invalid credentials.";
}
} catch (Exception $e) {
$error_message = "Oh no! There's an error connecting to authentication service.";
}

?>
<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
<h2 style="text-align:center;">Login</h2>

<?php if($errs): ?>
<div style="color:red;">
<?php foreach($errs as $e): ?>
<p><?php echo $e; ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if($error_message): ?>
<div style="color:red;">
<p><?php echo $error_message; ?></p>
</div>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'); ?>">
<label>Username:</label>
<input type="text" name="username" required><br><br>
<label>Password:</label>
<input type="password" name="password" required><br><br>
<button type="submit">Login</button>
</form>
</body>
</html>
