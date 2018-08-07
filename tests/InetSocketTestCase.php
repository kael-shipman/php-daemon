<?php
namespace KS;

abstract class InetSocketTestCase extends SocketTestCase
{
    protected $listeningSocket;
    protected $clientSocket;
    protected $serverSocket;

    protected function setupConnections()
    {
        $this->listeningSocket = InetSocket::newInetSocket();
        $this->clientSocket = InetSocket::newInetSocket();

        $this->assertSame(Result::SUCCEEDED, $this->listeningSocket->bind("127.0.0.1",0));
        $boundPort = $this->listeningSocket->getPort();
        $this->assertSame(Result::SUCCEEDED, $this->listeningSocket->setBlocking(false));
        $this->assertSame(Result::SUCCEEDED, $this->listeningSocket->listen());

        $this->assertSame(Result::SUCCEEDED, $this->clientSocket->setBlocking(false));
        $this->clientSocket->connect("127.0.0.1:".$boundPort);

        $this->assertTrue($this->isReadReady($this->listeningSocket));
        $this->serverSocket = $this->listeningSocket->accept();
        $this->assertNotFalse($this->serverSocket);
        $this->assertSame(Result::SUCCEEDED, $this->serverSocket->setBlocking(false));
        $this->listeningSocket->close();

        $this->assertTrue($this->hasConnected($this->clientSocket));
            }

}