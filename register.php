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
$errs[]="There needs to be a password. Try again!";
}

if(empty($errs)) {
try{
$mq = new rabbitMQClient('/var/www/html/testRabbitMQ.ini', 'testServer'); //mq for the client

$req=[
'type'=>'register',
'username'=>$user,
'password'=>$pass
];

$res=$mq->send_request($req); //not sure yet but might switch to publish function if apache hangs during testing

if (isset($res['success']) && $res['success'] === true) {
$okMsg=htmlspecialchars($res['message']); }
else {
$errs[]=htmlspecialchars($res['message'] ??  'Unfortunately, Your registration has failed. Try again please!');
}

//some additional error logging
} catch(Exception $e) {
$errs[]='Hello! Something unexpected occurred. Please try again later.';
error_log('register.php-> ' . $e->getMessage());
}
}
}

?>
// front end layout section


<!DOCTYPE html>
<html>
<head><title>Sign Up</title></head>
<body>
<h2 style="text-align:center;">Create Account</h2>
<title> Register </title>
<style>
body {
  font-family: Arial, sans-serif;
  background: #eee;
  margin: 0;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;

}
</style>


<?php if (!empty($errs)): ?>
<div style="color:red;">
<?php foreach ($errs as $err): ?>
<p><?php echo $err; ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($okMsg): ?>
<div style="color:green;">
<?php echo $okMsg; ?>
</div>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
<label for="username">Enter Username:</label>
<input type="text" id="username" name="username" required><br><br>

<label for="password">Enter Password:</label>
<input type="password" id="password" name="password" required><br><br>

<button type="submit">Click here to Register.</button>
</form>
</body>
</html>
