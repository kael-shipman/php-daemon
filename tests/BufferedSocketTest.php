<?php
namespace KS;

class BufferedSocketTests extends InetSocketTestCase
{
    public function testBuffering()
    {
        $this->setupConnections();

        $bufferedSocket = new BufferedSocket($this->serverSocket->getSocketForMove());

        $data = "ABCDEF";
        $this->assertSame(\strlen($data), $this->clientSocket->writeData($data));

        $this->assertTrue($this->isReadReady($bufferedSocket));
        $bufferedSocket->processReadReady();

        $this->assertSame(\strlen($data), $bufferedSocket->getBufferLength());

        $this->assertSame(\strlen($data), $this->clientSocket->writeData($data));
        $this->assertSame(\strlen($data), $this->clientSocket->writeData($data));

        $this->assertTrue($this->isReadReady($bufferedSocket));
        $bufferedSocket->processReadReady();

        $this->assertSame(\strlen($data)*3, $bufferedSocket->getBufferLength());

        //Process buffer
        $this->assertSame($data.$data.$data, $bufferedSocket->getReadBuffer());
        $bufferedSocket->consumeReadBuffer(\strlen($data)-1);

        $this->assertSame("F".$data.$data, $bufferedSocket->getReadBuffer());

        $readData = $bufferedSocket->getAndConsumeReadBuffer(2);
        $this->assertSame("FA", $readData);

        $this->assertSame("BCDEF".$data, $bufferedSocket->getReadBuffer());

        $this->clientSocket->close();
        $bufferedSocket->close();
    }
}
