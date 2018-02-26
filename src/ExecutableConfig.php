<?php
namespace KS;

class ExecutableConfig extends AbstractCliConfig implements ExecutableConfigInterface
{
    public function getLogIdentifier(): string
    {
        return $this->get('log-identifier');
    }

    public function getLogLevel(): int
    {
        return $this->get('log-level');
    }
}

