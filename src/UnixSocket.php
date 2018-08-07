<?php
namespace KS;

class UnixSocket extends BaseSocket
{
    private $filename="";

    public function __construct($socket, $filename)
    {
        parent::__construct($socket);
        $this->filename = $filename;
    }

    public static function newUnixSocket($filename, $socketType=\SOCK_STREAM, $socketProtocol=0)
    {
        return new UnixSocket(parent::newRawSocket(\AF_UNIX, $socketType, $socketProtocol), $filename);
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function connect()
    {
        $result = \socket_connect($this->socket, $this->filename, 0);

        return $result?Result::SUCCEEDED:Result::FAILED;
    }

    public function bind()
    {
        if (\socket_bind($this->socket, $this->filename) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

    public function accept()
    {
        return new BaseSocket(\socket_accept($this->socket));
    }

    public function listen($maxConnectionAttempts=5)
    {
        if (\socket_listen($this->socket, $maxConnectionAttempts) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

    public function writeMsg($msg)
    {
        return parent::writeBaseMsg($msg, $this->filename, null);
    }
    
    public function readMsg($msg)
    {
        $buffer = "";
        $port = null;
        return parent::readBaseMsg($this->filename);
    }

}
