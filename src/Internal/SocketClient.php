<?php

namespace Knik\GRcon\Internal;

use Knik\GRcon\Interfaces\SocketClientInterface;
use Socket\Raw\Socket;

class SocketClient implements SocketClientInterface
{
    /** @var Socket */
    private $socket;

    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    public function write($buffer): int
    {
        return $this->socket->write($buffer);
    }

    public function read($length, $type = PHP_BINARY_READ): string
    {
        try {
            $result = $this->socket->read($length, $type);
        } catch (\Exception $e) {
            $result = '';
        }

        return $result;
    }

    public function recv($length, $flags): string
    {
        return $this->socket->recv($length, $flags);
    }

    public function setOption(int $level, int $optname, $optval): void
    {
        $this->socket->setOption($level, $optname, $optval);
    }

    public function setBlocking($toggle = true): void
    {
        $this->socket->setBlocking($toggle);
    }

    public function close(): void
    {
        $this->socket->close();
    }
}
