<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Exceptions\ConnectionException;
use Knik\GRcon\Interfaces\ChatInterface;
use Knik\GRcon\Interfaces\ConfigurableAdapterInterface;
use Knik\GRcon\Interfaces\PlayersManageInterface;
use Knik\GRcon\Interfaces\ProtocolAdapterInterface;

class RustAdapter implements
    ProtocolAdapterInterface,
    ConfigurableAdapterInterface,
    PlayersManageInterface,
    ChatInterface
{
    /** @var SourceProtocol */
    private $client;

    /**
     * SourceAdapter constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->client = new SourceProtocol;
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
     * @return array
     */
    public function getPlayers(): array
    {
        $status = $this->execute('status');

        if (strlen($status) <= 0) {
            return [];
        }

        $count = preg_match_all('/^'
            . '\s*(?<id>\d*)\s*'
            . '\"(?<name>.*?)\"\s*'
            . '(?<ping>\d*)\s*'
            . '(?<connected>\d*s)\s*'
            . '(?<addr>[0-9\.]*)$'
            . '/mi',
            $status,
            $matches
        );

        if ($count <= 0) {
            return [];
        }

        $players = [];

        for ($i = 0; $i < $count; $i++) {
            if ($matches['addr'][$i] != 0) {
                $ip = explode(':', $matches['addr'][$i])[0];
            } else {
                $ip = '127.0.0.1';
            }

            $players[] = [
                // Common
                'id'        => $matches['id'][$i],
                'name'      => $matches['name'][$i],
                'ping'      => $matches['ping'][$i],
                'ip'        => $ip,

                // Rust
                'time'      => $matches['connected'][$i],
                'steamid'   => $matches['id'][$i],
            ];
        }

        return $players;
    }

    /**
     * @param $playerId
     * @param string $reason
     * @return mixed|void
     */
    public function kick($playerId, string $reason = '')
    {
        $this->execute("kick {$playerId} \"{$reason}\"");
    }

    /**
     * @param $playerId
     * @param string $reason
     * @param int $time
     * @return mixed|void
     */
    public function ban($playerId, string $reason = '', int $time = 0)
    {
        $this->execute("banid {$playerId} \"{$reason}\"");
    }

    /**
     * @param string $message
     * @return string
     */
    public function globalMessage(string $message): string
    {
        return $this->execute("say \"{$message}\"");
    }
}