<?php

include "../vendor/autoload.php";

use Knik\GRcon\EasyGRcon;

$rcon = new EasyGRcon('goldsource', [
    'host'          => '127.0.0.1',
    'port'          => '27018',
    'password'      => 'rconPassword'
]);

$result = $rcon->execute('status');
print_r($result);