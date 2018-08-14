<?php
namespace KS;

class UnixSocket extends BaseSocket
{
    private $filename="";

    public function __construct($socket, string $filename)
    {
        parent::__construct($socket);
        $this->filename = $filename;
    }

    public static function createUnixSocket(string $filename, int $socketType=\SOCK_STREAM, int $socketProtocol=0)
    {
        return new UnixSocket(parent::createRawSocket(\AF_UNIX, $socketType, $socketProtocol), $filename);
    }

    public function getFilename() : string
    {
        return $this->filename;
    }

    public function connect() : int
    {
        $result = \socket_connect($this->socket, $this->filename, 0);

        return $result ? Result::SUCCEEDED : Result::FAILED;
    }

    public function bind() : int
    {
        if (\socket_bind($this->socket, $this->filename) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

    public function accept() : BaseSocket
    {
        return new BaseSocket(\socket_accept($this->socket));
    }

    public function listen(int $maxConnectionAttempts=5)
    {
        if (\socket_listen($this->socket, $maxConnectionAttempts) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

    public function writeMsg(string $msg)
    {
        return parent::writeBaseMsg($msg, $this->filename, null);
    }
    
    public function readMsg() : string
    {
        $buffer = "";
        $port = null;
        return parent::readBaseMsg($this->filename);
    }

}
