<?php
namespace KS;

class UnixSocketTest extends SocketTestCase
{
    private $filename="";

    public function setUp()
    {
        $this->filename = \sys_get_temp_dir()."/UNIX_socket_".uniqid(rand(), true).'.socket';
    }

    public function tearDown()
    {
        if (\file_exists($this->filename)) {
            \unlink($this->filename);
        }
    }

    public function testCommunication()
    {
        $listeningSocket = UnixSocket::newUnixSocket($this->filename);
        $this->assertNotFalse($listeningSocket->getRawSocket());
        $listeningSocket->setBlocking(false);

        $clientSocket = UnixSocket::newUnixSocket($this->filename);
        $this->assertNotFalse($clientSocket->getRawSocket());
        $clientSocket->setBlocking(false);

        $listeningSocket->bind();
        $listeningSocket->listen();

        $clientSocket->connect();

        $this->assertTrue($this->isReadReady($listeningSocket));

        $serverSocket = $listeningSocket->accept();
        $listeningSocket->close();

        $serverSocket->writeData("ABCDEF");

        $this->assertTrue($this->isReadReady($clientSocket));

        $msg = $clientSocket->readData();
        $this->assertSame("ABCDEF", $msg);

        $serverSocket->close();
        $clientSocket->close();
    }
}
