<?php

namespace Knik\GRcon\Internal;

use Knik\GRcon\Exceptions\ConnectionException;
use Knik\GRcon\Interfaces\SocketClientInterface;
use Socket\Raw\Factory;

class SocketClientFactory
{
    public static function create($address, $timeout = null): SocketClientInterface
    {
        $factory = new Factory();
        try {
            $socket = $factory->createClient($address, $timeout);
        } catch (\Exception $e) {
            throw new ConnectionException("Unable to connect", $e->getCode(), $e);
        }

        return new SocketClient($socket);
    }
}
