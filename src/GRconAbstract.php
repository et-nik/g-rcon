<?php

namespace Knik\GRcon;

use Knik\GRcon\Exceptions\PlayersManageNotSupportedExceptions;
use Knik\GRcon\Interfaces\PlayersManageInterface;
use Knik\GRcon\Interfaces\ProtocolAdapterInterface;

abstract class GRconAbstract
{
    /**
     * @var ProtocolAdapterInterface
     */
    protected $adapter;

    /**
     * @var bool
     */
    private $isConnected = false;

    /**
     * @param $command
     * @return string
     */
    public function execute($command): string
    {
        if (! $this->isConnected) {
            $this->adapter->connect();
            $this->isConnected = true;
        }

        return $this->adapter->execute($command);
    }

    /**
     * @return bool
     */
    public function isPlayersManageSupported(): bool
    {
        return $this->adapter instanceof PlayersManageInterface;
    }

    /**
     * @return array
     * @throws PlayersManageNotSupportedExceptions
     */
    public function getPlayers(): array
    {
        if (!$this->isPlayersManageSupported()) {
            throw new PlayersManageNotSupportedExceptions;
        }

        return $this->adapter->getPlayers();
    }

    public function __destruct()
    {
        if ($this->isConnected) {
            $this->adapter->disconnect();
        }
    }
}