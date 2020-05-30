<?php

namespace Knik\GRcon;

use Knik\GRcon\Exceptions\PlayersManageNotSupportedExceptions;
use Knik\GRcon\Interfaces\ChatInterface;
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
     * @return bool
     */
    public function isChatSupported(): bool
    {
        return $this->adapter instanceof ChatInterface;
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

        if (! $this->isConnected) {
            $this->adapter->connect();
            $this->isConnected = true;
        }

        return $this->adapter->getPlayers();
    }

    /**
     * @param $playerId
     * @param string $reason
     * @return mixed
     * @throws PlayersManageNotSupportedExceptions
     */
    public function kick($playerId, string $reason = '')
    {
        if (!$this->isPlayersManageSupported()) {
            throw new PlayersManageNotSupportedExceptions;
        }

        if (! $this->isConnected) {
            $this->adapter->connect();
            $this->isConnected = true;
        }

        return $this->adapter->kick($playerId, $reason);
    }

    /**
     * @param $playerId
     * @param string $reason
     * @param int $time
     * @return mixed
     * @throws PlayersManageNotSupportedExceptions
     */
    public function ban($playerId, string $reason = '', int $time = 0)
    {
        if (!$this->isPlayersManageSupported()) {
            throw new PlayersManageNotSupportedExceptions;
        }

        if (! $this->isConnected) {
            $this->adapter->connect();
            $this->isConnected = true;
        }

        return $this->adapter->ban($playerId, $reason, $time);
    }

    /**
     * @param string $message
     * @return string
     * @throws PlayersManageNotSupportedExceptions
     */
    public function globalMessage(string $message): string
    {
        if (!$this->isChatSupported()) {
            throw new PlayersManageNotSupportedExceptions;
        }

        if (! $this->isConnected) {
            $this->adapter->connect();
            $this->isConnected = true;
        }

        return $this->adapter->globalMessage($message);
    }

    public function __destruct()
    {
        if ($this->isConnected) {
            $this->adapter->disconnect();
        }
    }
}