<?php
namespace KS;

class BaseSocket
{
    protected $socket=-1;

    protected static function createRawSocket(int $socketDomain, int $socketType, int $socketProtocol)
    {
        return \socket_create($socketDomain, $socketType, $socketProtocol);
    }
    public static function create(int $socketDomain, int $socketType, int $socketProtocol) : ?BaseSocket
    {
        return new BaseSocket(createRawSocket($socketDomain, $socketType, $socketProtocol));
    }

    public static function getLastGlobalErrorStr() : string
    {
        return \socket_strerror(socket_last_error());
    }

    public static function isBaseSocket($socket) : bool
    {
        return \is_a($socket, "\\KS\\BaseSocket");
    }

    // This accepts either a PHP socket resource or an object derived from BaseSocket
    public function __construct($socket)
    {
        if (BaseSocket::isBaseSocket($socket)) {
            $socket = $socket->getRawSocket();
        }
        $this->socket = $socket;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * getSocketForMove is used when you are moving an underlying socket from one class to another.
     * i.e. An InetSocket into a BufferedSocket
     * This prevents the destruction of this socket from closing the active socket (because it's being moved and not copied)
     * Only 1 BaseSocket derived object should own a socket resource
     * USAGE: $newSocket = new BufferedSocket($inetSocket->getSocketForMove());
     */
    public function getSocketForMove()
    {
        $socket = $this->socket;
        $this->invalidateSocket();
        return $socket;
    }

    public function setBlocking(bool $shouldBlock) : int
    {
        if (!$this->isSocketValid()) {
            return Result::FAILED;
        }
        $result=false;
        if ($shouldBlock) {
            $result = \socket_set_block($this->socket);
        }
        else {
            $result = \socket_set_nonblock($this->socket);
        }
        return $result===true ? Result::SUCCEEDED : Result::FAILED;
    }

    public function getRawSocket()
    {
        return $this->socket;
    }

    public function isSocketValid() : bool
    {
        return $this->socket != -1;
    }

    public function close() : void
    {
        if (!$this->isSocketValid()) {
            return;
        }
        \socket_close($this->socket);
        $this->invalidateSocket();
    }

    public function writeData(string $data) : ?int
    {
        if (!$this->isSocketValid()) {
            return null;
        }
        return \socket_write($this->socket, $data);
    }

    public function readData() : ?string
    {
        if (!$this->isSocketValid()) {
            return null;
        }
        return \socket_read($this->socket, 65*1024, PHP_BINARY_READ);
    }

    public function writeBaseMsg(string $msg, string $address, int $port) : ?int
    {
        if (!$this->isSocketValid()) {
            return null;
        }
        return \socket_sendto($this->socket, $msg, 0, $address, $port);
    }
    
    public function readBaseMsg(string $address) : ?string
    {
        if (!$this->isSocketValid()) {
            return null;
        }
        $buffer = "";
        $port = null;
        $result = \socket_recvfrom($this->socket, $buffer, 64*1024, 0, $address, $port);
        return $buffer;
    }

    public function getLastErrorStr() : string
    {
        return \socket_strerror(\socket_last_error($this->socket));
        
    }

    protected function invalidateSocket() : void
    {
        $this->socket = -1;
    }
}
