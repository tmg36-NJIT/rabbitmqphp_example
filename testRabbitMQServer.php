#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function doLogin($username, $password)
{
    // Replace with DB lookup later
    echo "Login attempt: $username with password $password\n";
    return [
        'success' => true,
        'message' => "Login OK (worker mock)"
    ];
}

function doRegister($username, $password)
{
    // Replace with DB insert later
    echo "Register attempt: $username\n";
    return [
        'success' => true,
        'message' => "Register OK (worker mock)"
    ];
}

function requestProcessor($request)
{
    echo "Received request:\n";
    var_dump($request);

    if (!isset($request['type'])) {
        return ['success' => false, 'message' => "ERROR: unsupported message type"];
    }

    switch ($request['type']) {
        case "login":
            return doLogin($request['username'], $request['password']);
        case "register":
            return doRegister($request['username'], $request['password']);
        default:
            return ['success' => false, 'message' => "Unknown request type"];
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN\n";
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END\n";
exit();
?>
