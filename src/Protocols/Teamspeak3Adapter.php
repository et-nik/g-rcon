<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Exceptions\ConnectionException;
use Knik\GRcon\Interfaces\ConfigurableAdapterInterface;
use Knik\GRcon\Interfaces\PlayersManageInterface;
use Knik\GRcon\Interfaces\ProtocolAdapterInterface;
use Knik\GRcon\Interfaces\ChatInterface;
use phpseclib\Net\SSH2;

class Teamspeak3Adapter implements
    ProtocolAdapterInterface,
    ConfigurableAdapterInterface,
    PlayersManageInterface,
    ChatInterface
{
    const STATUS_OK = 0;

    /** @var SSH2 */
    private $connection;

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var string */
    private $login = 'serveradmin';

    /** @var string */
    private $password;

    /** @var int */
    protected $timeout = 5;

    protected $configurable = [
        'host',
        'port',
        'login',
        'password',
        'timeout',
    ];

    /**
     * SourceAdapter constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

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
        $this->connection = new SSH2($this->host, $this->port, $this->timeout);

        if (! $this->connection->login($this->login, $this->password)) {
            throw new ConnectionException("Unable to connect/login");
        }

        $header = trim($this->connection->read("\n"));

        if ($header != 'TS3') {
            throw new ConnectionException("Not a TS3 server");
        }

        $this->connection->setTimeout($this->timeout);

        $this->connection->read("{$this->login}> ");
    }

    public function disconnect(): void
    {
        $this->connection->disconnect();
    }

    public function execute($command): string
    {
        $this->execAndParse('use sid=1');

        $result = $this->execAndParse($command);

        if ($result['errorId'] != self::STATUS_OK) {
            return $result['message'];
        }

        return $result['output'];
    }

    /**
     * @return array
     */
    public function getPlayers(): array
    {
        // TODO: Implement getting players from all virtual server
        // $serverList = $this->execAndParse('serverlist');

        $this->execute('use sid=1');

        $clientResult = $this->execAndParse('clientlist');

        if ($clientResult['errorId'] !== self::STATUS_OK) {
            return [];
        }

        $clientExplode = explode('|', $clientResult['output']);

        if (empty($clientExplode)) {
            return [];
        }

        $players = [];

        foreach ($clientExplode as $client) {
            $clientInfo = $this->parseVars($client);

            $players[] = [
                // Common
                'id'        => $clientInfo['clid'],
                'name'      => $clientInfo['client_nickname'],

                // TeamSpeak
                'clid'                  => $clientInfo['clid'],
                'cid'                   => $clientInfo['cid'],
                'type'                  => $clientInfo['client_type'],
                'client_database_id'    => $clientInfo['client_database_id']
            ];
        }

        return $players;
    }

    /**
     * @param $playerId
     * @param string $reason
     * @return mixed|string
     */
    public function kick($playerId, string $reason = '')
    {
        return $this->ban($playerId, $reason, 1);
    }

    /**
     * @param $playerId
     * @param string $reason
     * @param int $time
     * @return mixed|string
     */
    public function ban($playerId, string $reason = '', int $time = 0)
    {
        $this->execute('use sid=1');

        $reason = str_replace(' ', '\s', $reason);

        $result = $this->execAndParse("banclient clid={$playerId} time={$time} banreason={$reason}");
        return $result['message'];
    }

    /**
     * @param string $message
     * @return string
     */
    public function globalMessage(string $message): string
    {
        $message = str_replace(' ', '\s', $message);

        return $this->execAndParse("gm msg={$message}")['message'];
    }

    /**
     * @param $command
     * @return string[]
     */
    private function execAndParse($command)
    {
        $this->connection->write("{$command}\n");
        $read = $this->connection->read("/^{$this->login}.*>\s*/m", SSH2::READ_REGEX);

        if (empty($read)) {
            return [
                'output' => '',
                'errorId' => -1,
                'message' => 'Invalid read string'
            ];
        }

        $read = str_replace(["\r\n", "\n\r"], "\n", $read);

        $lines = explode("\n", $read);

        array_shift($lines);
        array_pop($lines);
        $statusMessage = array_pop($lines);

        $status = $this->parseVars($statusMessage);

        return [
            'output' => implode("\n", $lines),
            'errorId' => (int) ($status['id'] ?? -1),
            'message' => $status['msg'] ?? 'Invalid status message'
        ];
    }

    /**
     * Parse vars string to array
     *
     * Example
     *      "virtualserver_id=1 virtualserver_port=9987"  ==>  ['virtualserver_id' => '1', 'virtualserver_port' => 9987]
     *
     * @param string $varsString
     * @return array
     */
    private function parseVars(string $varsString): array
    {
        $vars = explode(' ', $varsString);
        $parsedVars = [];

        foreach ($vars as $var) {
            if (empty($var)) {
                continue;
            }

            $explode = explode('=', $var);

            if (empty($explode)) {
                continue;
            }

            $varName = $explode[0];
            $varValue = $explode[1] ?? '';

            $parsedVars[$varName] = $varValue;
        }

        return $parsedVars;
    }
}