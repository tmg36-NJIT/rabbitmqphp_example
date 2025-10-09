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
$user=($_POST['username'] ?? '');
$pass=($_POST['password'] ?? '');

}

?>

