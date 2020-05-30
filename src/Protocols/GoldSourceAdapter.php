<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Exceptions\ConnectionException;
use Knik\GRcon\Exceptions\GRconException;
use Knik\GRcon\Interfaces\ConfigurableAdapterInterface;
use Knik\GRcon\Interfaces\PlayersManageInterface;
use Knik\GRcon\Interfaces\ProtocolAdapterInterface;

class GoldSourceAdapter implements ProtocolAdapterInterface, PlayersManageInterface, ConfigurableAdapterInterface
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port = 27015;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var int
     */
    protected $timeout = 5;

    /**
     * @var resource
     */
    protected $connection;

    protected $configurable = [
        'host',
        'port',
        'password',
        'timeout',
    ];

    /**
     * @var int
     */
    private $challengeNumber;

    /**
     * GoldSourceAdapter constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * Set the config.
     *
     * @param array $config
     *
     * @return $this
     */
    public function setConfig(array $config)
    {
        foreach ($this->configurable as $optionName) {
            if ( ! isset($config[$optionName])) {
                continue;
            }

            if (property_exists(self::class, $optionName)) {
                $this->{$optionName} = $config[$optionName];
            }
        }

        return $this;
    }

    /**
     * Connect to server
     * @throws GRconException
     */
    public function connect(): void
    {
        set_error_handler(function () {
            throw new ConnectionException();
        });

        $this->connection = fsockopen("udp://" . $this->host, $this->port);

        if ( ! $this->connection) {
            throw new ConnectionException("Unable to connect");
        }

        stream_set_timeout($this->connection, $this->timeout);
        $this->challengeNumber();

        restore_error_handler();
    }

    /**
     * Disconnect form server
     */
    public function disconnect(): void
    {
        fclose($this->connection);
    }

    /**
     * @param $command
     * @return string
     */
    public function execute($command): string
    {
        $firstCommand = true;
        $result = '';

        while (true) {

            $cmdPartResult = $firstCommand
                ? $this->writeAndReadSocket("\xff\xff\xff\xffrcon $this->challengeNumber \"$this->password\" $command")
                : $this->writeAndReadSocket("\xff\xff\xff\xffrcon $this->challengeNumber \"$this->password\"");

            $result .= $cmdPartResult;

            if (strlen($cmdPartResult) < 256) {
                break;
            }

            $firstCommand = false;
        }

        return mb_convert_encoding($result, 'UTF-8', 'UTF-8');
    }

    /**
     * @return mixed|string
     * @throws GRconException
     */
    private function challengeNumber()
    {
        $this->challengeNumber = $this->writeAndReadSocket("\xff\xff\xff\xffchallenge rcon");

        if (!empty($this->challengeNumber)) {
            $challenge = explode(" ", $this->challengeNumber);

            if (!is_array($challenge) || count($challenge) < 3) {
                throw new GRconException('Invalid challenge');
            }

            $this->challengeNumber = $challenge[2];
            return $this->challengeNumber;
        } else {
            throw new GRconException;
        }
    }

    /**
     * @param $command
     * @return string
     */
    private function writeAndReadSocket($command)
    {
        fputs($this->connection, $command, strlen($command));
        $buffer =  fread($this->connection, 1);
        $status = socket_get_status($this->connection);
        $buffer .= fread($this->connection, $status["unread_bytes"]);

        return trim(substr($buffer, 5));
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

        $count = preg_match_all('/^\#\s?\d*\s*\"(?<name>.*?)\"\s*(?<userid>\d*)\s*(?<uniqueid>[a-zA-Z0-9\_\:]*)\s*(?<frag>hltv\:\d*\/\d* delay\:\d*|[a-z\-\:0-9]*)\s*(?<time>[0-9\:]*)\s*(?<ping>\s*|\d*)\s*(?<loss>\s*|\d*)\s*(?<addr>[0-9\.]*\:\d*|0)$/mi',
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
                'id'        => $matches['userid'][$i],
                'name'      => $matches['name'][$i],
                'ping'      => (int)$matches['ping'][$i],
                'score'     => (int)$matches['frag'][$i],
                'loss'      => (int)$matches['loss'][$i],
                'ip'        => $ip,

                // GoldSource
                'steamid'   => $matches['uniqueid'][$i],
                'time'      => $matches['time'][$i],
            ];
        }

        return $players;
    }

    /**
     * @param mixed $playerId
     * @param string $reason
     * @return string
     */
    public function kick($playerId, string $reason = '')
    {
        return $this->execute("kick #{$playerId}");
    }

    /**
     * @param mixed $playerId
     * @param string $reason
     * @param int $time
     * @return string
     */
    public function ban($playerId, string $reason = '', int $time = 0)
    {
        return $this->execute("banid {$time} #{$playerId}");
    }
}