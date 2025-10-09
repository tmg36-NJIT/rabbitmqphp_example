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



