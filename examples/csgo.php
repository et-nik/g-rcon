<?php

include "../vendor/autoload.php";

use Knik\GRcon\EasyGRcon;

$rcon = new EasyGRcon('csgo', [
    'host'          => '127.0.0.1',
    'port'          => 27019,
    'password'      => 'whIBOkRBR8'
]);

// Get Client List
print_r($rcon->getPlayers());
