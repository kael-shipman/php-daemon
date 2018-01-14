<?php
namespace KS;

class SocketDaemonConfig extends \KS\BaseConfig implements SocketDaemonConfigInterface
{
    public function getSocketDomain() : int
    {
        return $this->get('socket-domain');
    }
    public function getSocketType() : int
    {
        return $this->get('socket-type');
    }
    public function getSocketProtocol() : int
    {
        return $this->get('socket-protocol');
    }
    public function getSocketAddress() : string
    {
        return $this->get('socket-address');
    }
    public function getSocketPort() : ?string
    {
        return $this->get('socket-port');
    }
}

