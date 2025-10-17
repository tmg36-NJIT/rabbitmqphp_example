// this file will handle the logic for deliverables + API calls to be handled through the message broker (RabbitMQ).
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

