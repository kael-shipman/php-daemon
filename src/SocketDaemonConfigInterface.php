<?php
namespace KS;

interface SocketDaemonConfigInterface extends ExecutableConfigInterface
{
    public function getSocketDomain() : int;
    public function getSocketType() : int;
    public function getSocketProtocol() : int;
    public function getSocketAddress() : string;
    public function getSocketPort() : ?string;
}

