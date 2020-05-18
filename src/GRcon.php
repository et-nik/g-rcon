<?php

namespace Knik\GRcon;

use Knik\GRcon\Interfaces\ProtocolAdapterInterface;

class GRcon extends GRconAbstract
{
    /**
     * RconClient constructor.
     * @param ProtocolAdapterInterface $adapter
     */
    public function __construct(?ProtocolAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
}