<?php

include "../vendor/autoload.php";

use Knik\GRcon\EasyGRcon;

$rcon = new EasyGRcon('tf2', [
    'host'          => '127.0.0.1',
    'port'          => 27015,
    'password'      => '123'
]);

// Execute Command
echo $rcon->execute('status');

// Get Client List
print_r($rcon->getPlayers());