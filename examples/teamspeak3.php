<?php

include "../vendor/autoload.php";

use Knik\GRcon\EasyGRcon;

$rcon = new EasyGRcon('ts3', [
    'host'          => '172.17.0.3',
    'port'          => '10022',
    'password'      => 'jfreKJ3CUJ'
]);

// Execute Command
echo $rcon->execute('serverlist');

// Get Client List
print_r($rcon->getPlayers());

// Sent global message to chat
$rcon->globalMessage('Hello World');