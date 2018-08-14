<?php
namespace KS;

class SelectSocketLoopTest extends AbstractInetSocketTestCase
{
    public function testEventReady()
    {
        $loop = new SelectSocketLoop();

        $listeningSocket = InetSocket::createInetSocket();
        $listeningSocket->setBlocking(false);
        
        $listeningSocket->bind("127.0.0.1", 0);
        $listeningSocket->listen();
        $port = $listeningSocket->getPort();

        $noDelay = new TimeDuration(TimeDuration::NANOS, 0);
        $secondDelay = new TimeDuration(TimeDuration::SECONDS, 1);

        $this->assertSame(0, $loop->getReadWatchCount());
        $loop->watchForRead($listeningSocket);
        $this->assertSame(1, $loop->getReadWatchCount());

        $readyList = $loop->waitForEvents($noDelay);
        $this->assertSame(0, count($readyList));

        $clientSocket = InetSocket::createInetSocket();
        $clientSocket->setBlocking(false);

        $clientSocket->connect("127.0.0.1:".$port);

        $readyList = $loop->waitForEvents($secondDelay);
        $this->assertSame(1, count($readyList));
        $this->assertSame($listeningSocket, $readyList[0]);

        $serverSocket = $listeningSocket->accept();

        $loop->watchForRead($serverSocket);
        $loop->watchForRead($clientSocket);
        $this->assertSame(3, $loop->getReadWatchCount());

        $loop->unwatchForRead($listeningSocket);
        $this->assertSame(2, $loop->getReadWatchCount());
        $listeningSocket->close();

        $clientSocket->writeData("ABCD");
        $readyList = $loop->waitForEvents($secondDelay);
        $this->assertSame(1, count($readyList));

        $this->assertSame($serverSocket, $readyList[0]);
        $data = $serverSocket->readData();
        $readyList = $loop->waitForEvents($noDelay);
        $this->assertSame(0, count($readyList));

        $serverSocket->writeData("ABCD");
        $clientSocket->writeData("ABCD");
        $loop->unwatchForRead($listeningSocket);

        $readyList = $loop->waitForEvents($secondDelay);
        $this->assertSame(2, count($readyList));

        // Sockets should be ready in the order they were registered
        $this->assertSame($serverSocket, $readyList[0]);
        $this->assertSame($clientSocket, $readyList[1]);

        $serverSocket->readData();

        $serverSocket->close();
        $clientSocket->close();

    }
}
