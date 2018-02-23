<?php
namespace KS;

interface DaemonConfigInterface extends \KS\BaseConfigInterface
{
    public function getPhpErrorLevel() : int;
    public function getPhpDisplayErrors() : int;
    public function getLogLevel() : int;
    public function getLogIdentifier() : string;
}

