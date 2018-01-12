<?php
namespace KS;

class MessageDaemonConfig extends \KS\BaseConfig implements ConfigInterface
{
    public function getErrorReporting() : int
    {
        return $this->get('error-reporting');
    }
    public function getDisplayErrors() : int
    {
        return $this->get('display-errors');
    }
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
    public function getVerbosity() : int
    {
        return $this->get('verbosity');
    }
}

