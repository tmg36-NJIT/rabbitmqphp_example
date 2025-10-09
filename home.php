// official landing page after login is successful - tmg
<?php
require_once('rabbitMQLib.inc');
session_start();
if (!isset($_SESSION['username'])) {
header('Location: login.php');
exit;
}
$user = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>
<head><title>Homepage</title></head>
<body>
<h2>Welcome, <?php echo htmlspecialchars($user); ?>!</h2>
<p>Nice. You are logged in, successfully.</p>
<a href="logout.php">Logout</a>
</body>
</html>

