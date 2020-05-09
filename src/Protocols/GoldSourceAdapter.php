<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Exceptions\ConnectionException;
use Knik\GRcon\Exceptions\GRconException;
use Knik\GRcon\Interfaces\ConfigurableAdapterInterface;
use Knik\GRcon\Interfaces\ProtocolAdapterInterface;

class GoldSourceAdapter implements ProtocolAdapterInterface, ConfigurableAdapterInterface
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

        return $result;
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
}