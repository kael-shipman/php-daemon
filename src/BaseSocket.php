<?php
namespace KS;

class BaseSocket
{
    protected $socket=0;

    public static function newRawSocket($socketDomain, $socketType, $socketProtocol)
    {
        return \socket_create($socketDomain, $socketType, $socketProtocol);
    }

    public static function newSocket($socketDomain, $socketType, $socketProtocol)
    {
        return new BaseSocket(newRawSocket($socketDomain, $socketType, $socketProtocol));
    }

    public static function getLastGlobalErrorStr()
    {
        return \socket_strerror(socket_last_error());
    }

    public function __construct($socket)
    {
        if (\is_subclass_of($socket, "BaseSocket")) {
            $socket = $socket->getRawSocket();
        }
        $this->socket = $socket;
    }

    public function __destruct()
    {
        $this->closeSocket();
    }

    public function setBlocking($shouldBlock)
    {
        $result=false;
        if ($shouldBlock) {
            $result = \socket_set_block($this->socket);
        }
        else {
            $result = \socket_set_nonblock($this->socket);
        }
        return $result===true?Result::SUCCEEDED:Result::FAILED;
    }

    public function getRawSocket()
    {
        return $socket;
    }

    public function isSocketValid()
    {
        return $socket != 0;
    }

    public function closeSocket()
    {
        if (!$this->isSocketValid()) {
            return;
        }
        \socket_close($this->socket);
    }

    public function bind($address, $port)
    {
        if (\socket_set_nonblock($this->socket) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

    public function accept()
    {
        return new BaseSocket(\socket_accept($this->$socket));
    }

    public function listen($maxConnectionAttempts=5)
    {
        if (\socket_listen($this->socket, $maxConnectionAttempts) === true) {
            return Result::SUCCEEDED;
        }
        return Result::FAILED;
    }

    public function writeData($data)
    {
        return \socket_write($this->socket, $data);
    }

    public function readData()
    {
        return \socket_read($this->socket, 2048, PHP_NORMAL_READ);
    }

    public function getLastErrorStr()
    {
        return \socket_strerror(\socket_last_error($this->socket));
    }
}
