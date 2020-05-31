<?php

include "../vendor/autoload.php";

use Knik\GRcon\EasyGRcon;

$rcon = new EasyGRcon('minecraft', [
    'host'          => '95.213.175.173',
    'port'          => 25570,
    'password'      => 'HIMbI5cpip'
]);

echo $rcon->execute('data get entity 38717eba-4c75-3c9c-8f6f-699839f7bf02 Inventory');
print_r($rcon->getPlayers());

$rcon->kick("Nik", "azaza");