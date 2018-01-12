<?php
namespace KS;

interface MessageDaemonConfigInterface extends \KS\BaseConfigInterface
{
    public function getErrorReporting() : int;
    public function getDisplayErrors() : int;
    public function getSocketDomain() : int;
    public function getSocketType() : int;
    public function getSocketProtocol() : int;
    public function getSocketAddress() : string;
    public function getSocketPort() : ?string;
    public function getVerbosity() : int;
}
