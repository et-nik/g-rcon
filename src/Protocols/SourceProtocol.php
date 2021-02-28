<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Exceptions\ConnectionException;
use Knik\GRcon\Interfaces\SocketClientInterface;
use Knik\GRcon\Internal\SocketClientFactory;

/**
 * Class SourceProtocol
 * @package Knik\GRcon\Protocols
 * @internal
 */
class SourceProtocol
{
    private const SERVERDATA_EXECCOMMAND = 2;
    private const SERVERDATA_AUTH = 3;
    
    /**
     * @var SocketClientInterface|null
     */
    private $connection;

    /**
     * @var int
     */
    private $packetId;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $password;

    /**
     * @var int
     */
    protected $timeout = 5;

    protected $configurable = [
        'host',
        'port',
        'password',
        'timeout',
    ];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config): SourceProtocol
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

    public function setConnection(SocketClientInterface $connection): void
    {
        $this->connection = $connection;
    }

    public function connect(): void
    {
        $this->connection = SocketClientFactory::create("tcp://{$this->host}:{$this->port}", $this->timeout);
        $this->connection->setOption(SOL_SOCKET, SO_RCVTIMEO, ['sec' => 0, 'usec' => 100000]);
        $this->auth();
    }

    public function disconnect(): void
    {
        $this->connection->close();
    }

    public function execute($command): string
    {
        $this->write(self::SERVERDATA_EXECCOMMAND, $command);

        $response = $this->read();

        if (empty($response)) {
            return '';
        }

        if (isset($response[$this->packetId]['body'])) {
            return trim($response[$this->packetId]['body'], "\x00");
        }

        if (isset($response[0]['body'])) {
            return $response[0]['body'];
        }

        return '';
    }

    private function auth()
    {
        if (!$this->connection) {
            return false;
        }

        $this->write(self::SERVERDATA_AUTH, $this->password);

        // Real response (id: -1 = failure)
        $ret = $this->packetRead();

        if (false === $ret) {
            return false;
        }

        if (!isset($ret[1]['ID'])) {
            return false;
        }

        if ($ret[1]['ID'] === -1) {
            return false;
        }

        return true;
    }

    private function write($type, $command = '')
    {
        $id = ++$this->packetId;

        $data = pack("VV", $id, $type) . $command . "\x00\x00";
        $data = pack("V", strlen($data)) . $data;

        $this->connection->write($data);
        usleep(100);

        return $id;
    }

    private function packetRead()
    {
        $retarray = [];

        while ($size = $this->connection->read(4)) {

            $size = unpack('V1size', $size);

            if ($size['size'] > 4096) {
                $packet = $this->connection->read(4096);
            } else {
                $packet = $this->connection->read($size['size']);
            }

            $retarray[] = unpack("V1ID/V1type/a*body", $packet);
        }

        return $retarray;
    }

    private function read()
    {
        $packets = $this->packetRead();
        $result = [];

        foreach($packets as $pack) {
            $result[$pack['ID']] = [
                'type'      => $pack['type'],
                'body'      => $pack['body'],
            ];
        }

        return $result;
    }
}
