<?php
namespace KS;

class BaseSocket
{
    protected $socket=-1;

    public static function newRawSocket($socketDomain, $socketType, $socketProtocol)
    {
        return \socket_create($socketDomain, $socketType, $socketProtocol);
    }
    public static function newSocket($socketDomain, $socketType, $socketProtocol)
    {
        return new BaseSocket(newRawSocket($socketDomain, $socketType, $socketProtocol));
    }

    public function getSocketForMove()
    {
        $socket = $this->socket;
        $this->invalidateSocket();
        return $socket;
    }

    public static function getLastGlobalErrorStr()
    {
        return \socket_strerror(socket_last_error());
    }

    public function __construct($socket)
    {
        if (\is_a($socket, "\\KS\\BaseSocket")) {
            $socket = $socket->getRawSocket();
        }
        $this->socket = $socket;
    }

    public function __destruct()
    {
        $this->close();
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
        return $this->socket;
    }

    public function isSocketValid()
    {
        return $this->socket != -1;
    }

    public function close()
    {
        if (!$this->isSocketValid()) {
            return;
        }
        \socket_close($this->socket);
        $this->invalidateSocket();
    }

    public function writeData($data)
    {
        return \socket_write($this->socket, $data);
    }

    public function readData()
    {
        return \socket_read($this->socket, 65*1024, PHP_BINARY_READ);
    }

    public function writeBaseMsg($msg, $address, $port)
    {
        return \socket_sendto($this->socket, $msg, 0, $address, $port);
    }
    
    public function readBaseMsg($address)
    {
        $buffer = "";
        $port = null;
        $result = \socket_recvfrom($this->socket, $buffer, 64*1024, 0, $address, $port);
        return $buffer;
    }

    public function getLastErrorStr()
    {
        return \socket_strerror(\socket_last_error($this->socket));
        
    }

    protected function invalidateSocket() : void
    {
        $this->socket = -1;
    }
}
