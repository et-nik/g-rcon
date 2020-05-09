<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Interfaces\ConfigurableAdapterInterface;
use Knik\GRcon\Interfaces\ProtocolAdapterInterface;

class SampAdapter implements ProtocolAdapterInterface, ConfigurableAdapterInterface
{

    public function __construct(array $config)
    {
    }

    public function connect(): void
    {
        // TODO: Implement connect() method.
    }

    public function disconnect(): void
    {
        // TODO: Implement disconnect() method.
    }

    public function execute($command): string
    {
        // TODO: Implement execute() method.
    }
}