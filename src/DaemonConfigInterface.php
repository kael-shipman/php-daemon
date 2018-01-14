<?php
namespace KS;

interface DaemonConfigInterface extends \KS\BaseConfigInterface
{
    public function getPhpErrorLevel() : int;
    public function getPhpDisplayErrors() : int;
    public function getVerbosity() : int;
    public function getLogLevel() : string;
}

