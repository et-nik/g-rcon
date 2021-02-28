<?php

namespace Knik\GRcon\Tests\Protocols;

use Knik\GRcon\Interfaces\SocketClientInterface;
use Knik\GRcon\Protocols\CsgoAdapter;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class CsgoAdapterTest extends TestCase
{
    public function testExecute(): void
    {
        $adapter = $this->getAdapter();
        $socketClient = $this->createMock(SocketClientInterface::class);
        $adapter->setConnection($socketClient);
        $response = pack("VV", 1, 2) . "result" . "\x00\x00";
        $size = pack("V", strlen($response)) . $response;
        $socketClient->method('read')->willReturnOnConsecutiveCalls($size, $response, '');

        $result = $adapter->execute('status');

        Assert::assertEquals("result", $result);
    }

    private function getAdapter(): CsgoAdapter
    {
        return new CsgoAdapter([
            'host'          => '127.0.0.1',
            'port'          => 27015,
            'password'      => '123'
        ]);
    }
}
