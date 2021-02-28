<?php

namespace Knik\GRcon\Tests;

use Knik\GRcon\Exceptions\PlayersManageNotSupportedExceptions;
use Knik\GRcon\Interfaces\PlayersManageInterface;
use Knik\GRcon\Interfaces\ProtocolAdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Knik\GRcon\GRcon;

/**
 * @covers \Knik\GRcon\GRcon
 * @covers \Knik\GRcon\GRconAbstract
 */
class GRconAbstractTest extends TestCase
{
    /**
     * @var ProtocolAdapterInterface|MockObject
     */
    protected $adapterMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->adapterMock = $this->createMock(PlayersAdapter::class);
    }

    public function testExecute(): void
    {
        $this->adapterMock->method('connect');
        $this->adapterMock->method('execute')->willReturn('command result');

        $rcon = new GRcon($this->adapterMock);

        $result = $rcon->execute('command');
        $this->assertEquals('command result', $result);
    }

    public function testIsPlayersManageSupportedSuccess(): void
    {
        $adapter = new PlayersAdapter;

        $rcon = new GRcon($adapter);
        $this->assertTrue($rcon->isPlayersManageSupported());
    }

    public function testIsPlayersManageSupportedNotSupported(): void
    {
        $adapter = new Adapter;

        $rcon = new GRcon($adapter);
        $this->assertFalse($rcon->isPlayersManageSupported());
    }

    public function testGetPlayers()
    {
        $this->adapterMock->method('connect');
        $this->adapterMock->method('getPlayers')->willReturn([
            'name' => 'player',
            'score' => 1337
        ]);

        $rcon = new GRcon($this->adapterMock);

        $result = $rcon->getPlayers();

        $this->assertEquals([
            'name' => 'player',
            'score' => 1337
        ], $result);
    }

    public function testGetPlayersNotSupported()
    {
        $adapterMock = $this->createMock(Adapter::class);
        $adapterMock->method('connect');

        $rcon = new GRcon($adapterMock);

        $this->expectException(PlayersManageNotSupportedExceptions::class);
        $rcon->getPlayers();
    }
}

class Adapter implements ProtocolAdapterInterface
{
    public function connect(): void {}

    public function disconnect(): void {}

    public function execute($command): string
    {
        return '';
    }
}

class PlayersAdapter extends Adapter implements PlayersManageInterface
{
    public function getPlayers(): array
    {
        return [];
    }

    public function kick($playerId, string $reason = '') {}

    public function ban($playerId, string $reason = '') {}
}
