<?php

include "../vendor/autoload.php";

use Knik\GRcon\EasyGRcon;

$rcon = new EasyGRcon('csgo', [
    'host'          => '95.213.175.171',
    'port'          => 27015,
    'password'      => '123'
]);

// Get Client List
//print_r($rcon->getPlayers());


print_r($rcon->execute("status"));
