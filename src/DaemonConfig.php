<?php
namespace KS;

class DaemonConfig extends \KS\BaseConfig implements DaemonConfigInterface
{
    public function getPhpErrorLevel() : int
    {
        return $this->get('php-error-level');
    }
    public function getPhpDisplayErrors() : int
    {
        return $this->get('php-display-errors');
    }
    public function getVerbosity() : int
    {
        return $this->get('verbosity');
    }
    public function getLogLevel() : string
    {
        return $this->get('log-level');
    }
}


