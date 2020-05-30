<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Exceptions\ConnectionException;
use Knik\GRcon\Exceptions\GRconException;
use Knik\GRcon\Interfaces\ChatInterface;
use Knik\GRcon\Interfaces\ConfigurableAdapterInterface;
use Knik\GRcon\Interfaces\PlayersManageInterface;
use Knik\GRcon\Interfaces\ProtocolAdapterInterface;

class SampAdapter implements
    ProtocolAdapterInterface,
    ConfigurableAdapterInterface,
    PlayersManageInterface,
    ChatInterface
{
    const RCON_TYPE_CLIENTS     = 'c';
    const RCON_TYPE_DETAILS     = 'd';
    const RCON_TYPE_INFO        = 'i';
    const RCON_TYPE_RULES       = 'r';
    const RCON_TYPE_PING        = 'p';
    const RCON_TYPE_EXECUTE     = 'x';

    const READ_MS_TIMEOUT       = 500000; // 0.5 sec

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

    /** @var bool */
    protected $connected = false;

    protected $configurable = [
        'host',
        'port',
        'password',
        'timeout',
    ];

    /**
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
     * @throws ConnectionException
     * @throws GRconException
     */
    public function connect(): void
    {
        $this->connected = false;

        set_error_handler(function () {
            throw new ConnectionException();
        });

        $this->validateHost();

        $this->connection = fsockopen("udp://" . $this->host, $this->port);

        if ( ! $this->connection) {
            throw new ConnectionException("Unable to connect");
        }

        stream_set_timeout($this->connection, $this->timeout);

        $packet = $this->packet(self::RCON_TYPE_PING);

        $this->writeSocket($packet);
        $read = $this->rawRead();

        restore_error_handler();

        if  ($read != $packet) {
            throw new ConnectionException("Unable to connect");
        }

        $this->connected = true;
    }

    public function disconnect(): void
    {
        set_error_handler(function () {});
        fclose($this->connection);
        restore_error_handler();
    }

    /**
     * @param $command
     * @return string
     */
    public function execute($command): string
    {
        $packet = $this->packet(self::RCON_TYPE_EXECUTE, $command);

        $this->writeSocket($packet);
        return $this->readCommandResult();
    }

    /**
     * @param $playerId
     * @param string $reason
     * @return mixed|string
     */
    public function kick($playerId, string $reason = '')
    {
        return $this->execute("kick {$playerId}");
    }

    /**
     * @return array
     */
    public function getPlayers(): array
    {
        // ID	Name	Ping	IP
        // 0	MyNickname	20	192.168.101.202
        $commandResult = $this->execute('players');

        $lines = explode("\n", $commandResult);
        array_shift($lines);

        $players = [];

        foreach ($lines as $line) {
            $playerInfo = explode("\t", $line);

            $players[] = [
                'id'        => $playerInfo[0],
                'name'      => $playerInfo[1],
                'ping'      => $playerInfo[2],
                'ip'        => $playerInfo[3],
            ];
        }

        return $players;
    }

    /**
     * @param $playerId
     * @param string $reason
     * @param int $time
     * @return mixed|string
     */
    public function ban($playerId, string $reason = '', int $time = 0)
    {
        return $this->execute("ban {$playerId}");
    }

    /**
     * @param string $message
     * @return string
     */
    public function globalMessage(string $message): string
    {
        return $this->execute("say {$message}");
    }

    /**
     * @param null $type
     * @param null $command
     * @return string
     */
    private function packet($type = null, $command = null)
    {
        if (! $type) {
            $type = self::RCON_TYPE_PING;
        }

        $packet = $this->getHeader()
            . $type;

        if ($type == self::RCON_TYPE_EXECUTE) {
            $packet .= pack('v', strlen($this->password))
                . $this->password
                . pack('v', strlen($command))
                . $command;
        } else {
            $packet .= "\x00\x00\x00\x00";
        }

        return $packet;
    }

    /**
     * @param string $write
     */
    private function writeSocket(string $write)
    {
        fputs($this->connection, $write, strlen($write));
    }

    /**
     * @return string
     */
    private function rawRead()
    {
        stream_set_timeout($this->connection, 0, self::READ_MS_TIMEOUT);

        $read = '';
        while (($c = fgetc($this->connection)) !== false) {
            $read .= $c;
        }

        stream_set_timeout($this->connection, 0, $this->timeout);

        return $read;
    }

    /**
     * @return string
     */
    private function readCommandResult()
    {
        stream_set_timeout($this->connection, 0, self::READ_MS_TIMEOUT);

        $buffer = '';

        while (($c = fgetc($this->connection)) !== false) {
            $buffer .= $c;
        }

        stream_set_timeout($this->connection, $this->timeout);

        if (empty($buffer)) {
            return '';
        }

        $lines = [];
        foreach (explode($this->getHeader(), $buffer) as $line) {
            $lines[] = substr($line, 3);
        }

        array_shift($lines);

        return implode("\n", $lines);
    }

    /**
     * @return string
     */
    private function getHeader()
    {
        $ip = ip2long(gethostbyname($this->host));
        return "SAMP" . pack('Nn', $ip, $this->port);
    }

    /**
     * @return void
     * @throws GRconException
     */
    private function validateHost()
    {
       $ip = (! filter_var($this->host, FILTER_VALIDATE_IP))
           ? $this->host
           : gethostbyname($this->host);

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new GRconException('Invalid host');
        }
    }
}