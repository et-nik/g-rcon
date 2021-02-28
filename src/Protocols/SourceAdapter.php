<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Exceptions\ConnectionException;
use Knik\GRcon\Interfaces\ConfigurableAdapterInterface;
use Knik\GRcon\Interfaces\PlayersManageInterface;
use Knik\GRcon\Interfaces\ProtocolAdapterInterface;
use Knik\GRcon\Interfaces\SocketClientInterface;

class SourceAdapter implements
    ProtocolAdapterInterface,
    ConfigurableAdapterInterface,
    PlayersManageInterface
{
    /** @var SourceProtocol */
    private $client;

    /**
     * SourceAdapter constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->client = new SourceProtocol();
        $this->setConfig($config);
    }

    /**
     * @param array $config
     * @return $this|SourceAdapter
     */
    public function setConfig(array $config)
    {
        $this->client->setConfig($config);
        return $this;
    }

    /**
     * @throws ConnectionException
     */
    public function connect(): void
    {
        $this->client->connect();
    }

    public function setConnection(SocketClientInterface $connection): void
    {
        $this->client->setConnection($connection);
    }

    /**
     * @param $command
     * @return string
     */
    public function execute($command): string
    {
        return $this->client->execute($command);
    }

    public function disconnect(): void
    {
        $this->client->disconnect();
    }

    /**
     * @param $playerId
     * @param string $reason
     * @return mixed|void
     */
    public function kick($playerId, string $reason = '')
    {
        $this->execute("kickid {$playerId}");
    }

    /**
     * @param $playerId
     * @param string $reason
     * @param int $time
     * @return mixed|void
     */
    public function ban($playerId, string $reason = '', int $time = 0)
    {
        $this->execute("banid {$time} {$playerId} kick");
    }

    /**
     * @return array
     */
    public function getPlayers(): array
    {
        $status = $this->execute('status');

        if (strlen($status) <= 0) {
            return [];
        }

        $count = preg_match_all('/^'
            . '#\s*(?<userid>\d*)\s*'
            . '\d*\s*'
            . '"(?<name>.*?)"\s*'
            . '\[?(?<uniqueid>[a-zA-Z0-9_:]*)\]?\s*'
            . '(?<connected>[0-9:]*)?\s*'
            . '(?<ping>\d*)?\s*'
            . '(?<loss>\d*)?\s*'
            . '(?<state>[a-zA-Z0-9_:]*)\s*'
            . '(?<adr>[0-9.]*:\d*)?$'
            . '/mi',

            $status,
            $matches
        );

        if ($count <= 0) {
            return [];
        }

        $players = [];

        for ($i = 0; $i < $count; $i++) {
            if ($matches['adr'][$i] != 0) {
                $ip = explode(':', $matches['adr'][$i])[0];
            } else {
                $ip = '127.0.0.1';
            }

            $players[] = [
                // Common
                'id'        => $matches['userid'][$i],
                'name'      => $matches['name'][$i],
                'ping'      => $matches['ping'][$i],
                'loss'      => $matches['loss'][$i],
                'ip'        => $ip,

                // Source
                'steamid'   => $matches['uniqueid'][$i],
                'time'      => $matches['connected'][$i],
                'state'     => $matches['state'][$i],
            ];
        }

        return $players;
    }
}
