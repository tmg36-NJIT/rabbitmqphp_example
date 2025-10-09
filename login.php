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
