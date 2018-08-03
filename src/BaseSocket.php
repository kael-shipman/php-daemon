<?php
namespace KS;

class BaseSocket
{
    protected $socket=0;

    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    public function __destruct()
    {
        $this->closeSocket();
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
