<?php

namespace Knik\GRcon\Interfaces;

interface ProtocolAdapterInterface
{
    /**
     * Connect to server
     */
    public function connect(): void;

    /**
     * Disconnect from server
     */
    public function disconnect(): void;

    /**
     * Execute command
     *
     * @param $command
     * @return string
     */
    public function execute($command): string;
}