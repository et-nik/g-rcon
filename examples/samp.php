<?php

include "../vendor/autoload.php";

use Knik\GRcon\EasyGRcon;

$rcon = new EasyGRcon('samp', [
    'host'          => '172.17.0.3',
    'port'          => '7777',
    'password'      => 'test'
]);

// Execute Command
echo $rcon->execute('version');
echo "\n\n\n";
echo $rcon->execute('cmdlist');
echo "\n\n\n";
print_r($rcon->getPlayers());
