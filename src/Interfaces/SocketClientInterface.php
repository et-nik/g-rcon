<?php

namespace Knik\GRcon\Interfaces;

interface SocketClientInterface
{
    public function write($buffer): int;

    public function read($length, $type = PHP_BINARY_READ): string;

    public function setOption(int $level, int $optname, $optval): void;

    public function recv($length, $flags): string;

    public function setBlocking($toggle = true): void;

    public function close(): void;
}
