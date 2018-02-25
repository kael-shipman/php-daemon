<?php
namespace KS\Test;

class ExecutableConfig implements \KS\ExecutableConfigInterface
{
    public function getLogLevel() : int
    {
        return LOG_DEBUG;
    }

    public function getLogIdentifier() : string
    {
        return 'TestExecutable';
    }

    public function reload(): void
    {
        return;
    }

    public function checkConfig(): void
    {
        return;
    }
}

