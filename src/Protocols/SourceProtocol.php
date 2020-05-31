<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Exceptions\ConnectionException;

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
     * @var resource|false
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

    public function connect(): void
    {
        set_error_handler(function ($errSeverity, $errMsg) {
            throw new ConnectionException($errMsg);
        });

        $this->connection = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        restore_error_handler();

        if ( ! $this->connection) {
            throw new ConnectionException("Unable to connect");
        }

        stream_set_blocking($this->connection, 0);
        stream_set_timeout($this->connection, 5);
        $this->auth();
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            fclose($this->connection);
        }
    }

    public function execute($command): string
    {
        $this->write(self::SERVERDATA_EXECCOMMAND, $command,'');

        $response = $this->read();

        //ATM: Source servers don't return the request id, but if they fix this the code below should read as
        if (isset($response[$this->packetId]['S1'])) {
            return $response[$this->packetId]['S1'];
        }
        else {
            return $response[0]['S1'];
        }
    }

    private function auth()
    {
        if (!$this->connection) {
            return false;
        }

        $this->write(self::SERVERDATA_AUTH, $this->password);

        // Real response (id: -1 = failure)
        $ret = $this->packetRead();

        if (false == $ret) {
            return false;
        }

        if (@$ret[1]['ID'] == -1) {
            return false;
        } else {
            return true;
        }
    }

    private function write($cmd, $s1 = '', $s2 = '')
    {
        if (!is_resource($this->connection)) {
            return 0;
        }

        // Get and increment the packet id
        $id = ++$this->packetId;

        // Put our packet together
        $data = pack("VV", $id, $cmd) . $s1 . chr(0) . $s2 . chr(0);

        // Prefix the packet size
        $data = pack("V", strlen($data)) . $data;

        // Send packet
        @fwrite($this->connection, $data, strlen($data));
        sleep(1);

        // In case we want it later we'll return the packet id
        return $id;
    }

    private function packetRead()
    {
        //Declare the return array
        $retarray = array();

        //Fetch the packet size
        while ($size = @fread($this->connection,4)) {

            $size = unpack('V1Size',$size);
            //Work around valve breaking the protocol

            if ($size["Size"] > 4096) {
                //pad with 8 nulls
                $packet = "\x00\x00\x00\x00\x00\x00\x00\x00".fread($this->connection, 4096);
            } else {
                //Read the packet back
                $packet = @fread($this->connection,$size["Size"]);
            }
            array_push($retarray, unpack("V1ID/V1Response/a*S1/a*S2",$packet));
        }

        return $retarray;
    }

    private function read()
    {
        $packets = $this->packetRead();
        $ret = NULL;

        foreach($packets as $pack) {
            if (isset($ret[$pack['ID']])) {
                $ret[$pack['ID']]['S1'] .= $pack['S1'];
                $ret[$pack['ID']]['S2'] .= $pack['S1'];
            } else {
                $ret[$pack['ID']] = array(
                    'Response' => $pack['Response'],
                    'S1' => $pack['S1'],
                    'S2' =>	$pack['S2'],
                );
            }
        }

        return $ret;
    }
}