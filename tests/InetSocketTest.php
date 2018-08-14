<?php
namespace KS;

class InetSocketTest extends AbstractInetSocketTestCase
{

    public function testCommunication()
    {
        $this->setupConnections();

        $this->assertFalse($this->isReadReady($this->serverSocket, new TimeDuration(TimeDuration::MILLIS, 250)));

        $data = "ABCDEF";
        $this->assertSame(\strlen($data), $this->clientSocket->writeData($data));

        $readData = $this->serverSocket->readData();
        $this->assertSame($data, $readData);

        $this->clientSocket->close();
        $this->serverSocket->close();
    }
}
